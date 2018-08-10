<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionUpdate()
    {
        $model = Yii::$app->user->identity;

        if (
            $model->load($_POST) &&
            $model->modifyComics(Yii::$app->getRequest()->post('Comics')) &&
            $model->save()
        ) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t(
                    'app',
                    'Your changes have been saved.'
                )
            );
            return $this->redirect(['update']);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete()
    {
        $model = Yii::$app->user->identity;
        if ($model->queueForDelete()) {
            Yii::$app->user->logout();
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'Your account has been deleted')
            );
            return $this->redirect(['site/login']);
        }

        Yii::$app->session->setFlash(
            'error',
            Yii::t('app', 'Could not delete your account')
        );
        return $this->redirect(['update']);
    }
}
