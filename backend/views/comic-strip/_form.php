<?php

use Yii;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin() ?>
<?= $form->errorSummary($model) ?>
<?= $form->field($model, 'url') ?>
<?= $form->field($model, 'date') ?>
<?= Html::submitButton('Create Comic Strip', ['class' => 'btn btn-success']) ?>
<?php $form->end() ?>