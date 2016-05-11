<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;
use common\models\Comic;
use common\models\User;
use yii\helpers\Url;

class ComicController extends Controller
{
	public function beforeAction($action)
	{
		Yii::$app->getUrlManager()->setBaseUrl('http://comics.sammaye.com/');
		Yii::$app->getUrlManager()->setHostInfo('http://comics.sammaye.com/');
		
		return parent::beforeAction($action);
	}

	public function actionScrape($comic_id = null)
	{
		if($comic_id){
			$comic_id = $comic_id instanceof \MongoId 
				? $comic_id 
				: new \MongoId($comic_id);
			
			if(
				$comic = Comic::find()
					->where(['_id' => new \MongoId(), 'live' => 1])
					->one()
			){
				$comic->scrapeStrip();
				return self::EXIT_CODE_NORMAL;
			}else{
				Yii::error('Could not find comic ' . (String)$comic->_id);
				return self::EXIT_CODE_ERROR;
			}
			
		}else{
			foreach(
				Comic::find()
					->where(['live' => 1])
					->each()
				as $comic
			){
				$comic->scrapeStrip();
			}
			return self::EXIT_CODE_NORMAL;
		}
	}

	public function actionEmail($freq = null)
	{
		$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		$condition = [
			'$or' => 
			[
				['last_feed_sent' => ['$lt' =>  new \MongoDate($timeToday)]], 
				['last_feed_sent' => null]
			]
		];
		
		if($freq){
			if($freq === 'day' || $freq === 'week' || $freq === 'month'){
				$condition['email_frequency'] = $freq;
			}else{
				Yii::error('Frequency must be either daily, weekly, or monthly');
			}
		}
		
		foreach(
			User::find()
				->where($condition)
				->orderBy(['_id' => SORT_ASC])
				->each() 
			as $user
		){
			if(
				$user->last_feed_sent instanceof \MongoDate && 
				$user->last_feed_sent->sec === $timeToday
			){
				continue;
			}
			$user->last_feed_sent = new \MongoDate($timeToday);
			
			$strips = [];
			
			if($user->email_frequency == 'weekly'){
				$timeAgo = strtotime('7 days ago', $timeToday);
			}elseif($user->email_frequency == 'monthly'){
				$timeAgo = strtotime('1 month ago', $timeToday);
			}else{
				$timeAgo = strtotime('1 day ago', $timeToday);
			}
			
			if(!is_array($user->comics)){
				continue;
			}
			
			foreach($user->comics as $sub){
				if(
					$comic = Comic::find()
						->where(['_id' => $sub['comic_id'], 'live' => 1])
						->one()
				){
					if($comic->active){
						$condition = [
							'comic_id' => $comic->_id, 
							'date' => ['$gt' => new \MongoDate($timeAgo)]
						];
					}else{
						$condition = [
							'comic_id' => $comic->_id, 
							'index' => $comic->current_index
						];
					}
					
					if(
						$strip = ComicStrip::find()
							->where($condition)
							->orderBy(['date' => SORT_DESC])
							->one()
					){
						$strip->comic = $comic;
						$strips[] = $strip;
					}
				}
			}
			
			// Else let's just ignore it silently
			if(!$user->save(['last_feed_sent'])){
				Yii::warning('User: ' . (String)$user->_id . ' could not seem to be saved');
			}
			
			Yii::$app->getMailer()
				->compose('comicFeed', ['strips' => $strips])
				->setFrom([\Yii::$app->params['supportEmail'] => 'Sam Millman'])
				->setTo($user->email)
				->setSubject('Your c!y Feed for ' . date('d-m-Y'))
				->send();
		}
		return self::EXIT_CODE_NORMAL;
	}

	public function actionCheckTimeOfNew($comic_id)
	{
		if(
			!(
				$comic = Comic::find()
					->where(['_id' => new \MongoId($comic_id)])
					->one()
			)
		){
			Yii::error('That comic could not be found');
			return self::EXIT_CODE_ERROR;
		}
		
		$strip = $comic->current();
		$index = $comic->next(
			$strip, 
			true
		)->index;
		
		while(true){
			if($comic->indexExist($index)){
				Yii::info('Index ' . (
					$comic->type === self::TYPE_DATE 
					? date('d-m-Y', $index->sec) 
					: $index
				) . ' now exists');
				return self::EXIT_CODE_NORMAL;
			}
			sleep(3600);
		}
	}
}