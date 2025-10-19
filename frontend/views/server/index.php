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
/** @var $server \common\models\Server текущая категория */
/** @var $servers \yii\data\ActiveDataProvider список категорий */
/** @var $items \yii\data\ActiveDataProvider список категорий */

$this->title                   = Yii::t('frontend', 'Servers');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <table class="table table-striped table-bordered detail-view">
        <tr>
            <td>Server</td>
            <td>Action</td>
        </tr>
    <?php
    foreach ($servers->models as $server) {
        echo '<tr>';
        echo '<td>' . $server->title . ' <b>' . $server->ip . '</b> [' . $server->getItems()->query->count() . ']</td>';
        echo '<td>';
        echo \yii\helpers\Html::a('View', ['server/view', 'id' => $server->id], ['class' => 'btn btn-default btn-xs']) . '&nbsp;';
        echo \yii\helpers\Html::a('Check diff', ['server/check-diff', 'id' => $server->id], ['class' => 'btn btn-success btn-xs']) . '&nbsp;';
        echo \yii\helpers\Html::a('Check online', ['server/check-online', 'id' => $server->id], ['class' => 'btn btn-success btn-xs']);
        echo '</td>';
        echo '</tr>';
    }
    ?>
    </table>

    <style>
        table td:first-child {
            width: 100%;
        }
        table td:last-child {
            white-space: nowrap;
        }
    </style>

    <div>
        <?= LinkPager::widget([
            'pagination' => $servers->getPagination()
        ]) ?>
    </div>

</div>

<div class="col-sm-3 col-sm-offset-1 blog-sidebar">
    <h1><?php echo Yii::t('frontend', 'Servers'); ?></h1>
    <ul>
        <?php
        foreach ($servers->models as $server) {
            echo $this->render(
                'shortViewServer',
                array(
                    'model' => $server,
                )
            );
        }
        ?>
    </ul>
</div>
