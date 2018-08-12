<?php

use yii\helpers\Html;

$this->title = Yii::t(
    'app',
    'Create Comic Strip for {title}',
    ['title' => $model->comic->title]
);

?>
<?= Html::a(
    Yii::t(
        'app',
        'Back to {title}',
        ['title' => $model->comic->title]
    ),
    ['comic/update', 'id' => (String)$model->comic->_id],
    ['class' => 'return-to-comic-link']
) ?>
<h1 class="form-head"><?= Yii::t(
    'app',
    'Create Comic Strip for {title}',
    ['title' => $model->comic->title]
) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
