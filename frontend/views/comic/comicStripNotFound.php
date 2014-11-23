<?php

use yii\helpers\Url;

$this->params['comic_id'] = (String)$model->_id;

?>
<div class="comic-view-strip-not-found">
<div class="alert alert-danger">
This strip was not found on this site
</div>
<p>If you believe this is an error and this strip should be here, 
<a href="<?= Url::to(['site/help', '#' => 'need-help-support']) ?>">then please contact me through the help section</a>.</p>
<p>On the other hand if you wish to go back to one that does exist: <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id]) ?>">simply click here</a>.</p>
</div>