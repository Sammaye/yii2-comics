<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\Comic;
use common\models\ComicStrip;
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
				'model' => new Comic(['scenario' => 'search'])
			]
		);
	}
	
	public function actionCreate()
	{
		$record = [];
		if(isset($_POST['type_change'])){
			$values = json_decode($_POST['type_change']);
			foreach($values as $k => $v){
				$name = preg_replace('#Comic\[#', '', rtrim($v->name, ']'));
				$record[$name] = $v->value;
			}
		}
		$model = Comic::instantiate($record);
		Comic::populateRecord($model, $record);
		$model->setOldAttributes(null);

		if($model->load($_POST)){
			if($model->save()){
				return Yii::$app->getResponse()->redirect(['comic/update', 'id' => (string)$model->_id]);
			}
		}
		
		return $this->render('create', ['model' => $model]);
	}

	public function actionUpdate($id)
	{
		if($model = Comic::find()->where(['_id' => new ObjectID($id)])->one()){
			
			$record = [];
			if(isset($_POST['type_change'])){
				$values = json_decode($_POST['type_change']);
				foreach($values as $k => $v){
					$name = preg_replace('#Comic\[#', '', rtrim($v->name, ']'));
					$record[$name] = $v->value;
				}
			}
			if($record){
				$model = Comic::instantiate($record);
				Comic::populateRecord($model, $record);
				//$model->setOldAttributes(null);
			}
			
			if($model->load($_POST)){
				if($model->save()){
					Yii::$app->getSession()->setFlash('success', 'The record was saved');
					return $this->redirect(['comic/update', 'id' => $id]);
				}
			}
			
			return $this->render('update', ['model' => $model]);
		}else{
			throw new NotFoundHttpException();
		}
	}
	
	public function actionDelete($id)
	{
		if(
			($model = Comic::find()->where(['_id' => new ObjectID($id)])->one()) && 
			($model->delete())
		){
			ComicStrip::deleteAll(['comic_id' => $model->_id]);
			
			Yii::$app->getSession()->setFlash('success', 'That comic was deleted');
			return Yii::$app->getResponse()->redirect(['comic/index']);
		}
		Yii::$app->getSession()->setFlash('error', 'That comic could not be deleted');
		return Yii::$app->getResponse()->redirect(['comic/index']);
	}
}