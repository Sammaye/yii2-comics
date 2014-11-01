<?php

use yii\helpers\Html;
use common\models\Comic;

$this->beginContent('@app/views/layouts/main.php'); ?>
<ul class="nav nav-tabs" role="tablist">
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
</ul>
<div class="view-comic-content"><?= $content ?></div>
<?php $this->endContent() ?>