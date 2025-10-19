<?php
namespace common\components\check;

class Check extends \yii\base\Component{
    public function init() {
        require_once __DIR__ . "/WorkerCheck.php";
        parent::init();
    }
}
