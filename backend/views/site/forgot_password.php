<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap4\ActiveForm $form */

/** @var \backend\models\PasswordResetRequestForm $model */

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

$this->title = 'Login';
?>

<div class="row">
    <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
    <div class="col-lg-6">
        <div class="p-5">
            <div class="text-center">
                <h1 class="h4 text-gray-900 mb-4">Forgot Password?</h1>
            </div>
            <?php $form = ActiveForm::begin([
                'id' => 'forgot-password-form',
                'options' => ['class' => 'user']
            ]); ?>

            <?= $form->field($model, 'email', [
                'inputOptions' => [
                    'class' => 'form-control form-control-user',
                    'placeHolder' => 'Enter your email'
                ]
            ])->textInput(['autofocus' => true]) ?>

            <div class="form-group">
                <?= Html::submitButton('Submit', ['class' => 'btn btn-primary btn-user btn-block', 'name' => 'login-button']) ?>
            </div>
            <hr>
            <?php ActiveForm::end() ?>
            <div class="text-center">
                <a class="small" href="<?php echo \yii\helpers\Url::to(['/site/login']) ?>"> Login</a>
            </div>
        </div>
    </div>
</div>
