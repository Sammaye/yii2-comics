<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use common\models\ComicStrip;

$this->title = 'Update Comic Strip';

?>
<h1>Update Comic Strip</h1>
<?= $this->render('_form', ['model' => $model]) ?>
<?= Html::a('Refresh Image', ['comic-strip/refresh-image', 'id' => (string)$model->_id], ['class' => 'btn btn-default']) ?>