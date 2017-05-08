<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        $user = $auth->createRole('user');
        $auth->add($user);

        $staff = $auth->createRole('staff');
        $auth->add($staff);
        $auth->addChild($staff, $moderator);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $staff);

        $god = $auth->createRole('god');
        $auth->add($god);
        $auth->addChild($god, $admin);
    }
}