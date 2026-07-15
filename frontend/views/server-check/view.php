<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:14
 */
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var $this yii\web\View */
/** @var $serverCheck \common\models\Template текущая категория */
/** @var $templates \yii\data\ActiveDataProvider список категорий */
/** @var $items \yii\data\ActiveDataProvider список категорий */
/** @var $itemsByUrl \common\models\Item[] */

$this->title = Yii::t('frontend', 'Server check: ' . $model->title);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-12 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    $report = json_decode($model->report, true);
    if (!is_array($report)) {
        $report = [];
    }

    // Сортируем так, чтобы полностью успешные проверки были в конце.
    uasort($report, function ($a, $b) {
        $aStatus = is_array($a) ? ($a['status'] ?? 0) : $a;
        $bStatus = is_array($b) ? ($b['status'] ?? 0) : $b;
        $aAliasStatus = is_array($a) ? ($a['alias_status'] ?? null) : null;
        $bAliasStatus = is_array($b) ? ($b['alias_status'] ?? null) : null;
        $aIsOk = (int)$aStatus === 200 && ($aAliasStatus === null || (int)$aAliasStatus === 200);
        $bIsOk = (int)$bStatus === 200 && ($bAliasStatus === null || (int)$bAliasStatus === 200);

        return $aIsOk === $bIsOk ? (int)$aStatus <=> (int)$bStatus : ($aIsOk ? 1 : -1);
    });

    ?>
    <div>
    <table class="table table-bordered detail-view">
        <tr>
            <th>Host</th>
            <th>Http response</th>
            <th>Http response alias</th>
            <th>Дата публикации</th>
            <th>Статус</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($report as $key => $result):
            $value = is_array($result) ? ($result['status'] ?? 0) : $result;
            $aliasStatus = is_array($result) ? ($result['alias_status'] ?? null) : null;
            $hasAliasError = $aliasStatus !== null && (int)$aliasStatus !== 200;
            switch ((int)$value) {
                case 200:
                    $classes = $hasAliasError ? 'table-warning' : 'table-success';
                    break;
                case 0:
                    $classes = 'table-danger';
                    break;
                default:
                    $classes = 'table-warning';
            }

            ?>
            <?php
            $scheme = parse_url($key, PHP_URL_SCHEME);
            $host = parse_url($key, PHP_URL_HOST);
            $itemKey = $scheme && $host ? $scheme . '://' . $host : $key;
            $item = $itemsByUrl[$itemKey] ?? null;
            $domain = $host ?: preg_replace('#^https?://#', '', $key);
            ?>
            <tr class="<?php echo $classes; ?>">
                <td>
                    <a href="<?php echo $key; ?>" target="_blank"><?php echo $key; ?></a>
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm copy-domain-btn"
                        data-domain="<?= Html::encode($domain) ?>"
                    >
                        Copy domain
                    </button>
                </td>
                <td><?php echo $value; ?></td>
                <td><?= $aliasStatus === null ? '—' : Html::encode($aliasStatus) ?></td>
                <td><?= $item ? Html::encode($item->publish_date ?: '—') : '—' ?></td>
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
                    <?= Html::a('Перепроверить', ['server-check/recheck-site', 'id' => $model->id, 'url' => $key], [
                        'class' => 'btn btn-primary btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Перепроверить доступность сайта?',
                    ]) ?>
                    <?= Html::a('Убрать из отчёта', ['server-check/remove-site-from-report', 'id' => $model->id, 'url' => $key], [
                        'class' => 'btn btn-outline-danger btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Убрать сайт только из этого отчёта? Сам сайт и его мониторинг не изменятся.',
                    ]) ?>
                    <?php if ($item && !$item->isArchived()): ?>
                        <?= Html::a('Архивировать сайт', ['server-check/archive-item', 'id' => $item->id], [
                            'class' => 'btn btn-warning btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Архивировать сайт и отключить мониторинг?',
                        ]) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
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
