<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var $this yii\web\View */
/** @var $servers \common\models\Server[] */
/** @var $server \common\models\Server|null */
/** @var $zones \common\models\DnsSyncZone[] */

$this->title = 'DNS синк';
$this->params['breadcrumbs'][] = $this->title;
$options = ArrayHelper::map($servers, 'id', static fn($s): string => $s->title . ' (' . $s->ip . ')');
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= Html::beginForm(['index'], 'get', ['class' => 'd-flex gap-2 align-items-end mb-3']) ?>
    <div>
        <label for="dns-server" class="form-label">Сервер сайтов / ISPmanager</label>
        <?= Html::dropDownList('server', $server ? $server->id : null, $options, [
            'id' => 'dns-server', 'class' => 'form-select', 'prompt' => 'Выберите сервер', 'required' => true,
        ]) ?>
    </div>
    <?= Html::submitButton('Открыть', ['class' => 'btn btn-primary']) ?>
<?= Html::endForm() ?>
<?php if ($server !== null): ?>
    <p class="text-muted">Текущие сайты сервера: опубликованные, черновики и демо, без архивных.</p>
    <?php if (!$zones): ?><p>На сервере нет сайтов для обработки.</p><?php endif; ?>
    <?= $this->render('_workspace', ['server' => $server, 'zones' => $zones]) ?>
<?php endif; ?>
