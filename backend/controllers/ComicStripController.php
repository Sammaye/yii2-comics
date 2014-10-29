<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\ComicStrip;
use yii\imagine\Image;

class ComicStripController extends Controller
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

	public function actionCreate()
	{
		$model = new ComicStrip;
		
		if(
			($comic_id = Yii::$app->getRequest()->get('comic_id')) && 
			($comic_id = new \MongoId($comic_id))
		){
			$model->comic_id = $comic_id;
		}
		
		if($model->load($_POST)){
			if($model->validate()){
				// This currently done without care as to its outcome
				$model->populateRemoteImage();
				if($model->save(false)){
					return Yii::$app->getResponse()->redirect(['comic/update', 'id' => (string)$model->comic_id]);
				}
			}
		}
		return $this->render('create', ['model' => $model]);
	}

	public function actionUpdate($id)
	{
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			
			if($model->load($_POST) && $model->save()){
				return Yii::$app->getResponse()->redirect(['comic/update', 'id' => $model->comic_id]);
			}
			return $this->render('update', ['model' => $model]);
		}else{
			throw new NotFoundHttpException();
		}
	}
	
	public function actionDelete($id)
	{
		if(
			($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()) && 
			$model->delete()
		){
			Yii::$app->getSession()->setFlash('success', 'That strip was deleted');
		}else{
			Yii::$app->getSession()->setFlash('error', 'That strip could not be deleted');
		}
		return Yii::$app->getResponse()->redirect(['comic/update', 'id' => (String)$model->comic_id]);
	}
	
	public function actionRefreshImage($id)
	{
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			$model->url = null;
			$model->img = null;
			if($model->populateRemoteImage()){
				Yii::$app->getSession()->setFlash('success', 'The image for this strip was refreshed');
				return Yii::$app->getResponse()->redirect(['comic-strip/update', 'id' => $id]);
			}
		}
		Yii::$app->getSession()->setFlash('error', 'The image for this strip was not refreshed');
		return Yii::$app->getResponse()->redirect(['comic-strip/update', 'id' => $id]);		
	}
	
	public function actionRenderImage($id)
	{
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			$image = Image::getImagine()->load($model->img->{'bin'});
			return $image->show('png');
		}
		return '';
	}
}