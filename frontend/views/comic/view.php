<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'View ' . $model->title . ' for ' . date('d-m-Y', $comicStrip->date->sec);

$this->registerJs("
$('#datepicker').datepicker({
	dateFormat : 'dd-mm-yy'
});
");

$this->params['comic_id'] = (String)$model->_id;

?>
<?php if($model->description){
	echo Html::tag('p', $model->description);
} ?>
<a href="" class="btn btn-primary">Subscribe</a>
<div>
<div class="">
  <a href="" class="btn btn-lg btn-default">&laquo;</a>
  <input type="text" class="form-control input-lg" id="datepicker" style='width:auto;' />
  <a href="" class="btn btn-lg btn-default">&raquo;</a>
</div>
</div>
<div style="text-align:center;">
<a href="<?= Url::to($model->scrape_url . date($model->date_format, $comicStrip->date->sec)) ?>">
<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$comicStrip->_id]) ?>"/>
</a>
</div>
