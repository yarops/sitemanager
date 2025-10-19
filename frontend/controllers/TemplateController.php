<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:03
 */

namespace frontend\controllers;

use common\models\Template;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class TemplateController extends Controller
{
    public function actionIndex(): string
    {
        //$items = Item::findByCurrentUser();
        $query = Template::find();
        $items = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'items' => $items,
        ]);
    }

    public function actionView(int $id): string
    {
        $template = Template::findById($id);

        $items = $template->getItems();
        $items->setPagination([
            'pageSize' => Yii::$app->params['pageSize']
        ]);

        return $this->render('view', [
            'template' => $template,
            'items' => $items,
            'templates' => Template::findTemplates()
        ]);
    }
}
