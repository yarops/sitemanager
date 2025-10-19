<?php

/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 1:39
 */

/* @var $model common\models\Template */
?>
<div class="template-card">
    <?php if ($model->image_link) : ?>
        <a href="<?= $model->image_link ?>" class="template-card__image-link" target="_blank">
            <img src="<?= $model->image_link ?>" class="template-card__thumb img-fluid" alt="<?= $model->title ?>">
        </a>
    <?php endif; ?>
    <div class="template-card__content">
        <?= \yii\helpers\Html::a(
            $model->title,
            ['template/view', 'id' => $model->id],
            ['class' => 'template-card__title']
        ) ?>
        <div class="template-card__count"><?= $model->getItems()->query->count() ?> sites</div>
    </div>
</div>
