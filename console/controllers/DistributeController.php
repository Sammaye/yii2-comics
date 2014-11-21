<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\controllers\ScraperController;
use common\models\ComicStrip;
use common\models\Comic;
use common\models\User;
use yii\helpers\Url;

class DistributeController extends Controller
{
	public $log = [];
	public $userErrors = [];
	public $comicErrors = [];
	
	public function logUserError($message)
	{
		return $this->userErrors = $this->log('USER ERROR: ' . $message);
	}
	
	public function logComicError($message)
	{
		return $this->comicErrors = $this->log('COMIC ERROR: ' . $message);
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
				->setTo($user->email)
				->setSubject('User Errors for the comic Feed for ' . date('d-m-Y'))
				->send();
		}
		
		if(count($this->comicErrors) > 0){
			Yii::$app->getMailer()
				->compose()
				->setTextBody(implode('', $this->comicErrors))
				->setFrom([\Yii::$app->params['adminEmail'] => 'Sam Millman'])
				->setTo($user->email)
				->setSubject('Comic Errors for the comic Feed for ' . date('d-m-Y'))
				->send();
		}
	}
	
	public function actionEmail()
	{
		Yii::$app->getUrlManager()->setBaseUrl('http://frontend/');
		Yii::$app->getUrlManager()->setHostInfo('http://frontend/');
	
		foreach(User::find()->orderBy(['_id' => SORT_ASC])->each() as $user){
			
			$comics = [];
			$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
			
			if($user->last_feed_sent == $timeToday){
				continue;
			}
			
			//$user->last_feed_sent = $timeToday;
			
			foreach($user->comics as $sub){
				if($comic = Comic::find()->where(['_id' => $sub['comic_id']])->one()){
					if($comic->last_checked != $timeToday){
						// try and scrape one
						$strip = new ComicStrip();
						$strip->date = new \MongoDate($timeToday);
						$strip->comic_id = $comic->_id;
						if(!$strip->populateRemoteImage() || !$strip->save()){
							// Error
							$this->logComicError('Comic: ' . (String)$comic->_id . ' with strip: ' . date('d-m-Y') . ' could not be saved');
						}
					}
					$comic->last_checked = $timeToday;
					
					if($strip = ComicStrip::find()->where(['comic_id' => $comic->_id])->orderBy(['date' => SORT_DESC])->one()){
						$strip->comic = $comic;
						$comics[] = $strip;
					}
					
					if(!$comic->save()){
						// Error
						$this->logComicError('Comic: ' . (String)$comic->_id . 'could not be saved');
					}
				}
				// Else let's just ignore it silently
				
				if(!$user->save()){
					$this->logUserError('User: ' . (String)$user->_id . ' could not seem to be saved');
				}
			}
			
			if(count($comics) > 0){
				//echo "worked";
				Yii::$app->getMailer()
					->compose('comicFeed', ['comics' => $comics])
					->setFrom([\Yii::$app->params['supportEmail'] => 'Sam Millman'])
					->setTo($user->email)
					->setSubject('Your c!y Feed for ' . date('d-m-Y'))
					->send();
			}
		}
		
		$this->sendLog();
		return 0;
	}
	
	public function actionFacebook()
	{
		
	}
}