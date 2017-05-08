<?php

$this->title = Yii::t(
    'app',
    'Update User: {username}',
    ['username' => $model->username]
);

?>
<?= $this->render('_form', ['model' => $model]) ?>
