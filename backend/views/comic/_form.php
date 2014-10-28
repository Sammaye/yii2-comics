<?php

use Yii;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin() ?>
<?= $form->errorSummary($model) ?>
<?= $form->field($model, 'title') ?>
<?= $form->field($model, 'description') ?>
<?= $form->field($model, 'slug') ?>
<?= $form->field($model, 'abstract') ?>
<?= Html::submitButton('Create Comic', ['class' => 'btn btn-success']) ?>
<?php $form->end() ?>