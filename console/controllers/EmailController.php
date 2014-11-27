<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\controllers\ScraperController;
use common\models\ComicStrip;
use common\models\Comic;
use common\models\User;
use yii\helpers\Url;

class EmailController extends Controller
{
	public $log = [];
	public $userErrors = [];
	public $comicErrors = [];
	
	public function beforeAction($action)
	{
		Yii::$app->getUrlManager()->setBaseUrl('http://comics.sammaye.com/');
		Yii::$app->getUrlManager()->setHostInfo('http://comics.sammaye.com/');
		
		return parent::beforeAction($action);
	}
	
	public function logUserError($message)
	{
		return $this->userErrors[] = $this->log('USER ERROR: ' . $message);
	}
	
	public function logComicError($message)
	{
		return $this->comicErrors[] = $this->log('COMIC ERROR: ' . $message);
	}
	
	public function log($message)
	{
		return $this->log[] = '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message."\n";
	}
	
	public function sendLog()
	{
		if(count($this->userErrors) > 0){
			Yii::$app->getMailer()
				->compose()
				->setTextBody(implode('', $this->userErrors))
				->setFrom([\Yii::$app->params['adminEmail'] => 'Sam Millman'])
				->setTo(\Yii::$app->params['adminEmail'])
				->setSubject('User Errors for the comic Feed for ' . date('d-m-Y'))
				->send();
		}
		
		if(count($this->comicErrors) > 0){
			Yii::$app->getMailer()
				->compose()
				->setTextBody(implode('', $this->comicErrors))
				->setFrom([\Yii::$app->params['adminEmail'] => 'Sam Millman'])
				->setTo(\Yii::$app->params['adminEmail'])
				->setSubject('Comic Errors for the comic Feed for ' . date('d-m-Y'))
				->send();
		}
	}
	
	public function sendComicMail($comics, $user)
	{
		if(count($comics) > 0){
			return Yii::$app->getMailer()
				->compose('comicFeed', ['comics' => $comics])
				->setFrom([\Yii::$app->params['supportEmail'] => 'Sam Millman'])
				->setTo($user->email)
				->setSubject('Your c!y Feed for ' . date('d-m-Y'))
				->send();
		}
		return true;
	}
	
	public function getTodaysComic($comic)
	{
		$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
	
		if(
			!$comic->is_increment && 
			($strip = ComicStrip::find()->where([
				'comic_id' => $comic->_id,
				'date' => new \MongoDate($timeToday)
			])->one())
		){
			return true;
		}
	
		if($comic->last_checked != $timeToday){
			// try and scrape one
			$strip = new ComicStrip();
			$strip->date = new \MongoDate($timeToday);
			$strip->comic_id = $comic->_id;
			$strip->comic = $comic;
			
			if($comic->is_increment){
				
				$lastStrip = ComicStrip::find()
					->where(['comic_id' => $comic->_id])
					->orderBy(['inc_id' => SORT_DESC])
					->one();
				
				$strip->inc_id = isset($lastStrip) ? $lastStrip->inc_id + 1 : $comic->inc_at_create;
			}
			
			if(!$strip->populateRemoteImage() || !$strip->save()){
				// Error
				$this->logComicError('Comic: ' . (String)$comic->_id . ' with strip: ' . date('d-m-Y') . ' could not be saved');
				return false;
			}
		}
		
		$comic->last_checked = $timeToday;
		
		if(!$comic->save()){
			// Error
			$this->logComicError('Comic: ' . (String)$comic->_id . 'could not be saved');
			return false;
		}
		return true;
	}
	
	public function actionDaily()
	{
		foreach(User::find()->where(['email_frequency' => 'daily'])->orderBy(['_id' => SORT_ASC])->each() as $user){
			
			$comics = [];
			$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

			if($user->last_feed_sent == $timeToday){
				continue;
			}

			$user->last_feed_sent = $timeToday;
			
			foreach($user->comics as $sub){
				if($comic = Comic::find()->where(['_id' => $sub['comic_id']])->one()){
					$this->getTodaysComic($comic);
					
					if($strip = ComicStrip::find()->where(['comic_id' => $comic->_id])->orderBy(['date' => SORT_DESC])->one()){
						$strip->comic = $comic;
						$comics[] = $strip;
					}
				}
				// Else let's just ignore it silently
				if(!$user->save()){
					$this->logUserError('User: ' . (String)$user->_id . ' could not seem to be saved');
				}
			}
			$this->sendComicMail($comics, $user);
		}
		$this->sendLog();
		return 0;
	}
	
	public function actionWeekly()
	{
		return $this->actionAgo();
	}
	
	public function actionMonthly()
	{
		return $this->actionAgo('monthly');
	}
	
	public function actionAgo($period = 'weekly')
	{
		if($period != 'weekly' && $period != 'monthly'){
			echo "You can only get periods of either weekly or monthly currently";
			return 1;
		}
		
		foreach(User::find()->where(['email_frequency' => $period])->orderBy(['_id' => SORT_ASC])->each() as $user){
				
			$comics = [];
			$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
			
			if($period == 'weekly'){
				$timeAgo = strtotime('7 days ago', $timeToday);
			}elseif($period == 'monthly'){
				$timeAgo = strtotime('1 month ago', $timeToday);
			}
				
			if($user->last_feed_sent == $timeToday){
				continue;
			}
				
			$user->last_feed_sent = $timeToday;
				
			foreach($user->comics as $sub){
				if($comic = Comic::find()->where(['_id' => $sub['comic_id']])->one()){
					$this->getTodaysComic($comic);
					
					foreach(
						ComicStrip::find()->where(['comic_id' => $comic->_id])->orderBy(['date' => SORT_DESC])->each() 
						as $strip
					){
						if($strip->date->sec > $timeAgo){
							$strip->comic = $comic;
							$comics[] = $strip;
						}else{
							break; // reached end
						}
					}
				}
				// Else let's just ignore it silently
				if(!$user->save()){
					$this->logUserError('User: ' . (String)$user->_id . ' could not seem to be saved');
				}
			}
			$this->sendComicMail($comics, $user);
		}
		$this->sendLog();
		return 0;
	}
}