<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = Yii::t('app', 'Manage Comics');
?>
<h1><?= Yii::t('app', 'Manage Comics') ?></h1>
    <div class="admin-toolbar">
        <?= Html::a(
            Yii::t('app', 'Create Comic'),
            ['create'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>
<?= GridView::widget([
    'dataProvider' => $model->search(),
    'filterModel' => $model,
    'columns' => [
        '_id',
        'title',
        'abstract',
        [
            'label' => Yii::t('app', 'Strips'),
            'content' => function ($model, $key, $index, $column) {
                return $model->getStrips()->count();
            }
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
        ]
    ],
]) ?>