<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Json;
use common\models\Comic;
use MongoDB\BSON\ObjectID;

class ComicController extends Controller
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
                'model' => new Comic(['scenario' => Comic::SCENARIO_SEARCH])
            ]
        );
    }

    public function actionCreate()
    {
        $record = [];
        if ($typeChange = Json::decode(Yii::$app->request->post('type_change', []))) {
            foreach ($typeChange as $k => $v) {
                $name = preg_replace('#Comic\[#', '', rtrim($v->name, ']'));
                $record[$name] = $v->value;
            }
        }
        if ($record) {
            $model = Comic::instantiate($record);
            Comic::populateRecord($model, $record);
            $model->setOldAttributes(null);
        }

        if ($model->load($_POST) && $model->save()) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('app', 'Comic created')
            );
            return $this->redirect(['comic/update', 'id' => (string)$model->_id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        $record = [];
        if ($typeChange = Json::decode(Yii::$app->request->post('type_change', []))) {
            foreach ($typeChange as $k => $v) {
                $name = preg_replace('#Comic\[#', '', rtrim($v->name, ']'));
                $record[$name] = $v->value;
            }
        }
        if ($record) {
            $model = Comic::instantiate($record);
            Comic::populateRecord($model, $record);
            //$model->setOldAttributes(null);
        }

        if ($model->load($_POST) && $model->save()) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('app', 'Comic updated')
            );
            return $this->redirect(['update', 'id' => $id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->loadModel($id);
        if ($model->delete()) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('app', 'Comic deleted')
            );
            return $this->redirect(['index']);
        }
        Yii::$app->getSession()->setFlash(
            'error',
            Yii::t('app', 'Unknown error')
        );
        return $this->redirect(['index']);
    }

    public function loadModel($id)
    {
        $model = Comic::findOne(new ObjectId($id));
        if ($model === null) {
            throw new NotFoundHttpException();
        }
        return $model;
    }
}