<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'View ' . $model->title . ' for ' . date('d-m-Y', $comicStrip->date->sec);

$this->registerJs("
$('#datepicker').datepicker({
	dateFormat : 'dd-mm-yy',
	changeMonth: true,
	changeYear: true
});
");

$this->params['comic_id'] = (String)$model->_id;

?>
<?php if($model->description){
	echo Html::tag('p', $model->description);
} ?>
<a href="" class="btn btn-primary">Add to my email</a>
<div class="comic-date-picker">
<div>
	<?php if($comicStrip->getIsFirstStrip()){ ?>
  <a href="#" disabled="disabled" class="btn btn-lg btn-default">&laquo;</a>
	<?php }else{ ?>
  <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id, 'date' => date('d-m-Y', strtotime("-1 day", $comicStrip->date->sec))]) ?>" class="btn btn-lg btn-default">&laquo;</a>
	<?php } ?>
  <input type="text" class="form-control input-lg" id="datepicker" value="<?= $date ?>" />
	<?php if($comicStrip->getIsLastStrip()){ ?>
  <a href="#" disabled="disabled" class="btn btn-lg btn-default">&raquo;</a>
	<?php }else{ ?>
  <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id, 'date' => date('d-m-Y', strtotime("+1 day", $comicStrip->date->sec))]) ?>" class="btn btn-lg btn-default">&raquo;</a>
	<?php } ?>
</div>
</div>
<div style="text-align:center; ">
<a href="<?= Url::to($model->scrape_url . date($model->date_format, $comicStrip->date->sec)) ?>">
<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$comicStrip->_id]) ?>"/>
</a>
</div>
