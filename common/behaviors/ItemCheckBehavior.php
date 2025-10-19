<?php

namespace common\behaviors;

use common\models\Item;
use frontend\components\check\WorkerCheck;
use yii\base\Behavior;

class CheckBehavior extends Behavior
{
    public $component;

    public function events()
    {
        return [
            WorkerCheck::EVENT_AFTER_SAVE => 'afterSave',
        ];
    }

    /**
     * @param $event frontend\components\check\events\CheckItemEvent
     */
    public function afterSave($event)
    {


        $file = new File($event->filesystem, $event->path);
        $model = new FileStorageItem();
        $model->component = $this->component;
        $model->path = $file->getPath();
        $model->base_url = $this->getStorage()->baseUrl;
        $model->size = $file->getSize();
        $model->type = $file->getMimeType();
        $model->name = pathinfo($file->getPath(), PATHINFO_FILENAME);
        if (Yii::$app->request->getIsConsoleRequest() === false) {
            $model->upload_ip = Yii::$app->request->getUserIP();
        }
        $model->save(false);
    }

}
