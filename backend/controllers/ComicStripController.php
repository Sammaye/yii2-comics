<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\ComicStrip;

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
						'roles' => ['@'],
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
				'model' => new ComicStrip(['scenario' => 'search'])
			]
		);
	}
	
	public function actionCreate()
	{
		$model = new ComicStrip;
		if($model->load($_POST)){
			if($model->save()){
				return Yii::$app->getResponse()->redirect(['comic/update', 'id' => (string)$model->_id]);
			}
		}
		return $this->render('create', ['model' => $model]);
	}

	public function actionUpdate($id)
	{
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			return $this->render('update', ['model' => $model]);
		}else{
			throw new NotFoundHttpException();
		}
	}
	
	public function actionDelete($id)
	{
		if(ComicStrip::deleteAll('id=:id', [':id' => $id])){
			return Yii::$app->getResponse()->redirect(['index']);
		}else{
			// Prolly show an error
		}
	}
}