<?php
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var common\models\User $user
 */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl([
    'site/reset-password',
    'token' => $user->password_reset_token
]);
?>
<p><?=
    Yii::t(
        'app',
        'Hello {name},',
        ['name' => Html::encode($user->username)]
    )
?></p>
<p></p>
<p><?= Yii::t('app', 'Follow the link below to reset your password:') ?></p>
<p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
