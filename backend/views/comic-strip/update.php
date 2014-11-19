<?php

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use common\models\ComicStrip;

$this->title = 'Update Comic Strip';

?>
<h1 class="form-head">Update Comic Strip</h1>
<?= $this->render('_form', ['model' => $model]) ?>