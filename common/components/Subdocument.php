<?php

namespace common\components;

use Yii;
use common\components\ActiveRecord;
use yii\base\Arrayable;
use yii\base\Object;

class Subdocument extends Object implements \ArrayAccess, \Iterator
{
	public $attribute;
	public $value = [];
	public $class;
	public $options = [];
	
	public $isArray = true;
	public $isFlatArray = true;
	
	public $position = 0;
	
	public function init()
	{
		parent::init();

		foreach($this->value as $k => $v){
			if(!is_numeric($k)){
				// This is not an array
				$this->isArray = false;
			}
			
			if(!is_scalar($v)){
				// This is not a 1d array
				$this->isFlatArray = false;
			}
		}
		
		if(array_key_exists('eager', $this->options) && $this->options['eager'] === true){
			$this->all();
		}
	}
	
	public function all()
	{
		if(is_array($this->value) && count($this->value) > 0){
			// Should only do something if something is there
			foreach($this->value as $k => $value){
				$this->value[$k] = $this->instantiateOne($value);
			}
		}
		return $this->value;
	}
	
	public function one($key)
	{
		if(is_array($this->value) && isset($this->value[$key])){
			return $this->value[$key] = $this->instantiateOne($this->value[$key]);
			
		}
		return null;
	} 
	
	public function instantiateOne($value)
	{
		if($value instanceof Subdocument){
			return $value;
		}
		
		if($value instanceof \stdClass){
			// This shouldn't be a stdclass but if it is
			$value = json_decode(json_encode($value));
		}
		
		if($class){
			$model = new $this->class;
			$model->setAttributes($value);
			$value = $model;
		}
		return $value;
	}
	
	public function validate()
	{
		
	}
	
	public function offsetSet($offset, $value)
	{
		if(is_null($offset)){
			$this->value[] = $value;
		}else{
			$this->value[$offset] = $value;
		}
	}
	
	public function offsetExists($offset)
	{
		return isset($this->value[$offset]);
	}
	
	public function offsetUnset($offset)
	{
		unset($this->container[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return isset($this->value[$offset]) ? $this->one($offset) : null;
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function current()
	{
		return $this->one($this->position);
	}
	
	public function key()
	{
		return $this->position;
	}
	
	public function next()
	{
		++$this->position;
	}
	
	public function valid()
	{
		return isset($this->value[$this->position]);
	}
}