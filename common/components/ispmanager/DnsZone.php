<?php

namespace common\components\ispmanager;

use common\models\Server;
use Yii;

/** Reads DNS records of one zone from ISPmanager (read only). */
class DnsZone
{
    /**
     * @return array[] ['type', 'name' (FQDN with trailing dot), 'content', 'priority' (MX only)]
     * @throws ZoneNotFound when the panel has no such zone
     * @throws \RuntimeException on transport or response errors
     */
    public function records(Server $server, string $zone): array
    {
        try {
            $body = $this->request($server, ['func' => 'domain.record', 'elid' => $zone]);
        } catch (\Throwable $exception) {
            // Request URLs, credentials and panel responses must never be logged.
            Yii::warning('ISPmanager DNS request failed.', 'ispmanager');
            throw new \RuntimeException('Ошибка запроса к ISPmanager');
        }
        return self::parse($body, $zone);
    }

    protected function request(Server $server, array $params): string
    {
        if (empty($server->ip) || empty($server->password)) {
            throw new \RuntimeException('Missing ISPmanager configuration');
        }
        $curl = curl_init('https://' . $server->ip . ':1500/ispmgr');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params + [
                'out' => 'json', 'authinfo' => 'root:' . $server->password,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            // Match the existing ISPmanager connection for panels with self-signed certificates.
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        try {
            $body = curl_exec($curl);
            if ($body === false || (int)curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new \RuntimeException('ISPmanager request failed');
            }
            return $body;
        } finally {
            curl_close($curl);
        }
    }

    public static function parse(string $body, string $zone): array
    {
        $data = json_decode($body, true);
        $doc = is_array($data) ? ($data['doc'] ?? null) : null;
        if (!is_array($doc)) {
            throw new \RuntimeException('Некорректный ответ ISPmanager');
        }
        // Errors come with HTTP 200 inside doc.error.
        if (isset($doc['error'])) {
            $error = $doc['error'];
            $message = trim((string)($error['msg']['$'] ?? 'неизвестная ошибка'));
            if (($error['$type'] ?? null) === 'missed' && ($error['$object'] ?? null) === 'domain') {
                throw new ZoneNotFound('Нет зоны в ISP: ' . $message);
            }
            throw new \RuntimeException('ISPmanager: ' . $message);
        }
        if (isset($doc['plid']['$']) && strtolower($doc['plid']['$']) !== strtolower($zone)) {
            throw new \RuntimeException('ISPmanager вернул записи другой зоны');
        }
        if (isset($doc['p_elems']['$'], $doc['p_cnt']['$']) && (int)$doc['p_elems']['$'] > (int)$doc['p_cnt']['$']) {
            throw new \RuntimeException('Зона не помещается в одну страницу ISPmanager');
        }

        $elements = $doc['elem'] ?? [];
        if (isset($elements['name'])) {
            $elements = [$elements];
        }
        $records = [];
        foreach ($elements as $element) {
            $type = strtoupper(trim((string)($element['rtype_hidden']['$'] ?? '')));
            $name = trim((string)($element['name']['$'] ?? ''));
            if ($type === '' || $name === '') {
                throw new \RuntimeException('Некорректная запись ISPmanager');
            }
            $priority = null;
            if ($type === 'MX') {
                // ISPmanager has no priority field; it is only shown in info as "priority = N".
                if (!preg_match('/priority\s*=\s*(\d+)/', (string)($element['info']['$'] ?? ''), $match)) {
                    throw new \RuntimeException("MX $name без priority");
                }
                $priority = (int)$match[1];
            }
            $records[] = [
                'type' => $type,
                'name' => $name,
                'content' => (string)($element['value']['$'] ?? ''),
                'priority' => $priority,
            ];
        }
        return $records;
    }
}
