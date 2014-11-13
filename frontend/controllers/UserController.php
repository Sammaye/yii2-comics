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
						'roles' => ['@'],
					],
				],
			],
		];
	}
	
	public function actionSettings()
	{
		$model = Yii::$app->user->identity;
		if($model->load($_POST)){
			
		}
		
		return $this->render('settings', ['model' => $model]);
	}
}