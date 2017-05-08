<?php

use yii\mongodb\Migration;
use common\models\User;

class m170507_100119_migrate_roles extends Migration
{
    public function up()
    {
        foreach (
            User::find()->each() as $model
        ) {
            if ($model->role === 'user') {
                $model->setRole('user');
            } elseif ($model->role === 'affiliate') {
                $model->setRole('user');
            } elseif ($model->role === 'staff') {
                $model->setRole('staff');
            } elseif ($model->role === 'admin') {
                $model->setRole('admin');
            } else {
                $model->setRole('god');
            }
        }

        User::updateAll(['$unset' => ['role' => '']]);
    }

    public function down()
    {
        echo "m170507_100119_migrate_roles cannot be reverted.\n";
        return false;
    }
}
