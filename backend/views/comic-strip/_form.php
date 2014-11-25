<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= Html::activeHiddenInput($model, 'comic_id') ?>
<?= $form->field($model, 'url') ?>
<?php 
if($model->comic->is_increment){
	echo $form->field($model, 'inc_id'); 
}
echo $form->field($model, 'date')->textInput(['value' => $model->date instanceof \MongoDate ? date('d/m/Y', $model->date->sec) : null]);
?>
<?php if(!$model->getIsNewRecord()){ ?>
	<div>
	<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$model->_id]) ?>"/>
	</div>
<?php } ?>
<div class="toolbar comic-strip-form-end">
<?= Html::submitButton($model->getIsNewRecord() ? 'Create Comic Strip' : 'Update Comic Strip', ['class' => 'btn btn-success']) ?>
<?php
if(!$model->getIsNewRecord()){ 
	echo Html::a('Refresh Image', ['comic-strip/refresh-image', 'id' => (string)$model->_id], ['class' => 'btn btn-default']);
} 
?>
</div>
<?php $form->end() ?>