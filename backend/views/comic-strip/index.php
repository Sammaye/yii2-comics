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
	'columns' => $model->attributes(),
]) ?>