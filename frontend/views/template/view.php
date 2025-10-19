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
/** @var $template \common\models\Template текущая категория */
/** @var $templates \yii\data\ActiveDataProvider список категорий */
/** @var $items \yii\data\ActiveDataProvider список категорий */

$this->title = Yii::t('frontend', 'Template: ' . $template->title);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-sm-8 item-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php
    foreach ($items->models as $item) {
        echo $this->render('//item/shortView', [
            'model' => $item
        ]);
    }
    ?>
    <div>
        <?= LinkPager::widget([
            'pagination' => $items->getPagination()
        ]) ?>
    </div>

</div>
