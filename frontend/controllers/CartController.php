<?php

namespace frontend\controllers;

use common\models\CartItem;
use common\models\Order;
use common\models\OrderAddress;
use common\models\Product;
use common\models\User;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\AuthorizationsCaptureRequest;
use Yii;
use yii\filters\ContentNegotiator;
use \frontend\base\Controller;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CartController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['add', 'create-order', 'submit-payment'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON
                ]
            ],
            [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST', 'DELETE'],
                    'create-order' => ['POST']
                ]
            ]
        ];
    }

    public function actionIndex()
    {
        $cartItems = CartItem::getItemsForUser(\Yii::$app->user->id);
        return $this->render('index', [
            'items' => $cartItems,
            'totalPrice' => CartItem::getTotalPriceForUser(\Yii::$app->user->id),
        ]);
    }

    public function actionAdd()
    {
        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }

        if (\Yii::$app->user->isGuest) {

            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            $found = false;
            foreach ($cartItems as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $cartItem = [
                    'id' => $id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => $product->price,
                    'quantity' => 1,
                    'total_price' => $product->price
                ];
                $cartItems[] = $cartItem;
            }

            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $userId = \Yii::$app->user->id;
            $cartItem = CartItem::find()->userId($userId)->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity++;
            } else {
                $cartItem = new CartItem();
                $cartItem->product_id = $id;
                $cartItem->created_by = $userId;
                $cartItem->quantity = 1;
            }
            if ($cartItem->save()) {
                return [
                    'success' => true
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $cartItem->errors
                ];
            }
        }
    }

    public function actionDelete($id)
    {
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as $i => $cartItem) {
                if ($cartItem['id'] == $id) {
                    array_splice($cartItems, $i, 1);
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            CartItem::deleteAll(['product_id' => $id, 'created_by' => \Yii::$app->user->id]);
        }

        return $this->redirect(['index']);
    }

    public function actionChangeQuantity()
    {
        $id = \Yii::$app->request->post('id');
        $product = Product::find()->id($id)->published()->one();
        if (!$product) {
            throw new NotFoundHttpException("Product does not exist");
        }

        $quantity = \Yii::$app->request->post('quantity');
        if (\Yii::$app->user->isGuest) {
            $cartItems = \Yii::$app->session->get(CartItem::SESSION_KEY, []);
            foreach ($cartItems as &$cartItem) {
                if ($cartItem['id'] === $id) {
                    $cartItem['quantity'] = $quantity;
                    break;
                }
            }
            \Yii::$app->session->set(CartItem::SESSION_KEY, $cartItems);
        } else {
            $cartItem = CartItem::find()->userId(\Yii::$app->user->id)->productId($id)->one();
            if ($cartItem) {
                $cartItem->quantity = $quantity;
                $cartItem->save();
            }
        }
        return CartItem::getTotalQuantityForUser(\Yii::$app->user->id);
    }

    public function actionCheckout()
    {
        $cartItems = CartItem::getItemsForUser(\Yii::$app->user->id);
        $productQuantity = CartItem::getTotalQuantityForUser(\Yii::$app->user->id);
        $totalPrice = CartItem::getTotalPriceForUser(\Yii::$app->user->id);

        if (empty($cartItems)) {
            return $this->redirect(['site/index']);
        }

        $order = new Order();

        $order->total_price = $totalPrice;
        $order->status = Order::STATUS_DRAFT;
        $order->created_at = time();
        $order->created_by = \Yii::$app->user->id;
        $transaction = \Yii::$app->db->beginTransaction();
        if ($order->load(\Yii::$app->request->post())
            && $order->save()
            && $order->saveAddress(\Yii::$app->request->post())
            && $order->saveOrderItems()) {
            $transaction->commit();

            CartItem::clearCartItems(\Yii::$app->user->id);

            return $this->render('pay-now', [
                'order' => $order
            ]);
        }
        $orderAddress = new OrderAddress();

        if (!(\Yii::$app->user->isGuest)) {
            /** @var User $user */
            $user = \Yii::$app->user->identity;
            $userAddress = $user->getAddress();

            $order->firstname = $user->firstname;
            $order->lastname = $user->lastname;
            $order->email = $user->email;
            $order->status = Order::STATUS_DRAFT;

            $orderAddress = new OrderAddress();
            $orderAddress->address = $userAddress->address;
            $orderAddress->city = $userAddress->city;
            $orderAddress->state = $userAddress->state;
            $orderAddress->country = $userAddress->country;
            $orderAddress->zipcode = $userAddress->zipcode;
        }


        return $this->render('checkout', [
            'order' => $order,
            'orderAddress' => $orderAddress,
            'cartItems' => $cartItems,
            'productQuantity' => $productQuantity,
            'totalPrice' => $totalPrice
        ]);
    }


    public
    function actionSubmitPayment($orderId)
    {
        $where = ['id' => $orderId, 'status' => Order::STATUS_DRAFT];
        if (!\Yii::$app->user->isGuest) {
            $where['created_by'] = \Yii::$app->user->id;
        }
        $order = Order::findOne($where);
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $req = Yii::$app->request;
        $paypalOrderId = $req->post('orderId');
        $exists = Order::find()->andWhere(['paypal_order_id' => $paypalOrderId])->exists();
        if ($exists) {
            throw new BadRequestHttpException();
        }

        $environment = new SandboxEnvironment(Yii::$app->params['paypalClientId'], Yii::$app->params['paypalSecret']);
        $client = new PayPalHttpClient($environment);

        $response = $client->execute(new OrdersGetRequest($paypalOrderId));

        if ($response->statusCode === 200) {
            $order->paypal_order_id = $paypalOrderId;

            $paidAmount = 0;
            foreach ($response->result->purchase_units as $purchase_unit) {
                if ($purchase_unit->amount->currency_code === 'USD') {
                    $paidAmount += $purchase_unit->amount->value;
                }
            }
            if ($paidAmount === (float)$order->total_price && $response->result->status === 'COMPLETED') {
                $order->status = Order::STATUS_PAID;
            }
            $order->transaction_id = $response->result->purchase_units[0]->payments->captures[0]->id;
            if  ($order->save()) {
                if (!$order->sendEmailToVendor()) {
                    Yii::error("Email to the vendor is not sent");
                }
                if (!$order->sendEmailToCustomer()) {
                    Yii::error("Email to the customer is not sent");
                }

                return [
                    'success' => true
                ];
            } else {
                Yii::error("Order was not saved. Data: ".VarDumper::dumpAsString($order->toArray()).
                    '. Errors: '.VarDumper::dumpAsString($order->errors));
            }
        }

        throw new BadRequestHttpException();



//        $totalPrice = CartItem::getTotalPriceForUser(\Yii::$app->user->id);
//        if ($totalPrice === null) {
//            throw new BadRequestHttpException("Cart is empty");
//        }
//        $order = new Order();
//        $order->total_price = $totalPrice;
//        $order->status = Order::STATUS_DRAFT;
//        $order->created_at = time();
//        $order->created_by = \Yii::$app->user->id;
//        $transaction = \Yii::$app->db->beginTransaction();
//        if ($order->load(\Yii::$app->request->post())
//            && $order->save()
//            && $order->saveAddress(\Yii::$app->request->post())
//            && $order->saveOrderItems()) {
//            $transaction->commit();
//
//            CartItem::clearCartItems(\Yii::$app->user->id);
//
//            $cartItems = CartItem::getItemsForUser(\Yii::$app->user->id);
//            $productQuantity = CartItem::getTotalQuantityForUser(\Yii::$app->user->id);
//            $totalPrice = CartItem::getTotalPriceForUser(\Yii::$app->user->id);
//
//            return $this->render('pay-now', [
//                'orderAddress' => $order->orderAddress,
//                'cartItems' => $cartItems,
//                'productQuantity' => $productQuantity,
//                'totalPrice' => $totalPrice,
//                'order' => $order
//            ]);
//
//        } else {
//            $transaction->rollBack();
//            return [
//                'success' => false,
//                'errors' => $order->errors,
//
//            ];
//        }

    }

}