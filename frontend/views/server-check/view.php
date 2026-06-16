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

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    $report = json_decode($model->report, true);
    if (!is_array($report)) {
        $report = [];
    }

    // Сортируем так, чтобы ответы 200 были в конце, остальные - вверху
    uasort($report, function ($a, $b) {
        if ($a == 200 && $b != 200) {
            return 1; // a=200, b!=200 - a должен идти после b
        }
        if ($a != 200 && $b == 200) {
            return -1; // a!=200, b=200 - a должен идти перед b
        }
        // Если оба 200 или оба не 200 - сортируем по значению
        return $a <=> $b;
    });

    ?>
    <div>
    <table class="table table-bordered detail-view">
        <tr>
            <th>Host</th>
            <th>Http response</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($report as $key => $value):
            switch ((int)$value) {
                case 200:
                    $classes = 'table-success';
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
                <td>
                    <?php if ($item && !$item->isArchived()): ?>
                        <?= Html::a('Архивировать сайт', ['server-check/archive-item', 'id' => $item->id], [
                            'class' => 'btn btn-warning btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Архивировать сайт и отключить мониторинг?',
                        ]) ?>
                    <?php elseif ($item && $item->isArchived()): ?>
                        <span class="text-muted">Сайт в архиве</span>
                    <?php else: ?>
                        <span class="text-muted">Сайт не найден</span>
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
