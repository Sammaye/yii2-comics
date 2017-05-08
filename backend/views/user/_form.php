<?php
use common\models\User;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="form">
    <?php $form = ActiveForm::begin(['id' => 'user-form']); ?>
    <div class="row">
        <div class="col-sm-24">
            <?= $form->field($model, 'username') ?>
            <?= $form->field($model, 'email') ?>
            <?= $form->field($model, 'adminSetPassword') ?>
        </div>
        <div class="col-sm-24">
            <?=
            $form->field($model, 'status')->dropdownList([
                User::STATUS_ACTIVE => 'Active',
                User::STATUS_BANNED => 'Banned',
                User::STATUS_DELETED => 'Deleted'
            ])
            ?>
            <?php

            $rolesResult = Yii::$app->authManager->getRoles();
            $roles = [];
            foreach ($rolesResult as $v) {
                $roles[$v->name] = $v->name;
            }

            $model->role = Yii::$app->authManager->getAssignments($model->id)[0]->roleName;

            echo $form->field($model, 'role')->dropdownList($roles)
            ?>
        </div>
    </div>
    <div>
        <?= Html::submitButton(
            Yii::t(
                'app',
                $model->getIsNewRecord() ? 'Create' : 'Save'
            ),
            ['class' => 'btn btn-success']
        ) ?>
        <?php if (!$model->getIsNewRecord()) { ?>
            <?php if (Yii::$app->user->can('admin')) { ?>
                <a href="<?= Url::to(['login-as', 'id' => $model->id]) ?>" class="btn btn-default">
                    <?= Yii::t('app', 'Login As...') ?>
                </a>
            <?php } ?>
            <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" class="btn btn-danger">
                <?= Yii::t('app', 'Delete') ?>
            </a>
        <?php } ?>
    </div>
    <?php $form->end() ?>
</div>