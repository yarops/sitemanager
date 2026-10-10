<?php

namespace common\components\cloudflare;

class ReportGroups
{
    public const LABELS = [
        'http' => 'Проблемы HTTP',
        'missing' => 'Нет в наших аккаунтах Cloudflare',
        'attention' => 'Cloudflare требует внимания / не проверен',
        'success' => 'Успешные',
    ];

    public static function group($result): string
    {
        $status = is_array($result) ? ($result['status'] ?? 0) : $result;
        if ((int)$status !== 200) {
            return 'http';
        }
        $cf = is_array($result) ? ($result['cloudflare'] ?? []) : [];
        if (!empty($cf['stale'])) {
            return 'attention';
        }
        if (($cf['state'] ?? null) === 'missing') {
            return 'missing';
        }
        if (($cf['state'] ?? null) === 'found' && !empty($cf['zones'])) {
            foreach ($cf['zones'] as $zone) {
                if (($zone['status'] ?? null) !== 'active') {
                    return 'attention';
                }
            }
            return 'success';
        }
        return 'attention';
    }

    public static function rowClass($result): string
    {
        $group = self::group($result);
        if ($group === 'http') {
            $status = is_array($result) ? ($result['status'] ?? 0) : $result;
            return (int)$status === 0 ? 'table-danger' : 'table-warning';
        }
        if ($group === 'missing') {
            return 'table-info';
        }
        if ($group === 'success') {
            return 'table-success';
        }
        $cf = is_array($result) ? ($result['cloudflare'] ?? []) : [];
        return empty($cf['stale']) && !empty($cf['zones']) ? 'table-warning' : '';
    }

    public static function partition(array $report, bool $missingOnly = false): array
    {
        $groups = array_fill_keys(array_keys(self::LABELS), []);
        foreach ($report as $url => $result) {
            $cf = is_array($result) ? ($result['cloudflare'] ?? []) : [];
            if ($missingOnly && (($cf['state'] ?? null) !== 'missing' || !empty($cf['stale']))) {
                continue;
            }
            $groups[self::group($result)][$url] = $result;
        }
        foreach ($groups as $name => &$rows) {
            uksort($rows, static function ($a, $b) use (&$rows, $name) {
                if ($name === 'http') {
                    $aResult = $rows[$a];
                    $bResult = $rows[$b];
                    $comparison = (int)(is_array($aResult) ? ($aResult['status'] ?? 0) : $aResult)
                        <=> (int)(is_array($bResult) ? ($bResult['status'] ?? 0) : $bResult);
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }
                return strcmp(ZoneMonitor::normalizeHost($a), ZoneMonitor::normalizeHost($b)) ?: strcmp($a, $b);
            });
        }
        unset($rows);
        return $groups;
    }
}
