<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\PasswordResetRequestForm $model
 */
$this->title = Yii::t('app', 'Request password reset');
?>
<div class="site-request-password-reset">
    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Yii::t(
        'app',
        'Please fill out your email. A link to reset your password will be sent there'
    ) ?></p>

    <div class="row">
        <div class="col-sm-15">
            <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

            <?= $form->field($model, 'email') ?>
            <div class="form-group">
                <?= Html::submitButton(
                    Yii::t('app', 'Send'),
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
