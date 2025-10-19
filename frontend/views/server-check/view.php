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
    asort($report);

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
                    $classes = 'success';
                    break;
                case 0:
                    $classes = 'danger';
                    break;
                default:
                    $classes = 'warning';
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
