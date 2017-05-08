<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\authclient\widgets\AuthChoice;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\LoginForm $model
 */
$this->title = Yii::t('app', 'Login');
?>
<div class="row">
    <div class="site-login col-sm-15">
        <h1><?= Html::encode($this->title) ?></h1>
        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

        <?php $authAuthChoice = AuthChoice::begin([
            'baseAuthUrl' => ['site/auth'],
            'popupMode' => false,
        ]); ?>
        <p class="text-center text-muted"><?= Yii::t('app', 'Using your favourite network') ?>:</p>
        <ul class="social-login-methods">
            <?php foreach ($authAuthChoice->getClients() as $client): ?>
                <li><?= $authAuthChoice->clientLink($client) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="text-center text-muted"><?= Yii::t('app', 'Or manually') ?>:</p>

        <div class="login-form">
            <?= $form->field($model, 'email')->textInput(['class' => 'form-control']) ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
            <?= $form->field($model, 'rememberMe')->checkbox() ?>

            <div class="password-reset">
                <?= Yii::t(
                    'app',
                    'If you forgot your password you can {link}',
                    [
                        'link' => Html::a(
                            Yii::t('app', 'reset it'),
                            ['site/request-password-reset']
                        )
                    ]
                ) ?>
            </div>
        </div>
        <div class="form-group form-submit">
            <?= Html::submitButton('Login', ['class' => 'btn btn-success btn-lg', 'name' => 'login-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
