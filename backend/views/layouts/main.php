<?php
use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\widgets\Breadcrumbs;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <div class="wrap">
        <?php
            NavBar::begin([
                'brandLabel' => 'Go to front',
                'brandUrl' => 'https://panel.devgamescom.ru',
                'brandOptions' => [
                    'target' => '_blank',
                ],
                'options' => [
                    'class' => 'navbar-expand-lg navbar-dark bg-dark fixed-top',
                ],
            ]);

            if (Yii::$app->user->isGuest) {
                $menuItems[] = ['label' => Yii::t('backend', 'Login'), 'url' => ['/site/login']];
            } else {
                $menuItems[] = ['label' => Yii::t('backend', 'Items'), 'url' => ['item/index']];
                $menuItems[] = ['label' => Yii::t('backend', 'Servers'), 'url' => ['server/index']];
                $menuItems[] = ['label' => Yii::t('backend', 'Templates'), 'url' => ['template/index']];
                $menuItems[] = ['label' => Yii::t('backend', 'Users'), 'url' => ['user/index']];
                $menuItems[] = [
                    'label' => Yii::t('backend', 'Logout ({username})', [
                        'username' => Yii::$app->user->identity->username
                    ]),
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ];
            }
            $menuItems[] = ['label' => Yii::t('backend', 'Site'), 'url' => 'https://devel.codesweet.ru'];
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav ms-auto'],
                'items' => $menuItems,
            ]);
            NavBar::end();
            ?>

        <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="float-start">&copy; <a href="https://codesweet.ru" target="_blank">codesweet.ru</a> <?= date('Y') ?></p>
            <p class="float-end"><?= Yii::powered() ?></p>
        </div>
    </footer>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
