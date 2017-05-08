<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\ResetPasswordForm $model
 */
$this->title = Yii::t('app', 'Reset password');
?>
<div class="site-reset-password">
    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Yii::t('app', 'Please choose your new password:') ?></p>

    <div class="row">
        <div class="col-sm-15">
            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>

            <?= $form->field($model, 'password')->passwordInput() ?>
            <div class="form-group">
                <?= Html::submitButton(
                    Yii::t('app', 'Save'),
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>
            
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
