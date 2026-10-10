<?php

namespace common\components\ispmanager;

use common\models\Server;
use Yii;

/** Reads the same month-to-date user traffic counters as the ISPmanager user list. */
class UserTraffic
{
    public function fetch(Server $server): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Yii::$app->timeZone));
        $snapshot = [
            'state' => 'unavailable',
            'users' => [],
            'period_start' => $now->format('Y-m-01'),
            'period_end' => $now->format('Y-m-d'),
            'checked_at' => $now->format(DATE_ATOM),
        ];
        try {
            $snapshot['users'] = self::parse($this->request($server));
            $snapshot['state'] = 'available';
        } catch (\Throwable $exception) {
            // Request URLs, credentials and panel responses must never be logged.
            Yii::warning('ISPmanager user traffic retrieval failed.', 'ispmanager');
        }
        return $snapshot;
    }

    protected function request(Server $server): string
    {
        if (empty($server->ip) || empty($server->password)) {
            throw new \RuntimeException('Missing ISPmanager configuration');
        }
        $curl = curl_init('https://' . $server->ip . ':1500/ispmgr');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'func' => 'user', 'out' => 'xml', 'authinfo' => 'root:' . $server->password,
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

    public static function parse(string $body): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET);
            if ($doc === false || $doc->getName() !== 'doc' || isset($doc->error)
                || preg_match('/<!DOCTYPE/i', $body)
            ) {
                throw new \RuntimeException('Invalid ISPmanager response');
            }
            $users = [];
            foreach ($doc->elem as $row) {
                $login = trim((string)$row->name);
                if ($login === '' || array_key_exists($login, $users)) {
                    throw new \RuntimeException('Invalid ISPmanager user list');
                }
                // Real panels expose used and limit as separate fields, despite the API's traff label.
                $value = isset($row->traff_used) ? (string)$row->traff_used : (string)$row->traff;
                if (!isset($row->traff_used) && str_contains($value, '/')) {
                    $value = explode('/', $value, 2)[0];
                }
                $value = trim($value);
                $users[$login] = preg_match('/^\d+(?:\.\d+)?$/D', $value) ? (float)$value : null;
            }
            return $users;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public static function forLogin(?string $login, array $snapshot): array
    {
        $value = $login !== null && $login !== '' ? ($snapshot['users'][$login] ?? null) : null;
        return [
            'state' => ($snapshot['state'] ?? null) === 'available' && $value !== null ? 'available' : 'unavailable',
            'login' => $login,
            'used_mib' => $value,
            'period_start' => $snapshot['period_start'] ?? null,
            'period_end' => $snapshot['period_end'] ?? null,
            'checked_at' => $snapshot['checked_at'] ?? null,
        ];
    }
}
