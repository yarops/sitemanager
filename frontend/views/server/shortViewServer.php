<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 1:39
 */

/* @var $model common\models\Server */
?>
<li class="list-group-item">
<?php
echo \yii\helpers\Html::a(
    $model->title . ' [' . $model->ip . ']',
    [
        'server/view',
        'id' => $model->id,
    ]
)
?>
</li>
