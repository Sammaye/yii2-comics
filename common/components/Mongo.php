<?php

namespace common\components;

use Yii;

use MongoDB\BSON\UTCDateTime;

class Mongo
{
	public static function date(UTCDateTime $mongoDate)
	{
		return mktime(
			0, 
			0, 
			0, 
			$mongoDate->toDateTime()->format('m'), 
			$mongoDate->toDateTime()->format('d'), 
			$mongoDate->toDateTime()->format('Y')
		);
	}
}