<?php

namespace common\components;

use Yii;
use common\components\MongoCursor;

class ActiveQuery extends \yii\mongodb\ActiveQuery
{
	public function each()
	{
		return Yii::createObject([
			'class' => MongoCursor::className(),
			'query' => $this
		]);
	}
	
	public function buildCursor($db = null){
		return parent::buildCursor($db);
	}
	
	public function raw()
	{
		return $this->buildCursor();
	}
}