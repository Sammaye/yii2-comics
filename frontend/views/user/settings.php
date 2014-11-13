<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Your Settings';
?>
<h2>Subscriptions</h2>
<?php $form = ActiveForm::begin() ?>
<div class="row">
<div class="col-sm-15"> </div>
<div class="col-sm-15 col-sm-push-10"><?= $form->field($model, 'email_frequency')->dropDownList($model->emailFrequencies()) ?></div>
</div>
<div class="row">
<div class="col-sm-15">
<h2>Details</h2>
<?= $form->field($model, 'username') ?>
<?= $form->field($model, 'email') ?>
</div>
<div class="col-sm-15 col-sm-push-10">
<h2>Password</h2>
<?= $form->field($model, 'oldPassword') ?>
<?= $form->field($model, 'newPassword') ?>
</div>
</div>
<div style="margin-top:30px;">
<button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmDeletion">Delete Account</button>
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
        <button type="button" class="btn btn-danger">Delete</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->