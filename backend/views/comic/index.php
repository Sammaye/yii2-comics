<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Administrate Comics';
?>
<h1>Comics</h1>
<div>
<?= Html::a('Create Comic', ['create']) ?>
</div>
<?= GridView::widget([
	'dataProvider' => $model->search(),
	'filterModel' => $model,
	'columns' => [
		'_id',
		'title',
		'abstract',
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
		]
	],
]) ?>