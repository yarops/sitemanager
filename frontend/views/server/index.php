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

<div class="col-md-12 item-index mx-auto">

    <h1><?= Html::encode($this->title) ?></h1>

    <table class="table table-striped table-bordered detail-view">
        <tr>
            <td>Server</td>
            <td>Action</td>
        </tr>
    <?php
    foreach ($servers->models as $server) {
        echo '<tr>';
        $includeDrafts = !Yii::$app->user->isGuest;
        echo '<td>' . $server->title . ' <b>' . $server->ip . '</b> [' . $server->getItems($includeDrafts)->query->count() . ']</td>';
        echo '<td>';
        echo \yii\helpers\Html::a('View', ['server/view', 'id' => $server->id], ['class' => 'btn btn-secondary btn-sm']) . '&nbsp;';
        echo \yii\helpers\Html::a('Checks', ['server-check/server', 'id' => $server->id], ['class' => 'btn btn-secondary btn-sm']) . '&nbsp;';
        echo \yii\helpers\Html::a('Check diff', ['server/check-diff', 'id' => $server->id], ['class' => 'btn btn-success btn-sm']) . '&nbsp;';
        echo \yii\helpers\Html::a('Check online', ['server/check-online', 'id' => $server->id], [
            'class' => 'btn btn-success btn-sm',
            'data-method' => 'post',
            'data-confirm' => 'Run manual online check for this server?',
        ]);
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
