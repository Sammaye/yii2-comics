<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
<?= $form->errorSummary($model) ?>
<?= Html::activeHiddenInput($model, 'comic_id') ?>
<?= $form->field($model, 'url') ?>
<?= $form->field($model, 'date')->textInput(['value' => date('d/m/Y', $model->date->sec)]) ?>
<?php if(!$model->getIsNewRecord()){ ?>
	<div>
	<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$model->_id]) ?>"/>
	</div>
<?php } ?>
<?= Html::submitButton($model->getIsNewRecord() ? 'Create Comic Strip' : 'Update Comic Strip', ['class' => 'btn btn-success']) ?>
<?php $form->end() ?>