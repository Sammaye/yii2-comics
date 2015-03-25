<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use common\widgets\Alert;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var common\models\LoginForm $model
 */
$this->title = 'Login';
echo Alert::widget();
?>
<div class="row">
<div class="site-login col-sm-15">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="new-user-signup">New to c!y? <a href="<?= Url::to(['site/signup']) ?>">You can register for an account by clicking here</a>.</p>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
    	<div class="login-form">
        	<?= $form->field($model, 'email')->textInput(['class' => 'form-control', 'value' => Yii::$app->getUser()->identity->email]) ?>
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