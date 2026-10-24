<?php

namespace common\components\cloudflare;

use Yii;

/** Cloudflare zones and DNS records for the DNS sync tool (dnsApiKey tokens). */
class DnsRecords
{
    private const API = 'https://api.cloudflare.com/client/v4/';
    private const PER_PAGE = 100;
    private const MAX_PAGES = 100;
    private const MAX_RETRY_AFTER = 30;
    private const DEFAULT_RETRY_AFTER = 5;

    /** @var array label => ['id' => account id, 'dnsApiKey' => token] */
    private array $accounts = [];

    public function __construct(array $accounts)
    {
        foreach ($accounts as $label => $config) {
            // Accounts without a sync token do not take part in the sync.
            if (!empty($config['id']) && !empty($config['dnsApiKey'])) {
                $this->accounts[(string)$label] = $config;
            }
        }
    }

    public static function configured(): self
    {
        return new self(Yii::$app->params['cloudflare']['accounts'] ?? []);
    }

    /** @return string[] labels of accounts with a sync token */
    public function accountLabels(): array
    {
        return array_keys($this->accounts);
    }

    /**
     * Finds the zone with exactly this name in all accounts.
     *
     * @return array ['state' => found|missing|ambiguous|error, 'message'?, 'zone_id'?, 'account'?, 'status'?, 'name_servers'?]
     */
    public function findZone(string $domain): array
    {
        if (!$this->accounts) {
            return ['state' => 'error', 'message' => 'Нет аккаунтов Cloudflare с dnsApiKey'];
        }
        $domain = strtolower(rtrim($domain, '.'));
        $matches = [];
        $errors = [];
        foreach ($this->accounts as $label => $config) {
            try {
                $response = $this->send($label, 'GET', 'zones?' . http_build_query([
                    'name' => $domain, 'account.id' => $config['id'], 'per_page' => 50,
                ]));
                if (!is_array($response['result'] ?? null)) {
                    throw new \RuntimeException('Некорректный ответ Cloudflare');
                }
                foreach ($response['result'] as $zone) {
                    // The API filter is not trusted: only an exact name in this account counts.
                    if (is_array($zone) && !empty($zone['id'])
                        && strtolower(rtrim((string)($zone['name'] ?? ''), '.')) === $domain
                        && (string)($zone['account']['id'] ?? '') === (string)$config['id']
                    ) {
                        $matches[] = [
                            'zone_id' => (string)$zone['id'],
                            'account' => $label,
                            'status' => (string)($zone['status'] ?? ''),
                            'name_servers' => array_values((array)($zone['name_servers'] ?? [])),
                        ];
                    }
                }
            } catch (\Throwable $exception) {
                $errors[] = $label . ': ' . $exception->getMessage();
            }
        }
        if (count($matches) > 1) {
            return ['state' => 'ambiguous', 'message' => 'Найдено несколько зон: '
                . implode(', ', array_map(static fn(array $zone): string => $zone['account'], $matches))];
        }
        if ($errors) {
            // Uniqueness and absence are not proven while any account is unreadable.
            return ['state' => 'error', 'message' => 'Поиск неполный: ' . implode('; ', $errors)];
        }
        return $matches ? ['state' => 'found'] + $matches[0] : ['state' => 'missing'];
    }

    /**
     * All DNS records of a zone. Completeness is judged by pages actually read:
     * right after a batch total_count may still be stale.
     *
     * @return array[] ['id', 'type', 'name', 'content', 'priority', 'proxied', 'ttl']
     */
    public function list(string $account, string $zoneId): array
    {
        $records = [];
        $page = 1;
        do {
            $response = $this->send($account, 'GET', 'zones/' . rawurlencode($zoneId) . '/dns_records?'
                . http_build_query(['per_page' => self::PER_PAGE, 'page' => $page]));
            $result = $response['result'] ?? null;
            if (!is_array($result) || (int)($response['result_info']['page'] ?? 0) !== $page) {
                throw new \RuntimeException('Некорректный ответ Cloudflare');
            }
            foreach ($result as $record) {
                if (!is_array($record) || empty($record['id']) || empty($record['type'])) {
                    throw new \RuntimeException('Некорректная запись Cloudflare');
                }
                $records[$record['id']] = [
                    'id' => (string)$record['id'],
                    'type' => (string)$record['type'],
                    'name' => (string)($record['name'] ?? ''),
                    'content' => (string)($record['content'] ?? ''),
                    'priority' => isset($record['priority']) ? (int)$record['priority'] : null,
                    'proxied' => (bool)($record['proxied'] ?? false),
                    'ttl' => (int)($record['ttl'] ?? 1),
                ];
            }
            $totalPages = (int)($response['result_info']['total_pages'] ?? 1);
            $page++;
        } while (($page <= $totalPages || count($result) === self::PER_PAGE) && $page <= self::MAX_PAGES);
        if (count($result) === self::PER_PAGE) {
            throw new \RuntimeException('Слишком много записей в зоне Cloudflare');
        }
        return array_values($records);
    }

    /**
     * Replaces records in one transaction: Cloudflare applies all operations or none.
     *
     * @param string[] $deletes record ids
     * @param array[] $posts new records
     * @return array ['deletes' => [...], 'posts' => [...]]
     * @throws BatchTimeout when the result is unknown
     */
    public function batch(string $account, string $zoneId, array $deletes, array $posts): array
    {
        $body = [];
        if ($deletes) {
            $body['deletes'] = array_map(static fn(string $id): array => ['id' => $id], array_values($deletes));
        }
        if ($posts) {
            $body['posts'] = array_values($posts);
        }
        try {
            // No retry: on 429 nothing is applied, the user restarts the zone manually.
            $response = $this->send($account, 'POST', 'zones/' . rawurlencode($zoneId) . '/dns_records/batch', $body, false);
        } catch (RequestTimeout $exception) {
            throw new BatchTimeout('Таймаут batch, результат неизвестен');
        }
        $result = $response['result'] ?? null;
        if (!is_array($result)) {
            throw new \RuntimeException('Некорректный ответ batch Cloudflare');
        }
        return ['deletes' => (array)($result['deletes'] ?? []), 'posts' => (array)($result['posts'] ?? [])];
    }

    /**
     * Adds a zone without DNS import and without changing its settings.
     *
     * @return array ['zone_id', 'status', 'name_servers']
     */
    public function createZone(string $account, string $domain): array
    {
        $response = $this->send($account, 'POST', 'zones', [
            'account' => ['id' => (string)($this->accounts[$account]['id'] ?? '')],
            'name' => $domain,
            'type' => 'full',
            'jump_start' => false,
        ], false);
        $zone = $response['result'] ?? null;
        if (!is_array($zone) || empty($zone['id'])) {
            throw new \RuntimeException('Некорректный ответ Cloudflare');
        }
        return [
            'zone_id' => (string)$zone['id'],
            'status' => (string)($zone['status'] ?? ''),
            'name_servers' => array_values((array)($zone['name_servers'] ?? [])),
        ];
    }

    /** One API call with a single retry on 429 for idempotent requests. */
    private function send(string $account, string $method, string $path, ?array $body = null, bool $retry = true): array
    {
        $token = (string)($this->accounts[$account]['dnsApiKey'] ?? '');
        if ($token === '') {
            throw new \RuntimeException("Нет dnsApiKey для аккаунта $account");
        }
        [$code, $headers, $response] = $this->request($token, $method, $path, $body);
        if ($code === 429) {
            if (!$retry) {
                throw new \RuntimeException('Rate limit Cloudflare, запрос не применён');
            }
            $after = isset($headers['retry-after']) && is_numeric($headers['retry-after'])
                ? (int)$headers['retry-after'] : self::DEFAULT_RETRY_AFTER;
            $this->sleep(max(1, min(self::MAX_RETRY_AFTER, $after)));
            [$code, $headers, $response] = $this->request($token, $method, $path, $body);
            if ($code === 429) {
                throw new \RuntimeException('Rate limit Cloudflare');
            }
        }
        if ($code < 200 || $code >= 300 || !is_array($response) || ($response['success'] ?? false) !== true) {
            throw new \RuntimeException(self::errorMessage($code, $response));
        }
        return $response;
    }

    /** Only Cloudflare's own error code and text, never the request or the body. */
    private static function errorMessage(int $code, $response): string
    {
        $error = is_array($response) ? ($response['errors'][0] ?? null) : null;
        if (is_array($error) && isset($error['message'])) {
            return 'Cloudflare ' . ($error['code'] ?? $code) . ': ' . $error['message'];
        }
        return 'Cloudflare HTTP ' . $code;
    }

    /**
     * One HTTP call, no retries.
     *
     * @return array [int $httpCode, array $headers (lowercase names), ?array $decodedBody]
     */
    protected function request(string $token, string $method, string $path, ?array $body): array
    {
        $headers = [];
        $curl = curl_init(self::API . $path);
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $method === 'GET' ? 30 : 60,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($curl, $options);
        try {
            $response = curl_exec($curl);
            if ($response === false) {
                // Never log credentials, response bodies or transport exception messages.
                Yii::warning('Cloudflare DNS request failed.', 'cloudflare');
                if (curl_errno($curl) === CURLE_OPERATION_TIMEDOUT) {
                    throw new RequestTimeout('Таймаут запроса к Cloudflare');
                }
                throw new \RuntimeException('Ошибка соединения с Cloudflare');
            }
            $decoded = json_decode($response, true);
            return [(int)curl_getinfo($curl, CURLINFO_HTTP_CODE), $headers, is_array($decoded) ? $decoded : null];
        } finally {
            curl_close($curl);
        }
    }

    protected function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
