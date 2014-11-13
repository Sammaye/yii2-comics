<?php
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var common\models\RequestComicForm $model
 */
?>

<p>Hello,</p>
<p>Someone wants a comic adding to the c!y website.</p>
<p>Name: <?= $model->name ?></p>
<p>URL: <?= $model->url ?></p>
<p></p>
<p>If they entered an email to be contacted by or were logged in it is: <?= $model->email ?></p>
<p></p>
<p>Love, c!y</p>