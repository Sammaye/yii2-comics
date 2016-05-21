<?php

use yii\helpers\Url;
use common\models\Comic;

use MongoDB\BSON\UTCDateTime;

Yii::$app->response->setStatusCode(404);

$this->params['comic_id'] = (String)$model->_id;

$this->title = '#404 ZOMG strip Not Found!';

?>
<div class="comic-view-strip-not-found">
    
<div class="comic-date-picker">
<form method="get" action="<?= Url::to(['comic/view', 'id' => (String)$model->_id]) ?>">
<div>
<a href="#" disabled="disabled" class="btn btn-lg btn-default">&laquo;</a>
<input type="text" class="form-control input-lg" name="index" id="datepicker" 
value="<?php
if(Yii::$app->request->get('index')){
    $value = $model->index($value);
    if($value instanceof UTCDateTime){
        echo date('d-m-Y', $value->toDateTime()->getTimestamp());
    }else{
        echo $value;
    }
}else{
    $value = '####';
}
?>" />
<a href="#" disabled="disabled" class="btn btn-lg btn-default">&raquo;</a>
</div>
</form>
</div>
    
<div class="alert alert-danger">
C!y has no record of this strip!
</div>
<p>If you believe this is an error and this strip should be here, 
<a href="<?= Url::to(['site/help', '#' => 'need-help-support']) ?>">then please contact me through the help section</a>.</p>
<p>On the other hand if you wish to go back to one that does exist: <a href="<?= Url::to(['comic/view', 'id' => (String)$model->_id]) ?>">simply click here</a>.</p>
</div>