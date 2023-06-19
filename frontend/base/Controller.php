<?php

namespace frontend\base;

use common\models\CartItem;

class Controller extends \yii\web\Controller
{
    public function beforeAction($action)
    {
        $itemCount = CartItem::findBySql("SELECT SUM(quantity) FROM cart_items WHERE created_by = :user_id", ['user_id' => \Yii::$app->user->id])
            ->scalar();
        $this->view->params['cartItemCount'] = $itemCount;
        return parent::beforeAction($action);
    }
}