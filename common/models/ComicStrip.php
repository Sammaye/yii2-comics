<?php

namespace common\models;

use Yii;
use common\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use common\components\Mongo;
use common\models\Comic;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

class ComicStrip extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => 'yii\behaviors\TimestampBehavior',
				'value' => function($e){ return new UTCDateTime(time()*1000); }
			],
		];
	}

	public function rules()
	{
		$rules = [
			[['comic_id'], 'required'],
			['comic_id', 'common\components\MongoIdValidator'],
			['url', 'string', 'max' => 250],
			['skip', 'common\components\NumberValidator', 'min' => 0, 'max' => 1],

			[
				['index', 'next', 'previous'], 
				'common\components\MongoDateValidator', 
				'format' => 'php:d/m/Y',
				'when' => function($model){
					return $model->comic->type === Comic::TYPE_DATE;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . Comic::TYPE_DATE . "';
    			}"
			],
			[
				['index', 'next', 'previous'], 
				'integer',
				'when' => function($model){
					return $model->comic->type === Comic::TYPE_ID;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . Comic::TYPE_ID . "';
    			}"
			],
			[
				['index', 'next', 'previous'], 
				'filter', 
				'filter' => function($value){
					return (String)$value;
				},
				'when' => function($model){
					return $model->comic->type === Comic::TYPE_ID;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . Comic::TYPE_ID . "';
    			}"
			],
			['index', 'unique', 'targetAttribute' => ['index', 'comic_id']],
			
			[
				'date', 
				'common\components\MongoDateValidator', 
				'format' => 'php:d/m/Y'
			],
			
			[
				[
					'_id',
					'comic_id',
					'url',
					'index',
					'updated_at',
					'created_at'
				],
				'safe',
				'on' => ['search']
			],
		];
		return $rules;
	}

	public function attributes()
	{
		return [
			'_id',
			'comic_id',
			'url',
			'img',
			'index',
			'skip',
			'date',
			'next',
			'previous',
			'updated_at',
			'created_at'
		];
	}
	
	public function attributeLabels()
	{
		return [
			'skip' => 'Do not download this strip'
		];
	}
	
	public function setComic($rows)
	{
		return $this->populateRelation('comic', $rows);
	}
	
	public function getComic()
	{
		return $this->hasOne('common\models\Comic', ['_id' => 'comic_id']);
	}
	
	public function search($comic_id)
	{
		foreach($this->attributes() as $field){
			$this->$field = null;
		}
		if($get = Yii::$app->getRequest()->get('ComicStrip')){
			$this->attributes = $get;
		}

		$query = static::find();
		$query->filterWhere([
			'_id' => $this->_id ? new ObjectID($this->_id) : null,
			'comic_id' => $comic_id,
			'url' => $this->url ? new Regex($this->url) : null,
		]);
		
		if($this->comic->type === Comic::TYPE_DATE){
			$query->filterWhere([
				'index' => 
					$this->index 
					? new UTCDateTime(strtotime($this->index)*1000) 
					: null
			]);
		}elseif($this->comic->type === Comic::TYPE_ID){
			$query->filterWhere([
				'index' => $this->index ? $this->index : null
			]);
		}
		
		$query->filterWhere([
			'created_at' => 
				$this->created_at 
				? new UTCDateTime(strtotime($this->crated_at)*1000) 
				: null,
			'updated_at' => 
				$this->updated_at 
				? new UTCDateTime(strtotime($this->updated_at)*1000) 
				: null
		]);
		return new ActiveDataProvider([
			'query' => $query,
			'sort'=> ['defaultOrder' => ['index' => SORT_DESC]]
		]);
	}
}