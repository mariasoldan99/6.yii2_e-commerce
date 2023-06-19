<?php


/** @var common\models\User $user */
/** @var \yii\web\View $this */
/** @var common\models\UserAdress $userAddress */

?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Address information
            </div>
            <div class="card-body">
                <?php echo $this->render('user_address', [
                    'userAddress' => $userAddress
                ]) ?>
            </div>
        </div>

    </div>
    <div class="col">
        <div class="card">
            <div class="card-header">
                Account information
            </div>
            <div class="card-body">
                <?php echo $this->render('user_account', [
                    'user' => $user
                ]) ?>
            </div>
        </div>
    </div>
</div>
