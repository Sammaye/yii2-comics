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
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['tier2User'],
					],
				],
			],
		];
	}
	
	public function actionSettings()
	{
		$model = Yii::$app->user->identity;
		
		if($model->load($_POST) && $model->save()){
			Yii::$app->getSession()->setFlash('success', 'Your changes have been saved.');
			return Yii::$app->getResponse()->redirect(['user/settings']);
		}
		return $this->render('settings', ['model' => $model]);
	}
	
	public function actionDelete()
	{
		if(
			($model = Yii::$app->getUser()->identity) &&
			($model->delete())
		){
			Yii::$app->getSession()->setFlash('success', 'You have been deleted.');
			return Yii::$app->getResponse()->redirect(['site/index']);
		}
		Yii::$app->getSession()->setFlash('error', 'You could not be deleted for some rason. I am unsure why. 
		You can visit our help section for assistance.');
		return Yii::$app->getResponse()->redirect(['user/settings']);
	}
}