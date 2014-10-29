<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Comic extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => 'yii\behaviors\TimestampBehavior',
				'value' => function($e){ return new \MongoDate(); }
			],
		];
	}

	public function rules()
	{
		return [
			['title', 'required'],
			['slug', 'string', 'max' => 250],
			['description', 'string', 'max' => 1500],
			['abstract', 'string', 'max' => 250],
			['scrape_url', 'string', 'max' => 250],
			['homepage', 'url'],
			['date_format', 'validateDateFormat'],
			['date_format', 'default', 'value' => 'Y-m-d'],
			[
				[
					'_id', 
					'title', 
					'slug', 
					'description', 
					'abstract', 
					'updated_at', 
					'created_at'
				], 
				'safe', 
				'on' => 'search'
			]
		];
	}
	
	public function attributes()
	{
		return [
			'_id',
			'title',
			'slug',
			'description',
			'abstract',
			'scrape_url',
			'homepage',
			'date_format',
			'updated_at',
			'created_at'
		];
	}
	
	public function beforeSave($insert)
	{
		$this->slug = Inflector::slug($this->title);
		if($this->isAttributeChanged('description') || $insert){
			$this->abstract = StringHelper::truncate($this->description, 150);
		}
		return parent::beforeSave($insert);
	}
	
	public function validateDateFormat($attribute, $params)
	{
		if(
			preg_match('/[d]/i', $this->$attribute) <= 0 || 
			preg_match('/[m]/i', $this->$attribute) <= 0 || 
			preg_match('/[y]/i', $this->$attribute) <= 0
		){
			$this->addError($attribute, 'The date format must be valid to PHP standards.');
		}
	}
	
	public function getStrips()
	{
		return $this->hasMany('common\models\ComicStrip', ['comic_id' => '_id']);
	}
	
	public function search()
	{
		foreach($this->attributes() as $field){
			$this->$field = null;
		}
		if($get = Yii::$app->getRequest()->get('Comic')){
			$this->attributes = $get;
		}
		
		$query = static::find();
		$query->filterWhere([
			'_id' => $this->_id ? new \MongoId($this->_id) : null,
			'title' => $this->title ? new \MongoRegex("/$this->title/") : null,
			'slug' => $this->slug ? new \MongoRegex("/$this->slug/") : null,
			'description' => $this->description ? new \MongoRegex("/$this->description/") : null,
			'abstract' => $this->abstract ? new \MongoRegex("/$this->abstract/") : null,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at
		]);
		
		return new ActiveDataProvider([
			'query' => $query
		]);
	}
}