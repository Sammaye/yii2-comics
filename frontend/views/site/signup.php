<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \common\models\SignupForm $model
 */
$this->title = 'Signup';
?>
<div class="row">
<div class="site-signup col-sm-15">
    <h1><?= Html::encode($this->title) ?></h1>

    <div>
        <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>
        	<div class="signup-form">
            <?= $form->field($model, 'username') ?>
            <?= $form->field($model, 'email') ?>
            <?= $form->field($model, 'password')->passwordInput() ?>
            </div>
            <div class="form-group form-submit">
                <?= Html::submitButton('Signup', ['class' => 'btn btn-lg btn-success', 'name' => 'signup-button']) ?>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
</div>
