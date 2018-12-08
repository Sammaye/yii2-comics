<?php

use MongoDB\BSON\UTCDateTime;
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
				$('#comic-form')
				    .find('input:not(input[name=\'_csrf\']), select, textarea')
				    .serializeArray()
			),
			_csrf: $('#comic-form').find('input[name=_csrf]').val()
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

$(document).on('change', '#scraper_user_agent_prefill', function(e){
    var val = this.value;
    
    if(val){
        $('#comic-scraper_user_agent').val(val);
    }        
});
");
?>
<?php $form = ActiveForm::begin(['id' => 'comic-form', 'enableClientValidation' => false]) ?>
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
            <?= $form->field($model, 'base_url') ?>
            <?= $form->field($model, 'image_dom_path') ?>
        </div>
        <div class="col-sm-22 col-sm-push-4">
            <?= $form->field($model, 'index_format') ?>
            <?= $form->field($model, 'current_index')->textInput([
                'value' => $model->getCurrentIndexValue() instanceof UTCDateTime
                    ? $model->getCurrentIndexValue()->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat)
                    : $model->getCurrentIndexValue()
            ]) ?>
            <div>
                <?= Html::activeLabel($model, 'scraper_user_agent') ?>
                <p class="help-block">Selecting a user agent from the dropdown will pre-fill the scraper's user agent field with that option. You can also just type one in manually.</p>
                <div class="row">
                    <div class="col-sm-15">
                        <div class="form-group field-comic-scraper_user_agent_prefill">
                            <?= Html::dropDownList(
                                'scraper_user_agent_prefill',
                                null,
                                array_flip($model->userAgents),
                                [
                                    'class' => 'form-control',
                                    'prompt' => 'Choose a Pre-fill',
                                    'id' => 'scraper_user_agent_prefill'
                                ]
                            ) ?>
                        </div>
                    </div>
                    <div class="col-sm-33">
                        <?= $form->field($model, 'scraper_user_agent')->label(false) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr/>
    <div class="row">
        <div class="col-sm-22">
            <?= $form->field($model, 'nav_url_regex') ?>
            <?= $form->field($model, 'nav_previous_dom_path') ?>
            <?= $form->field($model, 'nav_next_dom_path') ?>
            <?= $form->field($model, 'nav_page_number_dom_path') ?>
        </div>
        <div class="col-sm-22 col-sm-push-4">
            <?= $form->field($model, 'first_index')->textInput([
                'value' => $model->getFirstIndexValue() instanceof UTCDateTime
                    ? $model->getFirstIndexValue()->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat)
                    : $model->getFirstIndexValue()
            ]) ?>
            <?= $form->field($model, 'last_index')->textInput([
                'value' => $model->getLastIndexValue() instanceof UTCDateTime
                    ? $model->getLastIndexValue()->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat)
                    : $model->getLastIndexValue()
            ]) ?>
            <?= $form->field($model, 'index_step') ?>
        </div>
    </div>
<?= Html::submitButton(
    Yii::t('app', $model->getIsNewRecord() ? 'Create Comic' : 'Update Comic'),
    ['class' => 'btn btn-success']
) ?>
<?php $form->end() ?>
