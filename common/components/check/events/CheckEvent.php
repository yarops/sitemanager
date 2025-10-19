<?php
namespace backend\components\check\events;

use yii\base\Event;

/**
 * Class CheckEvent
 * @package common\components\check
 * @author Yaroslav Popov <ed.creater@gmail.com>
 */
class CheckEvent extends Event
{

    /**
     * @var int
     */
    public $itemId;
}
