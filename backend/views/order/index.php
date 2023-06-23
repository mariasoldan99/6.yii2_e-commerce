<?php

use common\models\Order;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var backend\models\search\OrderSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Orders';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-index">

    <h1><?= Html::encode($this->title) ?></h1>


    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'id' => 'ordersTable',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pager' => [
            'class' => \yii\bootstrap4\LinkPager::class
        ],
        'columns' => [
            [
                'attribute' => 'id',
                'contentOptions' => ['style' => 'width:80px']
            ],

            [
                'attribute' => 'fullname',
                'content' => function ($model) {
                    return $model->firstname . ' ' . $model->lastname;
                }
            ],
            'total_price:currency',
            [
                'attribute' => 'status',
                'filter' => \yii\bootstrap4\Html::activeDropDownList($searchModel, 'status',  Order::getStatusLabel(), ['class' => 'form-control', 'prompt' => 'All']),
                'content' => function ($model) {
                    if ($model->status === Order::STATUS_COMPLETED) {
                        return Html::tag('span', 'Completed', ['class' => 'badge badge-success']);
                    } else if ($model->status === Order::STATUS_DRAFT) {
                        return Html::tag('span', 'Draft', ['class' => 'badge badge-secondary']);
                    } else if ($model->status === Order::STATUS_FAILURED) {
                        return Html::tag('span', 'Failed', ['class' => 'badge badge-danger']);
                    } else {
                        return Html::tag('span', 'Paid', ['class' => 'badge badge-primary']);
                    }
                }
            ],

            //'email:email',
            //'transaction_id',
            //'paypal_order_id',
            'created_at:datetime',
            //'created_by',
            [
                'class' => ActionColumn::className(),
                'template' => '{view} {delete} {update}',
                'urlCreator' => function ($action, Order $model) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                }
            ],
        ],
    ]); ?>


</div>
