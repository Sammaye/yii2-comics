<?php

use Yii;
use yii\helpers\Html;

$this->title = 'Update ' . $model->title;

?>
<h1>Update Comic</h1>
<?= $this->render('_form', ['model' => $model]) ?>