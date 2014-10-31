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
		return $this->actionView();
	}
	
	public function actionView($id = null, $date = null)
	{
		$this->layout = 'tabbedComics';
		
		if(!$id){
			$comic = Comic::find()->orderBy(['title' => SORT_ASC])->one();
		}
		
		if(
			(!$comic) &&
			(!($comic = Comic::find()->where(['_id' => new \MongoId($id)])->one()))
		){
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
			$comicStrip = new ComicStrip();
			$comicStrip->comic_id = $comic->_id;
			$comicStrip->date = date($coomic->date_format, strtotime($date));
			if(!$comicStrip->populateRemoteImage() && !$comicStrip->save()){
				return $this->render('comicStripNotFound', ['model' => $comic]);
			}
		}
		
		return $this->render('view', ['model' => $comic, 'comicStrip' => $comicStrip]);
	}
}