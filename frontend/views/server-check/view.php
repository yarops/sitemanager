<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:14
 */
use yii\helpers\Html;
use common\components\cloudflare\ReportGroups;

/** @var $this yii\web\View */
/** @var $serverCheck \common\models\Template текущая категория */
/** @var $templates \yii\data\ActiveDataProvider список категорий */
/** @var $items \yii\data\ActiveDataProvider список категорий */
/** @var $itemsByUrl \common\models\Item[] */

$this->title = Yii::t('frontend', 'Server check: ' . $model->title);
$this->params['breadcrumbs'][] = $this->title;
$heading = $this->title;
$reportDate = null;
if (preg_match('/^(.*?)(\d{1,2} [A-Za-z]+ \d{4})$/', $model->title, $matches)) {
    $heading = Yii::t('frontend', 'Server check: ' . trim($matches[1]));
    $reportDate = $matches[2];
}
?>

<div class="item-index">

    <h1 class="d-flex flex-wrap align-items-baseline gap-3">
        <span><?= Html::encode($heading) ?></span>
        <?php if ($reportDate !== null): ?>
            <span class="fs-6 fw-normal text-secondary"><?= Html::encode($reportDate) ?></span>
        <?php endif; ?>
    </h1>

    <?php
    $report = json_decode($model->report, true);
    if (!is_array($report)) {
        $report = [];
    }

    $missingOnly = Yii::$app->request->get('cloudflare') === 'missing';
    $groups = ReportGroups::partition($report, $missingOnly);

    ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?= Html::a('Все', ['view', 'id' => $model->id], [
            'class' => 'btn btn-sm ' . (!$missingOnly ? 'btn-primary' : 'btn-outline-primary'),
        ]) ?>
        <?= Html::a('Нет в наших аккаунтах Cloudflare', ['view', 'id' => $model->id, 'cloudflare' => 'missing'], [
            'class' => 'btn btn-sm ' . ($missingOnly ? 'btn-primary' : 'btn-outline-primary'),
        ]) ?>
    </div>
    <div class="table-responsive">
    <table class="table table-bordered align-top">
        <thead>
        <tr>
            <th>Host</th>
            <th>Http response</th>
            <th>Http response alias</th>
            <th>Наш Cloudflare</th>
            <th>Трафик пользователя, MiB</th>
            <th>Дата публикации</th>
            <th>Статус публикации</th>
            <th>Статус архивации</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($groups as $group => $rows): ?>
        <?php if (!$rows) { continue; } ?>
        <tr class="table-secondary">
            <th colspan="9"><?= Html::encode(ReportGroups::LABELS[$group]) ?> (<?= count($rows) ?>)</th>
        </tr>
        <?php foreach ($rows as $key => $result):
            $value = is_array($result) ? ($result['status'] ?? 0) : $result;
            $aliasStatus = is_array($result) ? ($result['alias_status'] ?? null) : null;
            $classes = ReportGroups::rowClass($result);
            $cf = is_array($result) ? ($result['cloudflare'] ?? []) : [];
            $traffic = is_array($result) ? ($result['isp_traffic'] ?? []) : [];

            ?>
            <?php
            $scheme = parse_url($key, PHP_URL_SCHEME);
            $host = parse_url($key, PHP_URL_HOST);
            $itemKey = $scheme && $host ? $scheme . '://' . $host : $key;
            $item = $itemsByUrl[$itemKey] ?? null;
            $domain = $host ?: preg_replace('#^https?://#', '', $key);
            $aliasUrl = $item && !empty($item->alias) && $item->alias !== $item->domain
                ? $item->protocol . '://' . $item->alias
                : (is_array($result) ? ($result['alias_url'] ?? null) : null);
            $rowId = 'site-row-' . substr(hash('sha256', $key), 0, 16);
            ?>
            <tr id="<?= Html::encode($rowId) ?>" class="<?php echo $classes; ?>">
                <td>
                    <a href="<?php echo $key; ?>" target="_blank"><?php echo $key; ?></a>
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm copy-domain-btn"
                        data-domain="<?= Html::encode($domain) ?>"
                    >
                        Copy domain
                    </button>
                    <?php if ($aliasUrl): ?>
                        <div class="mt-1">
                            <span class="text-muted">Алиас:</span>
                            <?= Html::a($aliasUrl, $aliasUrl, ['target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($item && !empty($item->childs)): ?>
                        <div class="mt-1">
                            <span class="text-muted">Поддомены:</span>
                            <?php foreach ($item->childs as $child): ?>
                                <div><?= Html::encode($child->domain) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><?php echo $value; ?></td>
                <td><?= $aliasStatus === null ? '—' : Html::encode($aliasStatus) ?></td>
                <td>
                    <?php if (!empty($cf['zones'])): ?>
                        <?php foreach ($cf['zones'] as $zone): ?>
                            <div><?= Html::encode($zone['account_label'] . ' · ' . $zone['name'] . ' · ' . $zone['status']) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div><?= ($cf['state'] ?? null) === 'missing' && empty($cf['stale']) ? 'Не найден' : 'Не проверено' ?></div>
                    <?php endif; ?>
                    <?php if (!empty($cf['stale'])): ?>
                        <div class="text-muted">Данные устарели / проверка неполная</div>
                    <?php endif; ?>
                    <?php foreach ($cf['accounts'] ?? [] as $account): ?>
                        <div class="small text-muted">
                            <?= Html::encode($account['account_label']) ?>:
                            <?= Html::encode($account['checked_at'] ?? 'нет успешной синхронизации') ?>
                            <?= !empty($account['stale']) ? ' (устарело)' : '' ?>
                        </div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <div><?= ($traffic['state'] ?? null) === 'available' && isset($traffic['used_mib'])
                        ? Html::encode(Yii::$app->formatter->asDecimal($traffic['used_mib'], 0)) : '—' ?></div>
                    <?php if (!empty($traffic['login'])): ?>
                        <div class="small text-muted"><?= Html::encode($traffic['login']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($traffic['period_start']) && !empty($traffic['period_end'])): ?>
                        <div class="small text-muted"><?= Html::encode($traffic['period_start'] . ' — ' . $traffic['period_end']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($traffic['checked_at'])): ?>
                        <div class="small text-muted">Получено: <?= Html::encode($traffic['checked_at']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= $item ? Html::encode($item->publish_date ?: '—') : '—' ?></td>
                <td>
                    <?php if ($item && $item->publish_status === \common\models\Item::STATUS_PUBLISH): ?>
                        <span class="badge bg-success">Опубликован</span>
                    <?php elseif ($item && $item->publish_status === \common\models\Item::STATUS_DRAFT): ?>
                        <span class="badge bg-secondary">Черновик</span>
                    <?php elseif ($item && $item->isDemo()): ?>
                        <span class="badge bg-info">Демо</span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($item && $item->isArchived()): ?>
                        <span class="text-muted">Сайт в архиве</span>
                    <?php elseif (!$item): ?>
                        <span class="text-muted">Сайт не найден</span>
                    <?php else: ?>
                        <span>Активен</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$item || ($item->publish_status !== \common\models\Item::STATUS_DRAFT && !$item->isDemo())): ?>
                    <?= Html::a('Перепроверить', ['server-check/recheck-site', 'id' => $model->id, 'url' => $key, 'row' => $rowId], [
                        'class' => 'btn btn-primary btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Обновить доступность, Cloudflare и трафик пользователя сайта?',
                    ]) ?>
                    <?php endif; ?>
                    <?= Html::a('Убрать из отчёта', ['server-check/remove-site-from-report', 'id' => $model->id, 'url' => $key], [
                        'class' => 'btn btn-outline-danger btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Убрать сайт только из этого отчёта? Сам сайт и его мониторинг не изменятся.',
                    ]) ?>
                    <?php if ($item && !$item->isArchived()): ?>
                        <?= Html::a('Архивировать сайт', [
                            'server-check/archive-item',
                            'id' => $item->id,
                            'reportId' => $model->id,
                            'row' => $rowId,
                        ], [
                            'class' => 'btn btn-warning btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Архивировать сайт и отключить мониторинг?',
                        ]) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!array_filter($groups)): ?>
            <tr><td colspan="9">Нет сайтов для отображения.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

</div>

<?php
$this->registerJs(<<<'JS'
document.querySelectorAll('.copy-domain-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        var domain = button.getAttribute('data-domain');
        if (!domain) {
            return;
        }

        navigator.clipboard.writeText(domain).then(function () {
            var originalText = button.textContent;
            button.textContent = 'Copied';
            setTimeout(function () {
                button.textContent = originalText;
            }, 1200);
        });
    });
});
JS);
?>
