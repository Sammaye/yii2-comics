<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

class FavouritesCollection extends ActiveRecord
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
	
	public function rules()
	{
		return [];
	}
	
	public function attributes()
	{
		return [
			'_id',
			'user_id',
			'strip_count',
			'created_at',
			'updated_at'
		];
	}
}