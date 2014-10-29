<?php

use Yii;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Create user';
?>
<h1>Create user</h1>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= $form->field($model, 'username') ?>
<?= $form->field($model, 'email') ?>
<?= $form->field($model, 'role') ?>
<?= $form->field($model, 'password')->passwordInput() ?>
<?= Html::submitButton('Create User', ['class' => 'btn btn-success']) ?>
<?php $form->end() ?>