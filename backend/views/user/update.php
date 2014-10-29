<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Update User ' . $model->username;

?>
<h1>Update user <?= $model->username ?></h1>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= $form->field($model, 'username') ?>
<?= $form->field($model, 'email') ?>
<?= $form->field($model, 'role') ?>
<?= $form->field($model, 'newPassword')->label('Password')->passwordInput() ?>
<?= Html::submitButton('Update User', ['class' => 'btn btn-success']) ?>
<?= Html::a(
	$model->status > 0 ? 'Prevent Login' : 'Allow Login', 
	['user/toggle-login', 'id' => (String)$model->_id], 
	['class' => 'btn btn-default']
) ?>
<?php $form->end() ?>