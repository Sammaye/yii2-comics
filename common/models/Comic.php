<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\data\ActiveDataProvider;
use MongoRegex;

class Comic extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => 'yii\behaviors\TimestampBehavior'
			],
		];
	}
	
	/*
	public function collectionName()
	{
		return 'strips';'
	}
	*/
	
	public function rules()
	{
		return [
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
			'updated_at',
			'created_at'
		];
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
			'_id' => new \MongoId($this->_id),
			'title' => new MongoRegex("/$this->title/"),
			'slug' => new MongoRegex("/$this->slug/"),
			'description' => new MongoRegex("/$this->description/"),
			'abstract' => new MongoRegex("/$this->abstract/"),
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at
		]);
		
		return new ActiveDataProvider([
			'query' => $query
		]);
	}
}