<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= $form->field($model, 'title') ?>
<?= $form->field($model, 'homepage') ?>
<?= $form->field($model, 'author') ?>
<?= $form->field($model, 'author_homepage') ?>
<?= $form->field($model, 'description')->textarea() ?>
<?= $form->field($model, 'slug') ?>
<?= $form->field($model, 'abstract') ?>
<?= $form->field($model, 'scrape_url') ?>
<?= $form->field($model, 'date_format') ?>
<?= Html::submitButton(
	$model->getIsNewRecord() ? 'Create Comic' : 'Update Comic', 
	['class' => 'btn btn-success']
) ?>
<?php $form->end() ?>