<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use common\components\Controller;
use common\models\LoginForm;
use common\models\PasswordResetRequestForm;
use common\models\ResetPasswordForm;
use common\models\SignupForm;
use yii\filters\VerbFilter;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['staff'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['comic/index']);
    }

    public function actionLogin()
    {
        return $this->redirect(Yii::$app->frontendUrlManager->createUrl(['site/login']));
    }
}
