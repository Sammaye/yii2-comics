<?php

namespace common\components;

use Yii;
use common\components\Subdocument;
use yii\mongodb\ActiveRecord as MongoActiveRecord;

class ActiveRecord extends MongoActiveRecord
{
	private $_subdocuments = [];
	
	public function init()
	{
		parent::init();
		
		foreach($this->subdocuments() as $k => $options){
			if(is_numeric($k)){
				$this->_subdocuments[$options] = [];
			}else{
				$this->_subdocuments[$k] = $options;
			}
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
	
	public function createSubdocument($options, $value)
	{
		$options['value'] = $value;
		return new Subdocument($options);
	}
	
	public function hasSubdocument($name)
	{
		if(isset($this->_subdocuments[$name])){
			return true;
		}else{
			return false;
		}
	}
	
	public static function populateRecord($record, $row)
	{
		$columns = array_flip($record->attributes());
		foreach ($row as $name => $value) {
			if($record->hasSubdocument($name)){
				$record->setAttribute($name, new Subdocument(['attribute' => $name, 'value' => $value]));
			}elseif (isset($columns[$name])) {
				$record->setAttribute($name, $value);
			} elseif ($record->canSetProperty($name)) {
				$record->$name = $value;
			}
		}
		$record->setOldAttributes($record->getAttributes());
	}
}