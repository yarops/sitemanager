<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 1:39
 */

/* @var $model common\models\Server */
?>
<li>
<?php
echo \yii\helpers\Html::a(
	$model->title,
	array(
		'server/view',
		'id' => $model->id,
	)
)
?>
</li>
