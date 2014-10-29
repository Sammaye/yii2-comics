<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\ComicStrip;
use yii\helpers\Url;

$this->title = 'Update ' . $model->title;

?>
<h1>Update Comic</h1>
<?= $this->render('_form', ['model' => $model]) ?>
<?= Html::a('Add Strip', ['comic-strip/create', 'comic_id' => (String)$model->_id], ['class' => 'btn btn-primary']) ?>
<?php 
$comicStrip = new ComicStrip;
echo GridView::widget([
	'dataProvider' => $comicStrip->search($model->_id),
	'filterModel' => $comicStrip,
	'columns' => [
		'_id',
		'url',
		[
			'attribute' => 'date',
			'format' => 'date'
		],
		[
			'attribute' => 'updated_at',
			'format' => 'date'
		],
		[
			'attribute' => 'created_at',
			'format' => 'date'
		],
		[
			'class' => 'yii\grid\ActionColumn',
			'template' => '{update} {delete}',
			'urlCreator' => function($action, $model, $key, $index){
				$params = is_array($key) ? $key : ['id' => (string) $key];
				$params[0] = 'comic-strip' . '/' . $action;
				return Url::toRoute($params);
			}
		]
	]
]) ?>