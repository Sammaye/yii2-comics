<?php

use yii\helpers\Html;
use common\models\Comic;

$this->beginContent('@app/views/layouts/main.php'); ?>
<ul class="nav nav-tabs comics-view-nav" role="tablist">
<?php foreach(Comic::find()->orderBy(['title' => SORT_ASC])->all() as $comic){
	echo Html::tag(
		'li', 
		Html::a(
			$comic->title, 
			['comic/view', 'id' => (String)$comic->_id]
		), 
		['class' => (String)$comic->_id === $this->params['comic_id'] ? 'active' : '', 'role' => 'presentation']
	);
} ?>
<li class="float-right"><a href="#" data-toggle="modal" data-target=".request-comic-modal"><span class="glyphicon glyphicon-plus"></span> Demand addition</a></li>
</ul>
<div class="view-comic-content"><?= $content ?></div>
<?php $this->endContent() ?>