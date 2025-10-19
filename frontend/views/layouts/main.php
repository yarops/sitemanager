<?php
use yii\helpers\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use frontend\widgets\Alert;

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
                'brandLabel' => 'Front',
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar-expand-lg navbar-dark bg-dark fixed-top',
                ],
            ]);
            $menuItems = [
                ['label' => Yii::t('frontend', 'Items'), 'url' => ['/item/index']],
                ['label' => Yii::t('frontend', 'Servers'), 'url' => ['/server/index']],
                ['label' => Yii::t('frontend', 'Templates'), 'url' => ['/template/index']],
                ['label' => 'Мониторинг', 'url' => ['/check/dashboard']],
                //['label' => Yii::t('frontend', 'About'), 'url' => ['/site/about']],
                //['label' => Yii::t('frontend', 'Contact'), 'url' => ['/site/contact']],
            ];
            if (Yii::$app->user->isGuest) {
                $menuItems[] = ['label' => Yii::t('frontend', 'Login'), 'url' => ['/site/login']];
            } else {
                $menuItems[] = [
                    'label' => Yii::t('frontend', 'Logout ({username})', ['username' => Yii::$app->user->identity->username]),
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ];
                $menuItems[] = [
                    'label' => Yii::t('frontend', 'Administration'),
                    'url' => 'https://backend.devel.codesweet.ru'
                ];
            }
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav ms-auto'],
                'items' => $menuItems,
            ]);

            echo $this->render('_formSearch');
            NavBar::end();
            ?>

        <div class="container">
        <?= Breadcrumbs::widget([
    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="float-start">&copy; <a href="https://codesweet.ru" target="_blank">codesweet.ru</a> <?= date('Y') ?></p>
            <p class="float-end"><?= Yii::powered() ?></p>
        </div>
    </footer>

    <div class="svg-preload">
        <?php include(Yii::getAlias('@frontend/web/images/icons.svg')); ?>
    </div>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
