<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<div class="row">
<div class="col-sm-22">
<?= $form->field($model, 'title') ?>
<?= $form->field($model, 'slug') ?>
<?= $form->field($model, 'homepage') ?>
<?= $form->field($model, 'author') ?>
<?= $form->field($model, 'author_homepage') ?>
<?= $form->field($model, 'description')->textarea() ?>
</div>
<div class="col-sm-22 col-sm-push-4">
<?= $form->field($model, 'abstract') ?>
<?= $form->field($model, 'scrape_url') ?>
<?= $form->field($model, 'date_format') ?>
<?= $form->field($model, 'dom_path') ?>
<?= $form->field($model, 'is_increment')->checkbox() ?>
<?= $form->field($model, 'inc_at_create') ?>
<?= $form->field($model, 'date_of_inc') ?>
<?= $form->field($model, 'day_step') ?>
</div>
</div>
<?= Html::submitButton(
	$model->getIsNewRecord() ? 'Create Comic' : 'Update Comic', 
	['class' => 'btn btn-success']
) ?>
<?php $form->end() ?>