<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use common\components\Controller;
use common\models\Comic;
use common\models\ComicStrip;
use common\models\User;
use common\models\RequestComicForm;

class ComicController extends Controller
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'actions' => ['subscribe', 'unsubscribe'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['index', 'view', 'request'],
						'roles' => ['?', '@']
					]
				],
			],
		];
	}
	
	
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
		
		if($comic->is_increment){
			
			$modelDate = new \MongoDate(mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$date = (int)$date;
			if(
				!$date && 
				$comicStrip = ComicStrip::find()->where(['comic_id' => $comic->_id])->orderby(['inc_id' => SORT_DESC])->one()
			){
				$date = $comicStrip->inc_id;
			}elseif(
				!$date || 
				(
					($comicStrip = ComicStrip::find()->where(['comic_id' => $comic->_id, 'inc_id' => $date])->one()) === null && 
					$date
				)
			){
				// then make a new strip
				$comicStrip = new ComicStrip();
				$comicStrip->date = $modelDate;
				$comicStrip->comic_id = $comic->_id;
				$comicStrip->inc_id = $date ?: $comic->inc_at_create;
				if(!$comicStrip->populateRemoteImage() || !$comicStrip->save()){
					return $this->render('comicStripNotFound', ['model' => $comic]);
				}
				$date = $comicStrip->inc_id;
			}

			if(
				($beforeStrip = ComicStrip::find()->where(['comic_id' => $comic->_id, 'inc_id' => $date - 1])->one()) === null && 
				($date - 1 > 0)
			){
				$beforeStrip = new ComicStrip();
				$beforeStrip->comic_id = $comic->_id;
				$beforeStrip->date = $modelDate;
				$beforeStrip->inc_id = $date - 1;
				if($beforeStrip->populateRemoteImage()){
					$beforeStrip->save();
				}
			}
			
			if(
				($afterStrip = ComicStrip::find()->where(['comic_id' => $comic->_id, 'inc_id' => $date + 1])->one()) === null &&
				($date - 1 > 0)
			){
				$afterStrip = new ComicStrip();
				$afterStrip->comic_id = $comic->_id;
				$afterStrip->date = $modelDate;
				$afterStrip->inc_id = $date + 1;
				if($afterStrip->populateRemoteImage()){
					$afterStrip->save();
				}
			}
		}else{
			if(!$date){
				$date = date('d-m-Y');
			}

			if(
				strtotime(date('d-m-Y 9:00:00')) > time() && 
				strtotime($date) === mktime(0, 0, 0, date('m'), date('d'), date('Y'))
			){
				$date = date('d-m-Y', mktime(0, 0, 0, date('m'), date('d') -1, date('Y')));
			}
			
			$comicStrip = null;
			if(
				$date && 
				preg_match('/^(0[1-9]|[12][0-9]|3[01])[-](0[1-9]|1[012])[-](19|20)\d\d$/', $date) > 0 && 
				($comicStrip = ComicStrip::find()->where(['comic_id' => $comic->_id, 'date' => new \MongoDate(strtotime($date))])->one())
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
				!($oldComicStrip = ComicStrip::find()->where(['comic_id' => $comic->_id, 'date' => $oldDate])->one())
			){
				$nextComicStrip = new ComicStrip();
				$nextComicStrip->comic_id = $comic->_id;
				$nextComicStrip->date = new \MongoDate(strtotime("-1 day", $comicStrip->date->sec));
				if($nextComicStrip->populateRemoteImage()){
					$nextComicStrip->save();
				}
			}
		}
		
		$comicStrip->comic = $comic;
		
		return $this->render('view', ['model' => $comic, 'comicStrip' => $comicStrip, 'date' => $date]);
	}
	
	public function actionSubscribe()
	{
		if(
			($comic_id = Yii::$app->getRequest()->get('comic_id')) && 
			($model = Comic::find()->where(['_id' => new \MongoId($comic_id)])->one())
		){
			$user = Yii::$app->user->identity;
			if(User::updateAll(
				[
					'$push' => [
						'comics' => [
							'date' => new \MongoDate(),
							'comic_id' => $model->_id
						]
					]
				],
				['_id' => $user->_id, 'comics.comic_id' => ['$ne' => $model->_id]]
			)){
				return json_encode(['success' => true, 'message' => 'You are now subscribed']);
			}
			
			foreach($user->comics as $comic){
				if((String)$comic['comic_id'] === (String)$model->_id){
					return json_encode(['success' => false, 'message' => 'You are already subscribed']);
				}
			}
			return json_encode(['success' => false, 'message' => 'There was an unknown error']);
		}
		return json_encode(['success' => false, 'message' => 'That comic does not exist']);
	}
	
	public function actionUnsubscribe()
	{
		if(
			($comic_id = Yii::$app->getRequest()->get('comic_id')) &&
			($model = Comic::find()->where(['_id' => new \MongoId($comic_id)])->one())
		){
			$user = Yii::$app->user->identity;
			if(User::updateAll(
				[
					'$pull' => [
						'comics' => ['comic_id' => $model->_id]
					]
				],
				['_id' => $user->_id]
			)){
				return json_encode(['success' => true, 'message' => 'You are now unsubscribed']);
			}
				
			foreach($user->comics as $comic){
				if((String)$comic['comic_id'] === (String)$model->_id){
					return json_encode(['success' => false, 'message' => 'There was an unknown error']);
				}
			}
			return json_encode(['success' => false, 'message' => 'You are already unsubscribed']);
		}
		return json_encode(['success' => false, 'message' => 'That comic does not exist']);
	}
	
	public function actionRequest()
	{
		$model = new RequestComicForm;
		if($model->load($_POST) && $model->validate()){
			/// send email
			\Yii::$app->mail->compose('requestComic', ['model' => $model])
				->setFrom([\Yii::$app->params['supportEmail'] => 'Sam Millman'])
				->setTo(\Yii::$app->params['adminEmail'])
				->setSubject('Comic Request for c!y')
				->send();
			return json_encode(['success' => true]);
		}else{
			return json_encode(['success' => false, 'errors' => $model->getErrors()]);
		}
	}
}