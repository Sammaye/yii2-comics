<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Comic;
use common\models\RequestComicForm;
use common\widgets\Select2Asset;

Select2Asset::register($this);

$this->registerJs("
$('#requestComicForm').on('submit', function(e){
	e.preventDefault();
		
	$.post('" . Url::to(['comic/request']) . "', $('#requestComicForm').serialize(), null, 'json')
	.done(function(data){
		$('#requestComicForm').find('.alert-request-errors').removeClass('alert-danger alert-success').html('');
		
		if(data.success){
		
			$('#requestComicForm').find('.alert-request-errors').addClass('alert-success').css({display: 'block'});
		
			$('#requestComicForm').find('.alert-request-errors').append(
				$('<p/>').text('Your request was successfully sent, thank you helping to make this site better!')
			).append(
				$('<p/>').append($('<a/>').text('Click here to close this form').attr({'data-dismiss': 'modal', href: '#'}))
			);
		}else{
		
			$('#requestComicForm').find('.alert-request-errors').addClass('alert-danger').css({display: 'block'});
		
			var ul = $('<ul/>');
			$.each(data.errors, function(){
				for(var i = 0; i < $(this).length; i++){
					ul.append($('<li/>').text($(this)[i]));
				}
			});
		
			$('#requestComicForm').find('.alert-request-errors').append(
				$('<p/>').text('Your request could not be sent because:')
			).append(ul);
		}
	});
});

function format(row) {
	row = $.parseJSON(row.text);
	if (!row.author) return row.title; // optgroup
	return row.title + '<span>By ' + row.author + '</span>';
}

$('#comicSelector').select2({
	formatSelection: format,
	formatResult: format
});

$('#comicSelector').on('change', function(e){
	window.location.href = '" . Url::to(['/comic']) . '/' . "' + $('#comicSelector option:selected').val();
});
");


$this->beginContent('@app/views/layouts/main.php'); ?>

<?php 
$comics = [];
foreach(Comic::find()->orderBy(['title' => SORT_ASC])->all() as $comic){
	//if($this->params['comic_id'] !== (String)$comic['_id']){
		$comics[(String)$comic->_id] = json_encode(['title' => $comic->title, 'author' => $comic->author]);
	//}
}
?><div class="view-comic-nav-top">
<div class="row">
<div class="col-sm-35">
<?php if(count($comics) > 0){
	echo Html::dropDownList(
		'comcSelector', 
		isset($this->params['comic_id']) ? $this->params['comic_id'] : null, 
		$comics, 
		['id' => 'comicSelector', 'class' => 'form-control']
	); 
} ?>
</div>
<div class="col-sm-13">
<ul class="nav nav-tabs comics-view-nav" role="tablist">
<li class="float-right"><a href="#" data-toggle="modal" data-target=".request-comic-modal"><span class="glyphicon glyphicon-plus"></span> Demand addition</a></li>
</ul>
</div>
</div>
</div>



<div class="view-comic-content"><?= $content ?></div>

<?php $model = new RequestComicForm(); ?>
<div class="modal fade request-comic-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">Demand a comic/cartoon to be added</h4>
      </div>
      <?php $form = ActiveForm::begin(['id' => 'requestComicForm']) ?>
      <div class="modal-body">
      <p>Currently comics are added manually by hand for moderation reasons.</p>
      <p>You can demand to have your comic added by just filling in the name and URL of the comic, however, 
      you must be aware that some sites are not crawlable.</p>
      <div class="alert alert-warning">
      <p>If your comic/cartoon suggestion cannot be added I will attempt to emaiil you if an address is provided. For logged in users the field is already filled, however, 
      for those users who are not you must fill in the field to be notified.</p>
      </div>
      <div class="alert alert-request-errors display-none"></div>
      <?= $form->field($model, 'name') ?>
      <?= $form->field($model, 'url') ?>
      <?php if(Yii::$app->getUser()->identity === null){
      	echo Html::tag('p', 'Since you are not logged in, add your email address here if you would like to be notified of when your comic is added', ['class' => 'margined-p']);
      	echo $form->field($model, 'email'); 
      }else{
      	echo $form->field($model, 'email')->textInput(['value' => Yii::$app->getUser()->identity->email, 'readonly' => true]);
      } ?>
      
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Submit demands</button>
      </div>
      <?php $form->end() ?>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php $this->endContent() ?>