<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Comic;

$this->title = Yii::t('app', 'Your Settings');

$this->registerJs("
	$(document).on('click', '.btn-unsubscribe', function(e){
        e.preventDefault();
        $(this).parents('.sortable-subscription').remove();
	});

	$( '#sortable' ).sortable();
	$( '#sortable' ).disableSelection();
	$( '#sortable' ).bind( 'sortstop', function(event, ui) {
	});
");
?>
<div class="user-settings">
    <h2><?= Yii::t('app', 'Subscriptions') ?></h2>
    <?php $form = ActiveForm::begin(['id' => 'user-update', 'enableClientValidation' => false]) ?>

    <?= $form->errorSummary($model) ?>
    <div class="row">
        <div class="col-sm-17">
            <?php if (count($model->comics) > 0) { ?>

                <p><?= Yii::t(
                    'app',
                    'Hold down (click or touch) on each row and move around to re-order your subscriptions'
                    ) ?></p>
                <div class="sortable-outer">
                    <ul class="sortable-subscriptions" id="sortable">
                        <?php foreach ($model->comics as $k => $comic) {
                            if ($comic = Comic::findOne($comic['comic_id'])) { ?>

                                <li class="clearfix sortable-subscription">
                                    <span><?= $comic->title ?></span>
                                    <?= Html::a(
                                        Yii::t('app', 'Unsubscribe'),
                                        ['#'],
                                        ['class' => 'btn btn-sm btn-danger btn-unsubscribe']
                                    ) ?>
                                    <?= Html::hiddenInput('Comics[]', (String)$comic->_id) ?>
                                </li>

                            <?php }
                        } ?>
                    </ul>
                </div>

            <?php } else { ?>
                <p><?= Yii::t(
                    'app',
                    'You are currently not subscribed to any comics, pick some and return here to be able to manage them')
                ?></p>
            <?php } ?>
        </div>
        <div class="col-sm-15 col-sm-push-8">
            <?= $form->field($model, 'email_frequency')->dropDownList($model->emailFrequencies()) ?>
        </div>
    </div>
    <?= Html::submitButton(
        Yii::t('app', 'Save Subscriptions'),
        ['class' => 'btn btn-success']
    ) ?>
    <div class="row">
        <div class="col-sm-15">
            <h2>Details</h2>
            <?= $form->field($model, 'username') ?>
            <?= $form->field($model, 'email') ?>
            <?= Html::submitButton(
                Yii::t('app', 'Save Changes'),
                ['class' => 'btn btn-success']
            ) ?>
        </div>
        <div class="col-sm-15 col-sm-push-10">
            <h2>Password</h2>
            <?= $form->field($model, 'oldPassword')->passwordInput() ?>
            <?= $form->field($model, 'newPassword')->passwordInput() ?>
            <?= $form->field($model, 'confirmPassword')->passwordInput() ?>
            <?= Html::submitButton(
                Yii::t(
                    'app',
                    'Change Password'
                ),
                ['class' => 'btn btn-success']
            ) ?>
        </div>
    </div>
    <div class="panel panel-danger panel-user-delete">
        <div class="panel-heading">
            <h3 class="panel-title"><?= Yii::t('app', 'DANGER RANGER!') ?></h3>
        </div>
        <div class="panel-body">
            <p class="text-danger"><?= Yii::t('app', 'Once you tell us to delete your account it can take upto 15 days,  
                during which time you can login and your account will be reactivated') ?></p>
            <a href="<?= Url::to(['delete']) ?>" class="btn btn-danger btn-delete">Delete Your Account</a>
        </div>
    </div>

    <?php $form->end() ?>

</div>