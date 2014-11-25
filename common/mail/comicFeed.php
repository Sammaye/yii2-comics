<?php

use yii\helpers\Url;
use yii\helpers\Html;

$lastComicTitle = null;
?>
<?php foreach($comics as $comic){ 
	if($comic->comic->title != $lastComicTitle){ 
		echo Html::tag('h1', $comic->comic->title); 
		$lastComicTitle = $comic->comic->title;
	} ?>
<div style='margin:10px 0;'>
<a href="<?= Url::to(['comic/view', 'id' => (String)$comic->comic->_id, 'date' => $comic->comic->is_increment ? $comic->inc_id : date('d-m-Y', $comic->date->sec)], 'http') ?>">
<img src="<?= Url::to(['comic/render-image', 'id' => (String)$comic->_id], 'http') ?>" style="border:0;" />
</a>
</div>
<?php } ?>