<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

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
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
					ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
				],
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
}