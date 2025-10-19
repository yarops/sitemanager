<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 14.12.14
 * Time: 0:20
 */

namespace frontend\models;

use common\models\Item;
use yii\base\Model;

class ItemForm extends Model
{
    /**
     * @var null|string action формы
     */
    public $action = null;
    /**
     * @var int|null идентификатор родительского комментария
     */
    public $pid = null;
    /**
     * @var string заголовок комментария
     */
    public $title;
    /**
     * @var string контент комментария
     */
    public $content;

    public function __construct($action = null)
    {
        $this->action = $action;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title', 'content'], 'required'],
            [['title', 'content'], 'string', 'max' => 255]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Заголовок',
            'content' => 'Комментарий'
        ];
    }

    public function save(Item $item, array $data): bool
    {
        $isLoad = $item->load([
            'title' => $data['title'],
            'content' => $data['content']
        ], '');

        return ($isLoad && $item->save());
    }
}
