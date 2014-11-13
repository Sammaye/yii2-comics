<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Comic;

$this->title = 'Your Settings';

$this->registerJs("
	$(document).on('click', '.btn-delete', function(e){
		window.location.replace('" . Url::to(['user/delete']) . "');
	});

	$( '#sortable' ).sortable();
	$( '#sortable' ).disableSelection();
	$( '#sortable' ).bind( 'sortstop', function(event, ui) {
	});
");
?>
<div class="user-settings">
<h2>Subscriptions</h2>
<?php $form = ActiveForm::begin() ?>
<?= $form->errorSummary($model) ?>
<div class="row">
<div class="col-sm-17">
<?php if(count($model->comics) > 0){ ?>
	<p>Hold down (click or touch) on each row and move around to re-order your subscriptions.</p>
  <div class="sortable-outer">
    <ul class="sortable-subscriptions" id="sortable">
    <?php foreach($model->comics as $k => $comic){ 
    	if($comic = Comic::find()->where(['_id' => $comic['comic_id']])->one()){ ?>
    	<li class="clearfix"><span><?= $comic->title ?></span>
    	<?= Html::a('Unsubscribe', ['comic/unsubscribe', 'comic_id' => (String)$comic->_id], ['class' => 'btn btn-sm btn-danger']) ?></li>
    	<?php }
    } ?>
    </ul>
  </div>

<?php }else{ ?>
<p>You are currently not subscribed to any comics, pick some and return here to be able to manage them.</p>
<?php } ?>
</div>
<div class="col-sm-15 col-sm-push-8"><?= $form->field($model, 'email_frequency')->dropDownList($model->emailFrequencies()) ?></div>
</div>
<?= Html::submitButton('Save Subscriptions', ['class' => 'btn btn-success']) ?>
<div class="row">
<div class="col-sm-15">
<h2>Details</h2>
<?= $form->field($model, 'username') ?>
<?= $form->field($model, 'email') ?>
<?= Html::submitButton('Save Changes', ['class' => 'btn btn-success']) ?>
</div>
<div class="col-sm-15 col-sm-push-10">
<h2>Password</h2>
<?= $form->field($model, 'oldPassword')->passwordInput() ?>
<?= $form->field($model, 'newPassword')->passwordInput() ?>
<?= Html::submitButton('Change Password', ['class' => 'btn btn-success']) ?>
</div>
</div>
<div class="delete-account-link">
<a href="#confirmDeletion" data-toggle="modal" class="text-danger delete-account-link">Delete Account</a>
</div>
<?php $form->end() ?>

<div class="modal fade" id="confirmDeletion">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">Confirm Account Deletion</h4>
      </div>
      <div class="modal-body">
        <p>Are you really sure? You will never be able to get this account back, it will be deleted.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-delete">Delete</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

</div>