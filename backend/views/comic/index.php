<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;

?>
<h1>Comics</h1>
<?= GridView::widget([
	'dataProvider' => $model->search(),
	'filterModel' => $model,
	'columns' => $model->attributes(),
]) ?>