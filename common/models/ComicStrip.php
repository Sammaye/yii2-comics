<?php

namespace common\models;

use Yii;
use common\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use common\components\Mongo;
use common\models\Comic;
use yii\helpers\Url;
use yii\validators\UniqueValidator;

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
		$rules = [
			[['comic_id', 'date'], 'required'],
			['comic_id', 'common\components\MongoIdValidator'],
			['url', 'string', 'max' => 250],
			['date', 'common\components\MongoDateValidator', 'format' => 'php:d/m/Y'],
			//['date', 'unique', 'targetAttribute' => ['date', 'comic_id']],
			['inc_id', 'common\components\NumberValidator'],
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
		return $rules;
	}
	
	public function attributes()
	{
		return [
			'_id',
			'comic_id',
			'url',
			'img',
			'date',
			'inc_id',
			'updated_at',
			'created_at'
		];
	}
	
	public function setComic($rows)
	{
		return $this->populateRelation('comic', $rows);
	}
	
	public function getComic()
	{
		return $this->hasOne('common\models\Comic', ['_id' => 'comic_id']);
	}
	
	public function beforeSave($insert)
	{
		if($insert){
			if($this->comic->is_increment){
				$v = new UniqueValidator;
				$v->targetAttribute = ['inc_id', 'comic_id'];
				$v->validateAttribute($this, 'inc_id');
			}else{
				$v = new UniqueValidator;
				$v->targetAttribute = ['date', 'comic_id'];
				$v->validateAttribute($this, 'date');
			}
			
			if(count($this->getErrors()) > 0){
				return false;
			}
		}
		return parent::beforeSave($insert);
	}
	
	public function getUrl()
	{
		return Url::to($this->comic->scrape_url . ($this->comic->is_increment ? $this->inc_id : date($this->comic->date_format, $this->date->sec)));
	}
	
	public function getNextUrl()
	{
		if($this->comic->is_increment){
			return Url::to([
				'comic/view',
				'id' => (String)$this->comic_id,
				'date' => $this->inc_id + 1
			]);
		}else{
			return Url::to([
				'comic/view',
				'id' => (String)$this->comic_id,
				'date' => date('d-m-Y', strtotime("+" . ($this->comic->day_step ?: 1) . " day", $this->date->sec))
			]);
		}
	}
	
	public function getPreviousUrl()
	{
		if($this->comic->is_increment){
			return Url::to([
				'comic/view',
				'id' => (String)$this->comic_id,
				'date' => $this->inc_id - 1
			]);
		}else{
			return Url::to([
				'comic/view', 
				'id' => (String)$this->comic_id, 
				'date' => date('d-m-Y', strtotime("-" . ($this->comic->day_step ?: 1) . " day", $this->date->sec))
			]);
		}
	}
	
	public function getIsFirstStrip()
	{
		if($this->isFirstStrip === null){
			if($this->comic->is_increment){
				if($comicStrip = ComicStrip::find()->orderBy(['inc_id' => SORT_ASC])->one()){
					if($this->inc_id != $comicStrip->inc_id){
						$this->isFirstStrip = false;
					}else{
						$this->isFirstStrip = true;
					}
				}else{
					$this->isFirstStrip = true;
				}
			}else{
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
		}
		return $this->isFirstStrip;
	}
	
	public function getIsLastStrip()
	{
		if($this->isLastStrip === null){
			if($this->comic->is_increment){
				if(($comicStrip = ComicStrip::find()->orderBy(['inc_id' => SORT_DESC])->one())){
					if($this->inc_id != $comicStrip->inc_id){
						$this->isLastStrip = false;
					}else{
						$this->isLastStrip = true;
					}
				}else{
					$this->isLastStrip = true;
				}
			}else{
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
		}
		return $this->isLastStrip;
	}
	
	public function getDoesPageExist()
	{
		$ch = curl_init();
		
		if($this->comic->is_increment){
			curl_setopt($ch, CURLOPT_URL, $this->comic->scrape_url . $this->inc_id . '/');
		}else{
			curl_setopt($ch, CURLOPT_URL, $this->comic->scrape_url . $date->format($this->comic->date_format));
		}
		
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true); // remove body
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$head = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
			
		if($httpCode == 200){
			return true;
		}else{
			return false;
		}
	}
	
	public function getRemoteImage()
	{
		$url = null;
		
		$date = new \DateTime();
		$date->setDate(date('Y', $this->date->sec), date('m', $this->date->sec), date('d', $this->date->sec));
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
		
		if($this->comic->is_increment){
			curl_setopt($ch, CURLOPT_URL, preg_replace('#\{\$value\}#', $this->inc_id, $this->comic->scrape_url));
		}else{
			curl_setopt($ch, CURLOPT_URL, preg_replace('#\{\$value\}#', $date->format($this->comic->date_format), $this->comic->scrape_url));
		}

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$body = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if(!$body){
			return $url;
		}
		
		$doc = new \DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHtml($body);
		libxml_clear_errors();
		
		$el = new \DOMXPath($doc);
		if(strpos($this->comic->dom_path, '||') !== false){
			$paths = preg_split('#\|\|#', $this->comic->dom_path);
		}else{
			$paths = [$this->comic->dom_path];
		}
		
		foreach($paths as $domPath){
			$elements = $el->query($domPath);
			if($elements){
				foreach($elements as $element){
					$url = $element->getAttribute('src');
				}
			}
			if($url){
				break;
			}
		
		}

		if(
			$url &&
			($parts = parse_url($url)) &&
			!isset($parts['scheme']) && 
			isset($parts['host'])
		){
			$url = 'http://' . trim($url, '//');
		}
		
		if(
			$url && 
			($parts = parse_url($url)) && 
			(
				!isset($parts['scheme']) || 
				!isset($parts['host'])
			) && 
			isset($parts['path'])
		){
			// The URL is relative as such add the homepage onto the beginning
			$url = trim($this->comic->homepage, '/') . '/' . trim($parts['path'], '/'); 
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