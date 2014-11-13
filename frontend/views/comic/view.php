<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\RequestComicForm;

$this->title = 'View ' . $model->title . ' for ' . date('d-m-Y', $comicStrip->date->sec);

$this->registerJs("
$('#datepicker').datepicker({
	dateFormat : 'dd-mm-yy',
	changeMonth: true,
	changeYear: true,
	maxDate: '" . date('d-m-Y') . "'
});

$('#datepicker').on('change', function(e){
	$(this).parents('form').submit();
});

$(document).on('click', '.btn-subscribe', function(e){
	e.preventDefault();
	$.get('" . Url::to(['comic/subscribe']) . "', {comic_id: '" . (String)$model->_id . "'}, null, 'json')
	.done(function(data){
		if(data.success){
			var btn = $('.btn-subscribe');
			btn.find('span').removeClass('glyphicon-ok').addClass('glyphicon-remove');
			btn.get(0).lastChild.nodeValue = ' Remove from email';
			btn.addClass('btn-unsubscribe btn-danger').removeClass('btn-subscribe btn-success');
		}
	});
});

$(document).on('click', '.btn-unsubscribe', function(e){
	e.preventDefault();
	$.get('" . Url::to(['comic/unsubscribe']) . "', {comic_id: '" . (String)$model->_id . "'}, null, 'json')
	.done(function(data){
		if(data.success){
			var btn = $('.btn-unsubscribe');
			btn.find('span').removeClass('glyphicon-remove').addClass('glyphicon-ok')
			btn.get(0).lastChild.nodeValue = ' Add to my email';
			btn.addClass('btn-subscribe btn-success').removeClass('btn-unsubscribe btn-danger');
		}
	});
});

$('#requestComicForm').on('submit', function(e){
	e.preventDefault();
		
	$.post('" . Url::to(['comic/request']) . "', $('#requestComicForm').serialize(), null, 'json')
	.done(function(data){
		$('#requestComicForm').find('.alert-request-errors').removeClass('alert-danger alert-success').html('');
		
		if(data.success){
		
			$('#requestComicForm').find('.alert-request-errors').addClass('alert-success').css({display: 'block'});
		
			$('#requestComicForm').find('.alert-request-errors').append(
				$('<p/>').text('Your request was successfully sent, thank you helping to make this site better!')
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
");

$this->params['comic_id'] = (String)$model->_id;

?>
<div class="comic-info-outer">
<div class="row">
<div class="col-sm-35">
<?php if($model->description){
	echo Html::tag('p', $model->description);
} ?>
<?php 
if($model->author || $model->homepage){
	echo Html::beginTag('p', ['class' => 'text-muted']);
	if($model->author){
		if(!$model->author_homepage){
			echo'By ' . $model->author;
		}else{
			echo 'By ' . Html::a($model->author, $model->author_homepage, ['rel' => 'nofollow', 'target' => '_blank']);
		}
	}
	if($model->homepage){
		echo Html::a('Homepage', $model->homepage, ['class' => 'comic-homepage', 'rel' => 'nofollow', 'target' => '_blank']);
	}
	echo Html::endTag('p');
}?>
</div>
<div class="col-sm-10 col-sm-push-2">
<?php 
if(
	($user = Yii::$app->getUser()->identity) && 
	($user->isSubscribed($model->_id))
){
?>
<a href="#" class="btn btn-lg btn-danger btn-unsubscribe"><span class="glyphicon glyphicon-remove"></span> Remove from email</a>
<?php }else{ ?>
<a href="#" class="btn btn-lg btn-success btn-subscribe"><span class="glyphicon glyphicon-ok"></span> Add to my email</a>
<?php } ?>
</div>
</div>
</div>

<div class="comic-date-picker">
<form method="get" action="<?= Url::to(['comic/view', 'id' => (String)$model->_id]) ?>">
<div>
	<?php if($comicStrip->getIsFirstStrip()){ ?>
  <a href="#" disabled="disabled" class="btn btn-lg btn-default">&laquo;</a>
	<?php }else{ ?>
  <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id, 'date' => date('d-m-Y', strtotime("-1 day", $comicStrip->date->sec))]) ?>" class="btn btn-lg btn-default">&laquo;</a>
	<?php } ?>
  <input type="text" class="form-control input-lg" name="date" id="datepicker" value="<?= $date ?>" />
	<?php if($comicStrip->getIsLastStrip()){ ?>
  <a href="#" disabled="disabled" class="btn btn-lg btn-default">&raquo;</a>
	<?php }else{ ?>
  <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id, 'date' => date('d-m-Y', strtotime("+1 day", $comicStrip->date->sec))]) ?>" class="btn btn-lg btn-default">&raquo;</a>
	<?php } ?>
</div>
</form>
</div>
<div class="comic-view-item">
<a href="<?= Url::to($model->scrape_url . date($model->date_format, $comicStrip->date->sec)) ?>" rel="nofollow" target="_blank">
<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$comicStrip->_id]) ?>" class="img-responsive comic-img"/>
</a>
</div>

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
      <p>Currently comics are added manually by hand for modertion reasons.</p>
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