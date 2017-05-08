<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use common\models\ComicStrip;

$this->title = Yii::t('app', 'Update Comic Strip');

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
<h1 class="form-head"><?= Yii::t('app', 'Update Comic Strip') ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>