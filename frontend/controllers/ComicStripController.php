<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\ComicStrip;
use yii\imagine\Image;

class ComicStripController extends Controller
{
	public function actionRenderImage($id)
	{
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			$image = Image::getImagine()->load($model->img->{'bin'});
			return $image->show('png');
		}
		return '';
	}
}