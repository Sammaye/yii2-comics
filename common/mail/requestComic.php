<?php
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var common\models\RequestComicForm $model
 */
?>

<p><?= Yii::t('app', 'Hello,') ?></p>
<p><?= Yii::t('app', 'Someone wants a comic adding to the cly website.') ?></p>
<p><?= Yii::t('app', 'Name: {name}', ['name' => $model->name]) ?></p>
<p><?= Yii::t('app', 'URL: {url}', ['url' => $model->url]) ?></p>
<p></p>
<p><?=
    Yii::t(
        'app',
        'If they entered an email to be contacted by or were logged in it is: {email}',
        ['email' => $model->email]
    )
?></p>