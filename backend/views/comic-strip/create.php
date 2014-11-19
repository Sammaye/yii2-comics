<?php

use Yii;
use yii\helpers\Html;
$this->title = 'Create Comic Strip for ' . $model->comic->title;
?>
<h1 class="form-head">Create Comic Strip for <?= $model->comic->title ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>