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
						'actions' => ['index', 'view', 'request', 'render-image'],
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
	
	public function actionView($id = null, $index = null)
	{
		$this->layout = 'tabbedComics';
		
		$comic = null;
		$condition = ['live' => 1];
		
		if($id){
			$condition['_id'] = new \MongoId($id);
		}
		if(
			!(
				$comic = Comic::find()
					->where($condition)
					->orderBy(['title' => SORT_ASC])
					->one()
			)
		){
			return $this->render('comicNotFound');
		}
		
		if(!$current = $comic->current($index)){
			return $this->render('comicStripNotFound', ['model' => $comic]);
		}
		$current->comic = $comic;
		$previous = $comic->previous($current);
		$next = $comic->next($current);

		return $this->render(
			'view', 
			[
				'model' => $comic, 
				'comicStrip' => $current, 
				'previousStrip' => $previous,
				'nextStrip' => $next,
			]
		);
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
	
	public function actionRenderImage($id)
	{
		return Comic::renderStripImage($id);
	}
}