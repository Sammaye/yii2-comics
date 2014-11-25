<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;
use common\models\Comic;

class ScraperController extends Controller
{
	public $log = [];
	
	public function log($message)
	{
		return $this->log[] = '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message."\n";
	}
	
	public function sendLog()
	{
		if(count($this->log) > 0){
			Yii::$app->getMailer()
			->compose()
			->setTextBody(implode('', $this->log))
			->setFrom([\Yii::$app->params['adminEmail'] => 'Sam Millman'])
			->setTo(\Yii::$app->params['adminEmail'])
			->setSubject('Scraper report for ' . date('d-m-Y'))
			->send();
		}
	}
	
	public function printLog()
	{
		foreach($this->log as $entry){
			echo $entry;
		}
	}
	
	public function actionCheckTimeOfNew($comic_id)
	{
		if(!($comic = Comic::find()->where(['_id' => new \MongoId($comic_id)])->one())){
			echo $this->log('That comic does not exist! Try another.');
			return 1;
		}
		
		while(true){
			
			$date = new \DateTime();
			$date->setDate(2014, 10, 18);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
			curl_setopt($ch, CURLOPT_URL, $comic->scrape_url . $date->format($comic->date_format));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$body = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			$doc = new \DOMDocument();
			libxml_use_internal_errors(true);
			$doc->loadHtml($body);
			libxml_clear_errors();
			
			$el = new \DOMXPath($doc);
			$elements = $el->query($comic->dom_path);
			
			if (!is_null($elements)) {
				foreach ($elements as $element) {
					echo "[". $element->nodeName. "]" . $element->getAttribute('src') . '\n';
				}
			}

			exit();
			/*
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $comic->scrape_url . $date->format($comic->date_format));
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true); // remove body
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$head = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if($httpCode == 200){
				$this->log("IT'S HERE IT'S HERE !!!!111");
				$this->actionGetDate($date->format('d'), $date->format('m'), $date->format('Y'), (String)$comic->_id);
				break;
			}else{
				$this->log("still not there");
				sleep(3600);
			}
			*/
			$this->printLog();
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
			if($strip = ComicStrip::find()->where(['comic_id' => new \MongoId($comic_id), 'date' => new \MongoDate($ts)])->one()){
				$this->log('Strip for ' . date('d/m/Y') . ' : ' . $strip->comic->title . ' already exists');
				$this->printLog();
				$this->sendLog();
				return 1;
			}
			
			$strip = new ComicStrip();
			$strip->date = new \MongoDate($ts);
			$strip->comic_id = $comic->_id;
			
			if($strip->comic->is_increment){
				
			}
			
			$strip->populateRemoteImage();
			if($strip->save()){
				$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' was saved successfully');
				
				$comic = $strip->comic;
				$strip->comic->last_checked = $ts;
				if(!$comic->save()){
					// Error
					$this->log('Comic: ' . (String)$comic->_id . 'could not be saved');
				}
			}else{
				$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' did not save');
			}
			
			$this->printLog();
			$this->sendLog();
		}else{
			foreach(Comic::find()->all() as $comic){
				if(ComicStrip::find()->where(['comic_id' => $comic->_id, 'date' => new \MongoDate($ts)])->one()){
					$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' already exists');
					// Don't drown us in crappy messages
					continue;
				}
				
				$strip = new ComicStrip();
				$strip->date = new \MongoDate($ts);
				$strip->comic = $comic;
				$strip->comic_id = $comic->_id;
				$strip->populateRemoteImage();
				if($strip->save()){
					$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' was saved successfully');
					
					$comic = $strip->comic;
					$strip->comic->last_checked = $ts;
					if(!$comic->save()){
						// Error
						$this->log('Comic: ' . (String)$comic->_id . 'could not be saved');
					}
					
				}else{
					$this->log('Strip for ' . date('d/m/Y') . ' : ' . $comic->title . ' did not save');
				}
			}
			
			$this->printLog();
			$this->sendLog();
		}
		return 0;
	}
}