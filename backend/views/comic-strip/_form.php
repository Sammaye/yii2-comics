<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= Html::activeHiddenInput($model, 'comic_id') ?>
<?= $form->field($model, 'url') ?>
<?= $form->field($model, 'index')->textInput([
	'value' => 
		$model->index instanceof \MongoDate 
		? date('d/m/Y', $model->index->sec) 
		: $model->index
]) ?>
<?= $form->field($model, 'date')->textInput([
	'value' => 
		$model->date instanceof \MongoDate 
		? date('d/m/Y', $model->date->sec) 
		: $model->date
]) ?>
<?= $form->field($model, 'skip')->checkbox() ?>
<?= $form->field($model, 'next')->textInput([
	'value' => 
		$model->next instanceof \MongoDate 
		? date('d/m/Y', $model->next->sec) 
		: $model->next
]) ?>
<?= $form->field($model, 'previous')->textInput([
	'value' => 
		$model->previous instanceof \MongoDate 
		? date('d/m/Y', $model->previous->sec) 
		: $model->previous
]) ?>
<?php if(!$model->getIsNewRecord()){
	if(is_array($model->img)){
		foreach($model->img as $k => $v){ ?>
		<div><img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$model->_id . '_' . $k]) ?>"/></div>
		<?php }
	}else{ ?>
	<div><img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$model->_id]) ?>"/></div>
	<?php } ?>
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