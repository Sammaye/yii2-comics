<?php

use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'View ' . $model->title . ' for ' . date('d-m-Y', $comicStrip->date->sec);

$this->registerJs("
$('#datepicker').datepicker({
	dateFormat : 
});
$('#format').change(function() {
$('#datepicker').datepicker( "option", "dateFormat", $( this ).val() );
});		
")

?>
<h1><?= $model->title ?></h1>
<?php if($model->description){
	echo Html::tag('p', $model->description);
} ?>
<a href="" class="btn btn-primary">Subscribe</a>
<div>
<div class="">
  <a href="" class="btn btn-lg btn-default">&laquo;</a>
  <input type="text" class="form-control input-lg" style='width:auto;' />
  <a href="" class="btn btn-lg btn-default">&raquo;</a>
</div>
</div>
<img src="<?= Url::to(['comic-strip/render-image', 'id' => (String)$comicStrip->_id]) ?>"/>
