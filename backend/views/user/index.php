<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\User;

$this->title = Yii::t('app', 'Manage Users');
?>
<h1><?= Yii::t('app', 'Mange Users') ?></h1>
<div class="admin-toolbar">
    <?= Html::a(
        Yii::t('app', 'Create User'),
        ['create'],
        ['class' => 'btn btn-primary']
    ) ?>
</div>
<?= GridView::widget([
    'dataProvider' => $model->search(),
    'filterModel' => $model,
    'columns' => [
        '_id',
        'username',
        'email',
        [
            'attribute' => 'status',
            'value' => function ($model, $key, $index, $column) {
                $value = $model->{$column->attribute};
                if ($value === User::STATUS_ACTIVE) {
                    return '<span class="text-success">Active</span>';
                } elseif ($value === User::STATUS_BANNED) {
                    return '<span class="text-warning">Banned</span>';
                } else {
                    return '<span class="text-danger">Deleted</span>';
                }
            },
            'format' => 'html'
        ],
        'facebook_id',
        'google_id',
        [
            'attribute' => 'created_at',
            'format' => 'date'
        ],
        [
            'attribute' => 'updated_at',
            'format' => 'date'
        ],
        [
            'attribute' => 'deleted_at',
            'format' => 'date'
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{update} {delete}',
        ]
    ],
]) ?>
