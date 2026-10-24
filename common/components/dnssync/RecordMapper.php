<?php

namespace common\components\dnssync;

/** Pure conversions between ISPmanager records, Cloudflare records and display lines. */
class RecordMapper
{
    private const DISPLAY_ORDER = ['A', 'AAAA', 'CNAME', 'MX', 'TXT'];
    private const PROXIABLE = ['A', 'AAAA', 'CNAME'];

    /** Lowercase, no trailing dot, IDN labels in punycode; wildcard and service labels are kept. */
    public static function normalizeName(string $name): string
    {
        $labels = explode('.', strtolower(rtrim(trim($name), '.')));
        foreach ($labels as &$label) {
            if ($label !== '' && preg_match('/[^\x20-\x7e]/', $label) && function_exists('idn_to_ascii')) {
                $ascii = idn_to_ascii($label, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
                if ($ascii !== false) {
                    $label = strtolower($ascii);
                }
            }
        }
        unset($label);
        return implode('.', $labels);
    }

    public static function filter(array $records, array $types): array
    {
        return array_values(array_filter($records, static fn(array $record): bool => in_array($record['type'], $types, true)));
    }

    /** Zones moved from cPanel have MX pointing to the zone root; they are out of scope. */
    public static function isLegacy(array $records, string $zone): bool
    {
        $zone = self::normalizeName($zone);
        foreach ($records as $record) {
            if ($record['type'] === 'MX' && self::normalizeName($record['content']) === $zone) {
                return true;
            }
        }
        return false;
    }

    /**
     * Records for the posts part of a Cloudflare batch.
     *
     * @param array $config params['dnsSync']: types, proxied, proxiedExclude, ttl
     */
    public static function toCloudflare(array $records, string $zone, array $config): array
    {
        $zone = self::normalizeName($zone);
        $records = self::filter($records, $config['types']);
        $mxTargets = [];
        foreach ($records as $record) {
            if ($record['type'] === 'MX') {
                $mxTargets[] = self::normalizeName($record['content']);
            }
        }
        $posts = [];
        foreach ($records as $record) {
            $type = $record['type'];
            $name = self::normalizeName($record['name']);
            if ($name !== $zone && !str_ends_with($name, '.' . $zone)) {
                throw new \RuntimeException("Запись $name вне зоны $zone");
            }
            $post = ['type' => $type, 'name' => $name, 'ttl' => (int)$config['ttl']];
            if ($type === 'MX' || $type === 'CNAME') {
                $post['content'] = self::normalizeName($record['content']);
            } elseif ($type === 'TXT') {
                $post['content'] = self::normalizeTxt($record['content']);
            } else {
                $post['content'] = trim($record['content']);
            }
            if ($type === 'MX') {
                $post['priority'] = (int)$record['priority'];
            }
            if (in_array($type, self::PROXIABLE, true)) {
                $proxied = !empty($config['proxied'][$type]);
                // Mail and FTP hosts cannot work through the proxy; CNAME is always proxied.
                if ($proxied && $type !== 'CNAME' && (in_array($name, $mxTargets, true)
                    || in_array(self::shortName($name, $zone), $config['proxiedExclude'], true))
                ) {
                    $proxied = false;
                }
                $post['proxied'] = $proxied;
            }
            $posts[] = $post;
        }
        return $posts;
    }

    /** Joins a quoted TXT representation ("a" "b") into one string; unquoted text is kept as is. */
    public static function normalizeTxt(string $content): string
    {
        $content = trim($content);
        $quoted = '"((?:[^"\\\\]|\\\\.)*)"';
        if (!preg_match('/^' . $quoted . '(?:\s+' . $quoted . ')*$/sD', $content)) {
            return $content;
        }
        preg_match_all('/' . $quoted . '/s', $content, $matches);
        return implode('', array_map(static fn(string $part): string => preg_replace('/\\\\(.)/s', '$1', $part), $matches[1]));
    }

    /** Name relative to the zone: '@' for the root. */
    public static function shortName(string $name, string $zone): string
    {
        $name = self::normalizeName($name);
        $zone = self::normalizeName($zone);
        if ($name === $zone) {
            return '@';
        }
        return str_ends_with($name, '.' . $zone) ? substr($name, 0, -strlen($zone) - 1) : $name;
    }

    /**
     * Lines like "A www → 1.2.3.4 [proxied]" for ISP, before and after columns.
     *
     * @return string[]
     */
    public static function formatForDisplay(array $records, string $zone): array
    {
        usort($records, static function (array $a, array $b) use ($zone): int {
            $order = static function (string $type): int {
                $index = array_search($type, self::DISPLAY_ORDER, true);
                return $index === false ? count(self::DISPLAY_ORDER) : $index;
            };
            return [$order($a['type']), $a['type'], self::shortName($a['name'], $zone), $a['priority'] ?? 0, $a['content']]
                <=> [$order($b['type']), $b['type'], self::shortName($b['name'], $zone), $b['priority'] ?? 0, $b['content']];
        });
        $lines = [];
        foreach ($records as $record) {
            $content = $record['type'] === 'TXT' ? self::normalizeTxt($record['content']) : rtrim($record['content'], '.');
            $line = $record['type'] . ' ' . self::shortName($record['name'], $zone) . ' → ' . $content;
            if (isset($record['priority']) && $record['type'] === 'MX') {
                $line .= ' (' . $record['priority'] . ')';
            }
            if (array_key_exists('proxied', $record) && in_array($record['type'], self::PROXIABLE, true)) {
                $line .= $record['proxied'] ? ' [proxied]' : ' [DNS only]';
            }
            $lines[] = $line;
        }
        return $lines;
    }
}
