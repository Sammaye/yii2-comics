<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;
use common\models\Comic;

class ScraperController extends Controller
{
	public function log($message)
	{
		return '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message."\n";
	}
	
	public function actionCheckTimeOfNew($comic_id)
	{
		if(!($comic = Comic::find()->where(['id' => new \MongoId($comic_id)])->one())){
			echo $this->log('That comic does not exist! Try another.');
			return 1;
		}
		
		while(true){
			
			$date = new \DateTime();
			$date->setDate(2014, 10, 18);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $comic->scrape_url . $date->format($comic->date_format));
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true); // remove body
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$head = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if($httpCode == 200){
				echo $this->log("IT'S HERE IT'S HERE !!!!111");
				$this->actionGetDate($date->format('d'), $date->format('m'), $date->format('Y'), (String)$comic->_id);
				break;
			}else{
				echo $this->log("still not there");
				sleep(3600);
			}
		}
	}
	
	public function actionGetToday($comic_id = null)
	{
		$date = new \DateTime();
		$date->setDate(date('Y'), date('m'), date('d'));
		$ts = mktime(0, 0, 0, $date->format('m'), $date->format('d'), $date->format('Y'));
		return $this->get($ts, $comic_id);
	}

	public function actionGetDate($day, $month, $year, $comic_id = null)
	{
		$date = new \DateTime();
		$date->setDate($year, $month, $day);
		$ts = mktime(0, 0, 0, $date->format('m'), $date->format('d'), $date->format('Y'));
		return $this->get($tds, $comic_id);
	}
	
	private function get($ts, $comic_id = null)
	{
		if($comic_id){
			if(ComicStrip::find()->where(['comic_id' => new \MongoId($comic_id), 'date' => new \MongoDate($ts)])->one()){
				echo $this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' already exists');
				return 1;
			}
			
			$strip = new ComicStrip();
			$strip->date = new \MongoDate($ts);
			$strip->populateRemoteImage();
			if($strip->save()){
				$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' was saved successfully');
				
				$comic = $strip->comic;
				$strip->comic->last_checked = $ts;
				if(!$comic->save()){
					// Error
					$this->logComicError('Comic: ' . (String)$comic->_id . 'could not be saved');
					return false;
				}
			}else{
				$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' did not save');
			}
			
			
		}else{
			foreach(Comic::find()->all() as $comic){
				if(ComicStrip::find()->where(['comic_id' => $comic->_id, 'date' => new \MongoDate($ts)])->one()){
					// Don't drown us in crappy messages
					continue;
				}
				
				$strip = new ComicStrip();
				$strip->date = new \MongoDate($ts);
				$strip->populateRemoteImage();
				if($strip->save()){
					echo $this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' was saved successfully');
					
					$comic = $strip->comic;
					$strip->comic->last_checked = $ts;
					if(!$comic->save()){
						// Error
						$this->logComicError('Comic: ' . (String)$comic->_id . 'could not be saved');
						return false;
					}
					
				}else{
					echo $this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' did not save');
				}
			}
		}
		return 0;
	}
}