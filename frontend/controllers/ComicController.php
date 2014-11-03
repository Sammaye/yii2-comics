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
		
		$comic = null;
		if(!$id){
			$comic = Comic::find()->orderBy(['title' => SORT_ASC])->one();
		}
		
		if(
			(!$comic) &&
			(!($comic = Comic::find()->where(['_id' => new \MongoId($id)])->one()))
		){
			return $this->render('comicNotFound');
		}
		
		if(!$date){
			$date = date('d-m-Y');
		}
		
		$comicStrip = null;
		if(
			$date && 
			preg_match('/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\d\d$/', $date) > 0 && 
			($comicStrip = ComicStrip::find()->where(['date' => new \MongoDate(strtotime($date))])->one())
		){
			// We found our strip
		}else{
			$comicStrip = new ComicStrip();
			$comicStrip->comic_id = $comic->_id;
			$comicStrip->date = new \MongoDate(strtotime($date));
			if(!$comicStrip->populateRemoteImage() || !$comicStrip->save()){
				return $this->render('comicStripNotFound', ['model' => $comic]);
			}
		}
		
		if(
			($oldDate = new \MongoDate(strtotime("-1 day", $comicStrip->date->sec))) && 
			!($oldComicStrip = ComicStrip::find()->where(['date' => $oldDate])->one())
		){
			$nextComicStrip = new ComicStrip();
			$nextComicStrip->comic_id = $comic->_id;
			$nextComicStrip->date = new \MongoDate(strtotime("-1 day", $comicStrip->date->sec));
			if($nextComicStrip->populateRemoteImage()){
				$nextComicStrip->save();
			}
		}
		return $this->render('view', ['model' => $comic, 'comicStrip' => $comicStrip, 'date' => $date]);
	}
}