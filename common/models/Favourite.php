<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

class Favourite extends ActiveRecord
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
	
	public function ules()
	{
		return [];
	}
	
	public function attributes()
	{
		return [
			'_id',
			'user_id',
			'comic_strip_id',
			'updated_at',
			'created_at'
		];
	}
}