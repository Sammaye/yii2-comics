<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

$this->registerJs("
$(document).on('change', '#comic-scraper', function(){
	post(
		'" . (
			$model->getIsNewRecord() ? 
			Url::to(['/comic/create']) : 
			Url::to(['/comic/update', 'id' => (String)$model->_id])
		) . "', 
		{
			type_change: JSON.stringify(
				$('#comicForm input:not(input[name=\'_csrf\']), #comicForm select, #comicForm textarea')
				.serializeArray()
			)
		}
	);
});

$(document).on('change', '#comic-type', function(){
	if($(this).val() == " . $model::TYPE_ID . "){
		$('.field-comic-index_format').css({display: 'none'});
	}else{
		$('.field-comic-index_format').css({display: 'block'});
	}
});
$('#comic-type').trigger('change');
");
?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false, 'id' => 'comicForm']) ?>
<?= $form->errorSummary($model) ?>
<div class="row">
<div class="col-sm-22">
<?= $form->field($model, 'title') ?>
<?= $form->field($model, 'slug') ?>
<?= $form->field($model, 'homepage') ?>
<?= $form->field($model, 'description')->textarea() ?>
</div>
<div class="col-sm-22 col-sm-push-4">
<?= $form->field($model, 'abstract') ?>
<?= $form->field($model, 'author') ?>
<?= $form->field($model, 'author_homepage') ?>
<?= $form->field($model, 'active')->checkbox() ?>
<?= $form->field($model, 'live')->checkbox() ?>
</div>
</div>
<hr/>
<div class="row">
<div class="col-sm-22">
<?= $form->field($model, 'type')->dropDownList($model->getTypes()) ?>
<?= $form->field($model, 'scraper')->dropDownList($model::getScrapers(), ['prompt' => 'Default']) ?>
<?= $form->field($model, 'scrape_url') ?>
<?= $form->field($model, 'dom_path') ?>
<?= $form->field($model, 'index_format') ?>
</div>
<div class="col-sm-22 col-sm-push-4">
<?= $form->field($model, 'current_index')->textInput(['value' => $model->getCurrentIndexValue()]) ?>
<?= $form->field($model, 'last_index')->textInput(['value' => $model->getLastIndexValue()]) ?>
<?= $form->field($model, 'first_index')->textInput(['value' => $model->getFirstIndexValue()]) ?>
<?= $form->field($model, 'index_step') ?>	
</div>
</div>
<?= Html::submitButton(
	$model->getIsNewRecord() ? 'Create Comic' : 'Update Comic', 
	['class' => 'btn btn-success']
) ?>
<?php $form->end() ?>