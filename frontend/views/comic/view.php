<?php

use yii\helpers\Url;
use yii\helpers\Html;

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
<div class="col-sm-10 col-sm-push-3">
<a href="" class="btn btn-lg btn-success"><span class="glyphicon glyphicon-ok"></span> Add to my email</a>
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
