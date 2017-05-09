<?php

use Yii;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Create Comic');
?>
<h1 class="form-head"><?= Yii::t('app', 'Create Comic') ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>