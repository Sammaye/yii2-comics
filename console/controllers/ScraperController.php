<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;

class ScraperController extends Controller
{
	public function log($message)
	{
		return '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message."\n";
	}
	
	public function actionCheckTimeOfNew()
	{
		while(true){
			
			$date = new \DateTime();
			$date->setDate(2014, 10, 18);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://garfield.com/comic/' . $date->format('Y-m-d'));
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true); // remove body
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$head = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if($httpCode == 200){
				echo $this->log("IT'S HERE IT'S HERE !!!!111");
				$this->actionGet($date->format('Y'), $date->format('m'), $date->format('d'));
				break;
			}else{
				echo $this->log("still not there");
				sleep(3600);
			}
		}
	}
	
	public function actionGet($year = 2014, $month = 10, $day = 17)
	{
		$date = new \DateTime();
		$date->setDate($year, $month, $day);
		$ts = mktime(0, 0, 0, $date->format('m'), $date->format('d'), $date->format('Y'));
		
		if(ComicStrip::find()->where(['date' => new \MongoDate($ts)])->one()){
			return;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
		curl_setopt($ch, CURLOPT_URL, 'http://garfield.com/comic/' . $date->format('Y-m-d'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$body = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$doc = new \DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHtml($body);
		libxml_clear_errors();
		
		$el = $doc->getElementById('comic_wrap');
		
		$url = null;
		foreach($el->childNodes as $child){
			if($child instanceof \DOMElement && $child->tagName == 'img'){
				$url = $child->getAttribute('src');
			}
		}
		
		if(!$url){
			echo $this->log("ERROR! Could not find the image for some reason...");
		}
		
		$strip = new ComicStrip();
		$strip->date = new \MongoDate($ts);
		$strip->url = $url;
		$strip->img = new \MongoBinData(file_get_contents($url));
		
		if(!$strip->save()){
			echo $this->log("ERROR! model could not save because: " . var_export($strip->getErrors(), true));
		}
		
		echo $this->log("SUCCESS! Strip was downloaded");
	}
}