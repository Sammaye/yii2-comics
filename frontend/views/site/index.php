<?php

use common\models\SignupForm;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 */
$this->title = 'Welcome to c!y';

$this->params['excludeContainer'] = true;
?>
<div class="site-index">
<div class="top-ribbon">
<div class="container alert-container">
	<?= common\widgets\Alert::widget() ?>
    <div class="jumbotron">
    <h1>Welcome to c!y</h1>
    <div class="row">
    <div class="col-sm-24">
	<p class="text-muted text-discolored"><i>noun</i> /ci-lee/</p>
    <p>A new free cartoon distribution service which allows you to aggregate and view your 
    cartoons by a schedule of your choosing from the comfort of your email inbox; currently allowing you 
    to batch cartoons by daily, weekly or monthly schedule .</p>
    <p>And the best part? It is absolutely free!</p>
    </div>
    <div class="col-sm-19 col-sm-push-5 col-signup">
    <h2>Try it out!</h2>
    <p>Already have an account? <a href="<?= Url::to(['site/login']) ?>">Click here to sign in</a></p>
        <div>
    <p class="social-login-desc">With your favourite network:</p>
<?= common\widgets\AuthChoice::widget([
     'baseAuthUrl' => ['site/auth']
]) ?>
<p class="social-login-desc">Or:</p>
    </div>
    <?php 
    	$model = new SignupForm();
        $form = ActiveForm::begin(['id' => 'form-signup', 'action' => ['site/signup']]); ?>
        <div class="signup-form">
        <?= $form->field($model, 'username') ?>
        <?= $form->field($model, 'email') ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        </div>
        <div class="form-group form-submit" style="text-align:center">
        <?= Html::submitButton('Sign up', ['class' => 'btn btn-lg btn-transparent', 'name' => 'signup-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>
    </div>
    </div>
    </div>
</div>
</div>

<div>
<div class="container">
<div class="jumbotron jumbo-more-info">
<h2>Need more information?</h2>
<p><a href="<?= Url::to(['site/help', '#' => 'faqs']) ?>">Why not try the FAQ section? It should help you get a more 
detailed idea of what this site is and does</a></p>
</div>
</div>
</div>

</div>
