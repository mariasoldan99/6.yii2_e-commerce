<?php

namespace frontend\base;

use common\models\CartItem;

class Controller extends \yii\web\Controller
{
    public function beforeAction($action)
    {
        $this->view->params['cartItemCount'] = CartItem::getTotalQuantityForUser(\Yii::$app->user->id);
        return parent::beforeAction($action);
    }
}