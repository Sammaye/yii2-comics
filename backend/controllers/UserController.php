<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\User;
use common\models\SignupForm;

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
				'model' => new User(['scenario' => 'search'])
			]
		);
	}
	
	public function actionCreate()
	{
		$model = new SignupForm();
		if ($model->load(Yii::$app->request->post())) {
			if ($user = $model->signup()) {
				return Yii::$app->getResponse()->redirect(['user/update', 'id' => (string)$user->_id]);
			}
		}
		return $this->render('create', ['model' => $model]);
	}

	public function actionUpdate($id)
	{
		if($model = User::find()->where(['_id' => new \MongoId($id)])->one()){
			
			if($model->load($_POST)){
				if($model->save()){
					Yii::$app->getSession()->setFlash('success', 'The record was saved');
					return $this->redirect(['user/update', 'id' => $id]);
				}
			}
			
			return $this->render('update', ['model' => $model]);
		}else{
			throw new NotFoundHttpException();
		}
	}
	
	public function actionToggleLogin($id)
	{
		if($model = User::find()->where(['_id' => new \MongoId($id)])->one()){
			if($model->status > 0){
				$model->status = 0;
			}else{
				$model->status = 10;
			}
			if($model->save()){
				if($model->status > 0){
					Yii::$app->getSession()->setFlash('success', 'This user is now allowed to login');
				}else{
					Yii::$app->getSession()->setFlash('success', 'This user was banned from logging in');
				}
			}
		}else{
			Yii::$app->getSession()->setFlash('error', 'This user could not be banned from logging in');
		}
		return $this->redirect(['user/update', 'id' => $id]);
	}

	public function actionDelete($id)
	{
		if(
			($model = User::find()->where(['_id' => new \MongoId($id)])->one()) && 
			($model->delete())
		){
			
			Yii::$app->getSession()->setFlash('success', 'That user was deleted');
			return Yii::$app->getResponse()->redirect(['user/index']);
		}
		Yii::$app->getSession()->setFlash('error', 'That user could not be deleted');
		return Yii::$app->getResponse()->redirect(['user/index']);
	}
}