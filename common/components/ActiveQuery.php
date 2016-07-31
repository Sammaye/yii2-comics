<?php

namespace common\components;

use Yii;
use yii\mongodb\ActiveQuery as BaseActiveQuery;
use common\components\Query;
use common\components\MongoCursor;

class ActiveQuery extends \yii\mongodb\ActiveQuery
{
	public function buildCursor($db = null){
		return parent::buildCursor($db);
	}
	
	public function raw()
	{
		return $this->buildCursor();
	}
}