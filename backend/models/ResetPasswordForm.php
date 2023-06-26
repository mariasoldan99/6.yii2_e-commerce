<?php

namespace backend\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;
use Yii;
use common\models\User;

/**
 * Password reset form
 */
class ResetPasswordForm extends \common\models\ResetPasswordForm
{
    public function findUser($token)
    {
        if (!User::isPasswordResetTokenValid($token)) {
            return null;
        }

        return User::findOne([
            'password_reset_token' => $token,
            'status' => User::STATUS_ACTIVE,
            'admin' => 1
        ]);

    }
}
