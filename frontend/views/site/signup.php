<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\authclient\widgets\AuthChoice;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\SignupForm $model
 */
$this->title = Yii::t('app', 'Signup');
?>
<div class="row">
    <div class="site-signup col-sm-15">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

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
            <div class="signup-form">
                <?= $form->field($model, 'username') ?>
                <?= $form->field($model, 'email') ?>
                <?= $form->field($model, 'password')->passwordInput() ?>
            </div>
            <div class="form-group form-submit">
                <?= Html::submitButton(
                    Yii::t('app', 'Signup'),
                    ['class' => 'btn btn-lg btn-success', 'name' => 'signup-button']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
