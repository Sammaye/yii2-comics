<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'View ' . $model->title . ' for ' . date('d-m-Y', $comicStrip->date->sec);

if(!$comicStrip->comic->is_increment){
	$this->registerJs("
	$('#datepicker').datepicker({
		dateFormat : 'dd-mm-yy',
		changeMonth: true,
		changeYear: true,
		maxDate: '" . date('d-m-Y') . "'
	});
	");
}

$this->registerJs("
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
");

$this->params['comic_id'] = (String)$model->_id;

?>
<div class="comic-info-outer">
<div class="row">
<div class="col-md-35 col-sm-30">
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
<div class="col-md-10 col-md-push-2 col-sm-18">
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
  <a href="<?= $comicStrip->getPreviousUrl() ?>" class="btn btn-lg btn-default">&laquo;</a>
	<?php } ?>
  <input type="text" class="form-control input-lg" name="date" id="datepicker" value="<?= $date ?>" />
	<?php if($comicStrip->getIsLastStrip()){ ?>
  <a href="#" disabled="disabled" class="btn btn-lg btn-default">&raquo;</a>
	<?php }else{ ?>
  <a href="<?= $comicStrip->getNextUrl() ?>" class="btn btn-lg btn-default">&raquo;</a>
	<?php } ?>
</div>
</form>
</div>
<div class="comic-view-item">
<a href="<?= Url::to($model->scrape_url . ($model->is_increment ? $comicStrip->inc_id : date($model->date_format, $comicStrip->date->sec))) ?>" rel="nofollow" target="_blank">
<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$comicStrip->_id]) ?>" class="img-responsive comic-img"/>
</a>
</div>