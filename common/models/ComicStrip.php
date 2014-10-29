<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

class ComicStrip extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => 'yii\behaviors\TimestampBehavior',
				'value' => function($e){ return new \MongoDate(); }
			],
		];
	}

	public function rules()
	{
		return [
			[['comic_id', 'url', 'date'], 'required'],
			['comic_id', 'common\components\MongoIdValidator'],
			['url', 'string', 'max' => 250],
			['date', 'common\components\MongoDateValidator'],
			['date', 'unique', 'targetAttribute' => ['date', 'comic_id']],
			[
				[
					'_id',
					'comic_id',
					'url',
					'date',
					'updated_at',
					'created_at'
				],
				'safe',
				'on' => 'search'
			]
		];
	}
	
	public function attributes()
	{
		return [
			'_id',
			'comic_id',
			'url',
			'img',
			'date',
			'updated_at',
			'created_at'
		];
	}
	
	public function getComic()
	{
		return $this->hasOne('common\models\ComicStrip', ['comic_id' => '_id']);
	}
	
	public function getRemoteImage()
	{
		$ch = curl_init();
		curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
		curl_setopt($ch, CURLOPT_URL, $this->comic->scrape_url . $date->format('Y-m-d'));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$body = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$doc = new \DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHtml($body);
		libxml_clear_errors();
		
		$el = $doc->getElementById('comic_wrap');
		
		$url = null;
		foreach($el->childNodes as $child){
			if($child instanceof \DOMElement && $child->tagName == 'img'){
				$url = $child->getAttribute('src');
			}
		}
		return $url;
	}
	
	public function search($comic_id)
	{
		foreach($this->attributes() as $field){
			$this->$field = null;
		}
		if($get = Yii::$app->getRequest()->get('ComicStrip')){
			$this->attributes = $get;
		}
	
		$query = static::find();
		$query->filterWhere([
			'_id' => $this->_id ? new \MongoId($this->_id) : null,
			'comic_id' => $this->comic_id ? new \MongoId($this->comic_id) : null,
			'url' => $this->title ? new \MongoRegex("/$this->url/") : null,
			'date' => $this->description ? new \MongoDate($this->date) : null,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at
		]);
	
		return new ActiveDataProvider([
			'query' => $query
		]);
	}
}