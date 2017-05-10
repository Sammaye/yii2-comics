<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Comic;
use common\widgets\Select2Asset;

Select2Asset::register($this);

$this->params['excludeContainer'] = true;

$this->registerJs("

$('.alert-summarise').summarise();
$('.alert-summarise').summarise('close');

$('#requestComicForm').on('submit', function(e){
	e.preventDefault();
	
	var modal = $(this).parents('.modal');
		
	$.post('" . Url::to(['comic/request']) . "', $('#requestComicForm').serialize(), null, 'json')
	.done(function(data){
	
        modal.find('.alert-summarise').summarise(
            'set', 
            data.success ? 'success' : 'error', 
            {
                message: data.message, 
                list: data.errors
            }
        )
        
        setTimeout(function(){
            modal.modal('hide');
        }, 3000);

	});
});

$('#request-comic-modal').on('hidden.bs.modal', function () {
    $(this).find('.alert-summarise').summarise('close');
});

$('#comicSelector').select2({
	width: '100%',
	placeholder: 'Select a Comic',
	dropdownCssClass: 'view-comic-select-comic-results',
	templateSelection: function (selection) {
		try{
			var o = $.parseJSON(selection.text);
			return $('<span/>').html(o.title + (o.author ? '<span>By ' + o.author + '</span>' : ''));
		}catch(e){
			return selection.text;
		}
	},
	templateResult: function (result) {
		try{
			var o = $.parseJSON(result.text);
			return $('<span/>').html(o.title + (o.author ? '<span>By ' + o.author + '</span>' : '')); 
		}catch(e){
			return result.text;
		}
	},
});

$('#comicSelector').on('change', function(e){
	window.location.href = '" . Url::to(['/comic']) . '/' . "' + $('#comicSelector option:selected').val();
});
");

$this->beginContent('@app/views/layouts/main.php'); ?>

<?php
$comics = ['' => ''];
foreach (
    Comic::find()
        ->where(['live' => 1])
        ->orderBy(['title' => SORT_ASC])
        ->all()
    as $comic
) {
    //if($this->params['comic_id'] !== (String)$comic['_id']){
    $comics[(String)$comic->_id] = json_encode(['title' => $comic->title, 'author' => $comic->author]);
    //}
}
?>
<div class="comic-view">
    <div class="container">
        <?= common\widgets\Alert::widget() ?>
    </div>
    <div class="nav-top">
        <div class="container">
            <div class="row">
                <div class="col-sm-35">
                    <?php if (count($comics) > 0) {
                        echo Html::dropDownList(
                            'comcSelector',
                            isset($this->params['comic_id']) ? $this->params['comic_id'] : null,
                            $comics,
                            ['id' => 'comicSelector', 'class' => 'form-control']
                        );
                    } ?>
                </div>
                <div class="col-sm-13">

                    <a
                        href="#"
                        class="btn btn-default btn-lg btn-transparent pull-right"
                        data-toggle="modal"
                        data-target=".request-comic-modal"
                    >
                        <span class="glyphicon glyphicon-plus"></span>
                        <?= Yii::t('app', 'Demand Addition') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content container"><?= $content ?></div>

    <div class="modal fade request-comic-modal" id="request-comic-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">
                            <?= Yii::t('app', 'Close') ?>
                        </span>
                    </button>
                    <h4 class="modal-title">
                        <?= Yii::t('app', 'Demand a comic/cartoon to be added') ?>
                    </h4>
                </div>
                <?php
                $form = ActiveForm::begin(['id' => 'requestComicForm']);
                $requestFormModel = Yii::$app->controller->comicRequestForm();
                ?>
                <div class="modal-body">
                    <div class="alert-summarise"></div>
                    <?= $form->field($requestFormModel, 'name') ?>
                    <?= $form->field($requestFormModel, 'url') ?>
                    <?php if (Yii::$app->getUser()->identity === null) {
                        echo Html::tag(
                            'p',
                            Yii::t(
                                'app',
                                'Since you are not logged in, add your email address here if you would like to be notified of when your comic is added'
                            ),
                            ['class' => 'margined-p']
                        );
                        echo $form->field($requestFormModel, 'email');
                    } else {
                        echo $form
                            ->field($requestFormModel, 'email')
                            ->textInput([
                                'value' => Yii::$app->getUser()->identity->email,
                                'readonly' => true
                            ]);
                    } ?>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit demands</button>
                </div>
                <?php $form->end() ?>
            </div>
        </div>
    </div>
</div>
<?php $this->endContent() ?>