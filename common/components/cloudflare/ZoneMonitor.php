<?php

namespace common\components\cloudflare;

use Yii;
use yii\caching\CacheInterface;
use yii\caching\FileCache;

class ZoneMonitor
{
    private array $accounts;
    private CacheInterface $cache;

    public function __construct(array $accounts, CacheInterface $cache)
    {
        $this->accounts = $accounts;
        $this->cache = $cache;
    }

    public static function configured(): self
    {
        return new self(Yii::$app->params['cloudflare']['accounts'] ?? [], new FileCache([
            'cachePath' => '@common/runtime/cloudflare',
        ]));
    }

    public function synchronize(): array
    {
        $snapshot = ['complete' => !empty($this->accounts), 'accounts' => []];
        foreach ($this->accounts as $label => $config) {
            $id = (string)($config['id'] ?? '');
            $token = (string)($config['apikey'] ?? '');
            $key = ['cloudflare-zones-v1', $id];
            try {
                if ($id === '' || $token === '') {
                    throw new \RuntimeException('Missing configuration');
                }
                $zones = [];
                $page = 1;
                $expectedPages = null;
                $expectedCount = null;
                do {
                    $response = $this->requestZones($id, $token, $page);
                    $info = $response['result_info'] ?? [];
                    if (($response['success'] ?? false) !== true
                        || !is_array($response['result'] ?? null)
                        || (int)($info['page'] ?? 0) !== $page
                        || !isset($info['total_pages'], $info['total_count'])
                    ) {
                        throw new \RuntimeException('Incomplete response');
                    }
                    $totalPages = max(1, (int)$info['total_pages']);
                    $totalCount = (int)$info['total_count'];
                    if ($totalCount < 0 || ($expectedPages !== null
                        && ($expectedPages !== $totalPages || $expectedCount !== $totalCount))) {
                        throw new \RuntimeException('Pagination changed during synchronization');
                    }
                    $expectedPages = $totalPages;
                    $expectedCount = $totalCount;
                    foreach ($response['result'] as $zone) {
                        if (!is_array($zone) || empty($zone['id']) || empty($zone['name'])
                            || !isset($zone['status']) || ($zone['account']['id'] ?? null) !== $id
                        ) {
                            throw new \RuntimeException('Invalid zone');
                        }
                        $zones[$zone['id']] = [
                            'id' => (string)$zone['id'],
                            'name' => self::normalizeHost($zone['name']),
                            'status' => (string)$zone['status'],
                        ];
                    }
                    $page++;
                } while ($page <= $totalPages);
                if (count($zones) !== $totalCount) {
                    throw new \RuntimeException('Incomplete zone list');
                }
                $data = ['zones' => array_values($zones), 'checked_at' => gmdate('c')];
                $this->cache->set($key, $data, 0);
                $stale = false;
            } catch (\Throwable $exception) {
                // Never log credentials, response bodies or transport exception messages.
                Yii::warning('Cloudflare zone synchronization failed.', 'cloudflare');
                $cached = $id !== '' ? $this->cache->get($key) : false;
                $data = is_array($cached) ? $cached : ['zones' => [], 'checked_at' => null];
                $stale = true;
                $snapshot['complete'] = false;
            }
            $snapshot['accounts'][] = $data + [
                'account_id' => $id, 'account_label' => (string)$label, 'stale' => $stale,
            ];
        }
        return $snapshot;
    }

    protected function requestZones(string $id, string $token, int $page): array
    {
        $curl = curl_init('https://api.cloudflare.com/client/v4/zones?' . http_build_query([
            'account.id' => $id, 'page' => $page, 'per_page' => 50,
        ]));
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        try {
            $body = curl_exec($curl);
            $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($body === false || $code !== 200) {
                throw new \RuntimeException('Cloudflare request failed');
            }
            $result = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($result)) {
                throw new \RuntimeException('Invalid response');
            }
            return $result;
        } finally {
            curl_close($curl);
        }
    }

    public static function normalizeHost(string $value): string
    {
        $host = parse_url(str_contains($value, '://') ? $value : 'https://' . $value, PHP_URL_HOST);
        $host = strtolower(rtrim((string)$host, '.'));
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false) {
                $host = strtolower($ascii);
            }
        }
        return $host;
    }

    public static function match(string $url, array $snapshot): array
    {
        $host = self::normalizeHost($url);
        $matches = [];
        $accounts = [];
        foreach ($snapshot['accounts'] as $account) {
            $best = null;
            foreach ($account['zones'] as $zone) {
                $name = $zone['name'];
                if ($host !== '' && $name !== '' && ($host === $name || str_ends_with($host, '.' . $name))
                    && ($best === null || strlen($name) > strlen($best['name']))
                ) {
                    $best = $zone;
                }
            }
            $metadata = array_intersect_key($account, array_flip([
                'account_id', 'account_label', 'checked_at', 'stale',
            ]));
            $accounts[] = $metadata;
            if ($best !== null) {
                $matches[] = $best + $metadata;
            }
        }
        return [
            'state' => $matches ? 'found' : (!empty($snapshot['complete']) && $host !== '' ? 'missing' : 'unknown'),
            'stale' => empty($snapshot['complete']),
            'checked_at' => gmdate('c'),
            'zones' => $matches,
            'accounts' => $accounts,
        ];
    }
}
