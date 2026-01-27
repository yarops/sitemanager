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

$this->title = Yii::t('frontend', 'Server check: ' . $model->title);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    $report = json_decode($model->report, true);
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
            <tr class="<?php echo $classes; ?>">
                <td>
                    <a href="<?php echo $key; ?>" target="_blank"><?php echo $key; ?></a>
                </td>
                <td><?php echo $value; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>

</div>
