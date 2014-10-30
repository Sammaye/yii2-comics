<?php

namespace frontend\controllers;

use Yii;
use common\components\Controller;
use common\models\Comic;
use common\models\ComicStrip;

class ComicController extends Controller
{
	public function actionIndex()
	{
		
	}
	
	public function actionView($id = null, $date = null)
	{
		if(!$id){
			return Yii::$app->getResponse()->redirect(['comic/index']);
		}
		
		if(!($comic = Comic::find()->where(['_id' => new \MongoId($id)])->one())){
			return $this->render('comicNotFound');
		}
		
		$comicStrip = null;
		if(
			$date && 
			preg_match('/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\d\d$/', $date) > 0 && 
			($comicStrip = ComicStrip::find()->where(['date' => new \MongoDate(strtotime($date))])->one())
		){
			// We found our strip
		}elseif(!$date){
			// No date, get latest
			$comicStrip = ComicStrip::find()->where(['comic_id' => $comic->_id])->orderBy(['date' => SORT_DESC])->one();
		}else{
			return $this->render('comicStripNotFound', ['model' => $comic]);
		}
		
		return $this->render('view', ['model' => $comic, 'comicStrip' => $comicStrip]);
	}
}