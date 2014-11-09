<?php

namespace common\components;

use Yii;
use common\components\Subdocument;
use yii\mongodb\ActiveRecord;

class ActiveRecord extends ActiveRecord
{
	public function init()
	{
		parent::init();
		
		foreach($this->subdocuments() as $sub){
			$sub['value'] = $this->{$sub['attribute']};
			$this->{$sub['attribute']} = new Subdocument($sub);
		}
	}
	
	public function subdocuments()
	{
		return [];
	}
	
	public function getDirtyAttributes($names = null)
	{
		$attributes = parent::getDirtyAttributes($names);
		return $this->getRawAttributes($attributes);
	}
	
	public function getAttributes($names = null, $except = [])
	{
		$attributes = parent::getAttributes($names, $except);
		return $this->getRawAttributes($attributes);
	}
	
	public function getRawAttributes()
	{
		
	}
}