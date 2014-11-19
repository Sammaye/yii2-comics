<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Administrate Users';
?>
<h1>Users</h1>
<div class="admin-toolbar">
<?= Html::a('Create User', ['create'], ['class' => 'btn btn-primary']) ?>
</div>
<?= GridView::widget([
	'dataProvider' => $model->search(),
	'filterModel' => $model,
	'columns' => [
		'_id',
		'username',
		'email',
		'status',
		'role',
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