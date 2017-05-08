<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\User;
use MongoDB\BSON\ObjectID;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['staff'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render(
            'index',
            [
                'model' => new User(['scenario' => User::SCENARIO_SEARCH])
            ]
        );
    }

    public function actionCreate()
    {
        $model = new User(['scenario' => User::SCENARIO_ADMIN]);
        if ($model->load($_POST) && $model->validate()) {

            if ($model->role) {
                $model->setRole($model->role);
            }
            $model->password = $model->adminSetPassword;
            $model->generateAuthKey();

            if ($model->save(false)) {
                Yii::$app->session->setFlash(
                    'success',
                    Yii::t(
                        'app',
                        "{username} was created",
                        ['username' => $model->username]
                    )
                );
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if ($model->load($_POST) && $model->validate()) {

            if ($model->adminSetPassword) {
                $model->password = $model->adminSetPassword;
            }
            if ($model->role) {
                $model->setRole($model->role);
            }

            if ($model->save(false)) {
                Yii::$app->session->setFlash(
                    'success',
                    Yii::t(
                        'app',
                        "{username} was updated",
                        ['username' => $model->username]
                    )
                );
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->loadModel($id);
        if (!$model) {
            throw new NotFoundHttpException();
        }
        if (!$model->delete()) {
            throw new ServerErrorHttpException();
        }
        if (Yii::$app->request->isAjax) {
            return json_encode([
                'success' => true,
                'message' => Yii::t(
                    'app',
                    "{username} was deleted",
                    ['username' => $model->username]
                )
            ]);
        }
        Yii::$app->session->setFlash(
            'success',
            Yii::t(
                'app',
                "{username} was deleted",
                ['username' => $model->username]
            )
        );
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionLoginAs($id)
    {
        $next = Yii::$app->request->get('next', ['site/index']);
        if (Yii::$app->user->can('admin')) {

            if (!$user = User::findOne($id)) {
                throw new NotFoundHttpException(
                    Yii::t(
                        'app',
                        "User #$id not found",
                        ['id' => $id]
                    )
                );
            }

            Yii::$app->user->switchIdentity($user);

            return $this->redirect(
                Yii::$app->frontendUrlManager->createUrl($next)
            );
        }
        throw new ForbiddenHttpException();
    }

    public function loadModel($id)
    {
        $model = User::findOne((int)$id);
        if ($model === null) {
            throw new NotFoundHttpException();
        }
        return $model;
    }
}