<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\LoginForm $model
 */
$this->title = 'Login';
?>
<div class="row">
<div class="site-login col-sm-15">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
    	<div class="login-form">
        	<?= $form->field($model, 'email')->textInput(['class' => 'form-control']) ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
            <?= $form->field($model, 'rememberMe')->checkbox() ?>

            <div class="password-reset">
               If you forgot your password you can <?= Html::a('reset it', ['site/request-password-reset']) ?>.
            </div>
        </div>
        <div class="form-group form-submit">
            <?= Html::submitButton('Login', ['class' => 'btn btn-success btn-lg', 'name' => 'login-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
