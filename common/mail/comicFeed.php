<?php

use yii\helpers\Url;
use yii\helpers\Html;

?>
<?php foreach($comics as $comic){ ?>
<h1><?= $comic->comic->title ?></h1>
<div style='margin:10px 0;'>
<img src="<?= Url::to(['comic/render-image', 'id' => (String)$comic->_id], 'http') ?>" />
</div>
<?php } ?>
