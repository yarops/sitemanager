<?php

namespace common\components\dnssync;

use Yii;

/** Current delegation of a domain (NS records) via a public DNS-over-HTTPS resolver. */
class NameServers
{
    private const RESOLVER = 'https://dns.google/resolve';
    private const TYPE_NS = 2;
    private const STATUS_NXDOMAIN = 3;

    /**
     * @return string[]|null sorted NS names without trailing dot; null when the domain does not exist
     * @throws \RuntimeException
     */
    public function lookup(string $domain): ?array
    {
        return self::parse($this->request($domain));
    }

    public static function parse(?array $response): ?array
    {
        if (!is_array($response) || !isset($response['Status'])) {
            throw new \RuntimeException('Некорректный ответ DNS');
        }
        if ((int)$response['Status'] === self::STATUS_NXDOMAIN) {
            return null;
        }
        if ((int)$response['Status'] !== 0) {
            throw new \RuntimeException('Ошибка DNS, код ' . (int)$response['Status']);
        }
        $servers = [];
        foreach ((array)($response['Answer'] ?? []) as $answer) {
            if ((int)($answer['type'] ?? 0) === self::TYPE_NS && !empty($answer['data'])) {
                $servers[] = RecordMapper::normalizeName((string)$answer['data']);
            }
        }
        $servers = array_values(array_unique($servers));
        sort($servers);
        return $servers;
    }

    /** Assigned pair is active only when the delegation is exactly the Cloudflare pair of the zone. */
    public static function state(?array $current, array $assigned): string
    {
        if ($current === null) {
            return 'nxdomain';
        }
        if (!$current) {
            return 'none';
        }
        $assigned = array_map([RecordMapper::class, 'normalizeName'], $assigned);
        sort($assigned);
        if ($assigned && $current === $assigned) {
            return 'cloudflare';
        }
        foreach ($current as $server) {
            if (!str_ends_with($server, '.ns.cloudflare.com')) {
                return 'other';
            }
        }
        return 'cloudflare-other';
    }

    protected function request(string $domain): ?array
    {
        $curl = curl_init(self::RESOLVER . '?' . http_build_query(['name' => $domain, 'type' => 'NS']));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        try {
            $body = curl_exec($curl);
            if ($body === false || (int)curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                Yii::warning('DNS-over-HTTPS NS lookup failed.', 'dnssync');
                throw new \RuntimeException('DNS-резолвер недоступен');
            }
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : null;
        } finally {
            curl_close($curl);
        }
    }
}
