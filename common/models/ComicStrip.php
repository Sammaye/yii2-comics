<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;
use yii\data\ActiveDataProvider;
use common\components\Mongo;
use common\models\Comic;

class ComicStrip extends ActiveRecord
{
	public $isFirstStrip = null;
	public $isLastStrip = null;
	
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
			[['comic_id', 'date'], 'required'],
			['comic_id', 'common\components\MongoIdValidator'],
			['url', 'string', 'max' => 250],
			['date', 'common\components\MongoDateValidator', 'format' => 'php:d/m/Y'],
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
		return $this->hasOne('common\models\Comic', ['_id' => 'comic_id']);
	}
	
	public function getIsFirstStrip()
	{
		if($this->isFirstStrip === null){
			if($comicStrip = ComicStrip::find()->orderBy(['date' => SORT_ASC])->one()){
				if(Mongo::date($this->date) != Mongo::date($comicStrip->date)){
					$this->isFirstStrip = false;
				}else{
					$this->isFirstStrip = true;
				}
			}else{
				$this->isFirstStrip = true;
			}
		}
		return $this->isFirstStrip;
	}
	
	public function getIsLastStrip()
	{
		if($this->isLastStrip === null){
			if(Mongo::date($this->date) == Mongo::date(new \MongoDate)){
				$this->isLastStrip = true;
			}elseif(($comicStrip = ComicStrip::find()->orderBy(['date' => SORT_DESC])->one())){
				if(Mongo::date($this->date) != Mongo::date($comicStrip->date)){
					$this->isLastStrip = false;
				}else{
					$this->isLastStrip = true;
				}
			}else{
				$this->isLastStrip = true;
			}
		}
		return $this->isLastStrip;
	}
	
	public function getRemoteImage()
	{
		$date = new \DateTime();
		$date->setDate(date('Y', $this->date->sec), date('m', $this->date->sec), date('d', $this->date->sec));
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
		curl_setopt($ch, CURLOPT_URL, $this->comic->scrape_url . $date->format($this->comic->date_format));
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
		
		if(!$el){
			return null;
		}
		
		$url = null;
		foreach($el->childNodes as $child){
			if($child instanceof \DOMElement && $child->tagName == 'img'){
				$url = $child->getAttribute('src');
			}
		}
		return $url;
	}

	public function populateRemoteImage()
	{
		if(!$this->url){
			$this->url = $this->getRemoteImage();
		}
		if(($this->url) && ($binary = file_get_contents($this->url))){
			$this->img = new \MongoBinData($binary);
			return true;
		}
		return false;
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
			'comic_id' => $comic_id,
			'url' => $this->url ? new \MongoRegex("/$this->url/") : null,
			'date' => $this->date ? new \MongoDate($this->date) : null,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at
		]);
	
		return new ActiveDataProvider([
			'query' => $query
		]);
	}
}