<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $servers \common\models\Server[] */
/** @var $serverId int|null */

$this->title = 'Новый DNS синк';
$this->params['breadcrumbs'][] = ['label' => 'DNS синк', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$options = ArrayHelper::map($servers, 'id', static fn($server): string => $server->title . ' (' . $server->ip . ')');
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= Html::beginForm(['create'], 'post', ['class' => 'col-12 col-lg-6']) ?>
    <div class="mb-3">
        <label class="form-label" for="source-server">Сервер сайтов</label>
        <?= Html::dropDownList('source_server_id', $serverId, $options, [
            'id' => 'source-server', 'class' => 'form-select', 'prompt' => '—', 'required' => true,
        ]) ?>
        <div class="form-text">Зоны берутся из сайтов сервера: опубликованные, черновики и демо, без архивных.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="isp-server">ISPmanager</label>
        <?= Html::dropDownList('isp_server_id', null, $options, [
            'id' => 'isp-server', 'class' => 'form-select', 'prompt' => 'Тот же сервер',
        ]) ?>
        <div class="form-text">Сервер, из ISPmanager которого читаются записи зон.</div>
    </div>
    <?= Html::submitButton('Создать', ['class' => 'btn btn-primary']) ?>
<?= Html::endForm() ?>
