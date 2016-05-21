<?php

namespace common\components;

use IteratorIterator;

use yii\base\Object;

class MongoCursor extends Object implements \Iterator
{
	public $cursor;
	/**
	 * @var \common\components\ActiveQuery
	 */
	public $query;


	public function init()
	{
		$cursor = $this->query->buildCursor();
		$it = new IteratorIterator($cursor);
		$it->rewind();
		$this->cursor = $it;
		parent::init();
	}
	
	public function rewind()
	{
		$this->cursor->rewind();
	}
	
	public function current()
	{
		return $this->query->populate([$this->cursor->current()])[0];
	}
	
	public function key()
	{
		return $this->cursor->key();
	}
	
	public function next() {
		$this->cursor->next();
	}
	
	public function valid()
	{
		return $this->cursor->valid();
	}
}