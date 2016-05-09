<?php

namespace common\models;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use common\components\ActiveRecord;
use common\components\DateValidator;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use common\models\ComicStrip;
use yii\imagine\Image;
use common\components\MongoDateValidator;

class Comic extends ActiveRecord
{
	const TYPE_DATE = 0;
	const TYPE_ID = 2;

	private static $_scrapers;
	private $_scraperErrors;
	
	public function formName()
	{
		return 'Comic';
	}
	
	public static function collectionName()
	{
		return Inflector::camel2id(StringHelper::basename('Comic'), '_');
	}

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
			['slug', 'string', 'max' => 250],
			['description', 'string', 'max' => 1500],
			['abstract', 'string', 'max' => 250],
			['scrape_url', 'string', 'max' => 250],
			['homepage', 'url'],
			['author', 'string', 'max' => 400],
			['author_homepage', 'url'],
			
			['type', 'in', 'range' => array_keys($this->getTypes())],
			['type', 'filter', 'filter' => function($value){
					return (int)$value;
			}],
			
			['scraper', 'in', 'range' => array_keys($this->getScrapers())],
			['dom_path', 'string', 'max' => 400],
			
			[
				'index_format', 
				'default', 
				'value' => 'Y-m-d',
				'when' => function($model){
					return $model->type === self::TYPE_DATE;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . self::TYPE_DATE . "';
    			}"
			],
			[
				['current_index', 'first_index', 'last_index'], 
				'common\components\MongoDateValidator', 
				'format' => 'php:d/m/Y',
				'when' => function($model){
					return $model->type === self::TYPE_DATE;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . self::TYPE_DATE . "';
    			}"
			],

			//['index_format', 'default', '#^\d+$#'],
			[
				['current_index', 'first_index', 'last_index'], 
				'integer',
				'when' => function($model){
					return $model->type === self::TYPE_ID;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . self::TYPE_ID . "';
    			}"
			],
			[
				['current_index', 'first_index', 'last_index'], 
				'filter', 
				'filter' => function($value){
					return (String)$value;
				},
				'when' => function($model){
					return $model->type === self::TYPE_ID;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . self::TYPE_ID . "';
    			}"
			],
			
			['index_format', 'validateIndexFormat'],
			['index_step', 'validateIndexStep'],
			
			[
				[
					'active',
					'live'
				], 
				'common\components\NumberValidator', 
				'min' => 0, 
				'max' => 1
			],

			[
				[
					'title', 
					'scrape_url', 
					'type', 
					'dom_path', 
					'current_index'
				], 
				'required'
			],
			
			[
				[
					'last_index'
				],
				'required',
				'when' => function($model){
					return $model->active == false;
				},
				'whenClient' => "function (attribute, value) {
        			return $('#comic-active').val() == 0;
    			}"
			],
			[
				'current_index', 
				'validateInactiveCurrentIndex',
				'when' => function($model){
					return $model->active == false;
				},
				'whenClient' => "function (attribute, value) {
					return $('#comic-active').val() == 0;
				}"
			],
			
			[
				[
					'_id', 
					'title', 
					'slug', 
					'description', 
					'abstract', 
					'updated_at', 
					'created_at'
				], 
				'safe', 
				'on' => 'search'
			]
		];
	}
	
	public function validateIndexFormat($attribute, $params)
	{
		if(
			$this->type === self::TYPE_DATE &&
			(
				!preg_match('/[d]/i', $this->$attribute) || 
				!preg_match('/[m]/i', $this->$attribute) || 
				!preg_match('/[y]/i', $this->$attribute)
			)
		){
			$this->addError($attribute, 'The index format must be valid syntax');
		}elseif($this->type === self::TYPE_ID){
			// There is nothing to validate here atm really
			//$this->addError($attribute, 'The index format must be valid syntax');
		}else{
			//$this->addError($attribute, 'Could not validate the index format since no type was set');
		}
	}
	
	public function validateIndexStep($attribute, $params)
	{
		$value = $this->$attribute;
		if($this->type === self::TYPE_DATE){
			if(preg_match('#^\d+$#', $value)){
				// If it is an int then let's add the default "day" step
				$value = $value . ' day';
			}
			if(!preg_match('#^([0-9]+)\s+(year|month|week|day)#', $value)){
				$this->addError($attribute, 'The index step is not a valid syntax');
			}
			$this->$attribute = $value;
		}elseif($this->type === self::TYPE_ID){
			if(!preg_match('#^\d+$#', $value)){
				$this->addError($attribute, 'The index step for ID should be an int');
			}
			$value = (int)$value;
			if($value <= 0){
				$this->addError($attribute, 'The index step must be greater than 0');
			}
			$this->$attribute = $value;
		}else{
			//$this->addError($attribute, 'Could not validate the index step since no type was set');
		}
	}
	
	public function validateInactiveCurrentIndex($attribute, $params)
	{
		$value = $this->$attribute;
		if(
			$this->type === self::TYPE_DATE && 
			$this->current_index->sec > $this->last_index->sec
		){
			$this->addError(
				$attribute, 
				'Inactive comics cannot have a current index after it\'s end'
			);
		}elseif(
			$this->type === self::TYPE_ID && 
			$this->current_index > $this->last_index
		){
			$this->addError(
				$attribute, 
				'Inactive comics cannot have a current index after it\'s end'
			);
		}
	}
	
	public function attributes()
	{
		return [
			'_id',
			'title',
			'slug',
			'description',
			'abstract',
			'scrape_url',
			'homepage',
			'author',
			'author_homepage',
			'type',
			
			'scraper',
			'dom_path',
			'index_format',
			'current_index',
			'last_index',
			'first_index',
			'index_step',
			
			'active',
			'live',
			'last_checked',
			'updated_at',
			'created_at'
		];
	}
	
	public function init()
	{
		$this->active = 1;
		$this->live = 1;
		parent::init();
	}
	
	public static function instantiate($row)
	{
		if(
			!isset($row['adapter']) || 
			!array_key_exists($row['adapter'], static::getScrapers())
		){
			return new static;
		}
		
		$className = '\common\scrapers\\' . $row['adapter'];
		if(!class_exists($className)){
			// OMG Another Error
			throw new InvalidConfigException(
				'Comic: ' . (String)$row['_id'] . ' has a non-existant adapter: ' . $className
			);
		}
		return new $className;
	}
	
	public function beforeSave($insert)
	{
		$this->slug = Inflector::slug($this->title);
		if($this->isAttributeChanged('description') || $insert){
			$this->abstract = StringHelper::truncate($this->description, 150);
		}
		
		// Nullify all empty fields to save a tad bit of space
		foreach($this->attributes() as $k => $v){
			if(is_string($this->$v) && strlen($this->$v) <= 0){
				$this->$v = null;
			}
		}
		
		return parent::beforeSave($insert);
	}
	
	public function getStrips()
	{
		return $this->hasMany('common\models\ComicStrip', ['comic_id' => '_id']);
	}
	
	public static function getScrapers()
	{
		if(self::$_scrapers === null){
			$adapters = [];
			$dir = Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'scrapers';
			$d = dir($dir);
			
			while(false !== ($entry = $d->read())){
				if($entry === '.' || $entry === '..'){
					continue;
				}
				if(pathinfo($entry, PATHINFO_EXTENSION) === 'php'){
					// Then we willl count it as an adapter
					$name = pathinfo($entry, PATHINFO_FILENAME);
					$adapters[$name] = $name;
				}
			}
			self::$_scrapers = $adapters;
		}
		return self::$_scrapers;
	}

	public function getTypes()
	{
		return [
			self::TYPE_DATE => 'Date', 
			self::TYPE_ID => 'ID'
		];
	}
	
	public function getCurrentIndexValue()
	{
		if($this->current_index != null){
			if($this->type === self::TYPE_DATE){
				return date('d/m/Y', $this->current_index->sec);
			}elseif($this->type === self::TYPE_ID){
				return (String)$this->current_index;
			}
		}
		return $this->current_index;
	}
	
	public function getLastIndexValue()
	{
		if($this->last_index != null){
			if($this->type === self::TYPE_DATE){
				return date('d/m/Y', $this->last_index->sec);
			}elseif($this->type === self::TYPE_ID){
				return (String)$this->last_index;
			}
		}
		return $this->last_index;
	}
	
	public function getFirstIndexValue()
	{
		if($this->first_index != null){
			if($this->type === self::TYPE_DATE){
				return date('d/m/Y', $this->first_index->sec);
			}elseif($this->type === self::TYPE_ID){
				return (String)$this->first_index;
			}
		}
		return $this->first_index;
	}

	public function index($index = null)
	{
        $index = $index ?: $this->current_index;
        if(
			$this->type === self::TYPE_DATE && 
			!$index instanceof \MongoDate
        ){
			if(
				(
					new MongoDateValidator(['format' => 'php:d-m-Y'])
				)->validate($index)
			){
				$index = new \MongoDate(strtotime($index));
			}else{
				throw new InvalidParamException('The index ' . $index . ' is not a valid date');
			}
        }elseif($this->type === self::TYPE_ID){
			// Return a string since this is the standard for non-int ids as well
			$index = (String)$index;
        }
        return $index;
	}
	
	public function indexUrl($index, $protocol = null)
	{
		$index = $this->index($index);
		if($this->type === self::TYPE_DATE){
			return Url::to([
				'comic/view',
				'id' => (String)$this->_id,
				'index' => date('d-m-Y', $index->sec)
			], $protocol);
		}elseif($this->type === self::TYPE_ID){
			return Url::to([
				'comic/view',
				'id' => (String)$this->_id,
				'index' => $index
			], $protocol);
		}
		return null;
	}
	
	public function updateIndex($index, $save = true)
	{
		if($this->active){
			if(
				$this->type === self::TYPE_DATE && 
				$index->sec > $this->current_index->sec
			){
				$this->current_index = $index;
			}elseif(
				$this->type === self::TYPE_ID && 
				$index > $this->current_index
			){
				$this->current_index = $index;
			}
		}
		
		if($save){
			$this->save(['current_index']);
		}
	}
	
	public function isIndexOutOfRange($index)
	{
		if($this->active){
			if(
				$this->type === self::TYPE_DATE && 
				$index->sec > $this->current_index->sec
			){
				return true;
			}elseif(
				$this->type === self::TYPE_ID && 
				$index > $this->current_index
			){
				return true;
			}        	
		}else{
			if(
				$this->type === self::TYPE_DATE && 
				$index->sec > $this->last_index->sec
			){
				return true;
			}elseif(
				$this->type === self::TYPE_ID && 
				$index > $this->last_index
			){
				return true;
			}
		}
		return false;
	}
	
	public function scrapeUrl($index)
	{
		switch($this->type){
			case self::TYPE_DATE:
				$date = new \DateTime();
				$date->setDate(
					date('Y', $index->sec),
					date('m', $index->sec),
					date('d', $index->sec)
				);
				$index = $date->format($this->index_format);
				break;
			case self::TYPE_ID:
			default:
				break;
		}
		return preg_replace('#\{\$value\}#', $index, $this->scrape_url);
	}
	
	public function previous(ComicStrip $cStrip, array $data = [])
    {
        $index = $this->index($cStrip->index);

        if($this->type === self::TYPE_DATE){
	        $strip = $this->getStrip(new \MongoDate(
	            strtotime("-" . ($this->index_step ?: '1 day'), $index->sec)
	        ));
        }elseif($this->type === self::TYPE_ID){
        	$indexStep = $this->index_step ?: 1;
			if(($index - $indexStep) <= 0){
				return null;
			}
			$strip = $this->getStrip($index - $indexStep, $data);
        }
        
        if(!$strip){
            // As a last resort, to try and compensate for
            // odd schedules, do we have any previously?
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => ['$lt' => $index]])
                ->orderBy(['index' => SORT_DESC])
                ->one();
        }
        return $strip;
    }
    
    public function next(ComicStrip $cStrip, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($cStrip->index);
        
        $nextIndex = null;
        if($this->type === self::TYPE_DATE){
	        $nextIndex = new \MongoDate(
	            strtotime("+" . ($this->index_step ?: '1 day'), $index->sec)
	        );
        }elseif($this->type === self::TYPE_ID){
			$nextIndex = $index + ($this->index_step ?: 1);
        }
        
        $nextIndex = $this->index($nextIndex);

        if(
        	!$nextIndex || 
        	(!$ignoreCurrent && $this->isIndexOutOfRange($nextIndex))
        ){
        	return null;
        }

        if(
			$strip = ComicStrip::find()
			    ->where(['comic_id' => $this->_id, 'index' => $nextIndex])
			    ->one()
		){
            return $strip;
		}else{
            $strip = $this->downloadStrip($nextIndex, $data);
            
            if(!$strip){
                // As a last resort, to try and compensate for
                // odd schedules, do we have any next?
                $strip = ComicStrip::find()
    			    ->where(['comic_id' => $this->_id, 'index' => ['$gt' => $index]])
    			    ->orderBy(['index' => SORT_DESC])
    			    ->one();
            }
            return $strip;
		}
    }
    
    public function current($index = null, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($index);
		if(!$ignoreCurrent && $this->isIndexOutOfRange($index)){
			return null;
		}
        return $this->getStrip($index, $data);
    }
    
    public function getStrip($index, array $data = [])
    {
        $index = $this->index($index);
        if(
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => $index])
                ->one()
        ){
            return $strip;
        }else{
    		return $this->downloadStrip($index, $data);
        }
    }
    
    /**
     * Used specifically by the scraper to get new strips
     */
    public function scrapeStrip()
    {
    	$timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    	
    	$strip = $this->current();
		
		if(!$strip){
			Yii::warning(
				'Could not any strip for ' . $this->title 
				. '(' . (String)$this->_id . ') by the index ' 
				. $this->current_index
			);
			return null;
		}
		
		if(
			$strip->date instanceof \MongoDate && 
			$strip->date->sec === $timeToday
		){
			return $strip;
		}
		
		if(
			(
				$strip = $this->next(
					$strip, 
					true, 
					['date' => new \MongoDate($timeToday)]
				)
			) === null
		){
			/*
			Yii::warning(
				'Could not get strip for ' . $comic->title 
				. '(' . (String)$comic->_id . ') by the index ' 
				. $index
			);
			*/
			return null;
		}

		$this->updateIndex($strip->index, false);
		$this->last_checked = new \MongoDate($tiemToday);
		if(!$this->save(['last_checked', 'current_index'])){
			Yii::warning(
				'Could not save last_checked and current_index for ' 
				. (String)$this->_id
			);
		}
		return $strip;
    }

	public function downloadStrip($index, array $data = [])
	{
		$strip = new ComicStrip();
		$strip->comic_id = $this->_id;
		$strip->index = $index;
		foreach($data as $k => $v){
			$strip->$k = $v;
		}
		if(!$this->populateRemoteImage($strip) || !$strip->save()){
			return null;
		}
		return $strip; 
	}
	
	public function populateRemoteImage(&$model, $url = null)
	{
		if(!$model->url){
			$model->url = $this->getRemoteImage($url ?: $this->scrapeUrl($model->index));
		}

		if(($model->url) && ($binary = file_get_contents($model->url))){
			$model->img = new \MongoBinData($binary);
			return true;
		}
		return false;
	}
	
	public function getRemoteImage($scrapeUrl)
	{
		$url = null;
		$this->_scraperErrors = [];
		
		try{
			$res = (new Client)->request(
				'GET', 
				$scrapeUrl, 
				[
					'headers' => [
						'User-Agent' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)'
					]
				]
			);
		}catch(ClientException $e){
			// Log the exception
			$message = (String)$this->_id . ' returned ' . 
				$e->getResponse()->getStatusCode()  
				. ' for ' . $scrapeUrl;
			$this->_scraperErrors[] = $message;
			Yii::warning($message);
			return $url;
		}

		$doc = new \DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHtml($res->getBody());
		libxml_clear_errors();
		
		$el = new \DOMXPath($doc);
		if(strpos($this->dom_path, '||') !== false){
			$paths = preg_split('#\|\|#', $this->dom_path);
		}else{
			$paths = [$this->dom_path];
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

		if(!$url){
			$message = (String)$this->_id . ' could not find img with a src for ' 
				. $scrapeUrl;
			$this->_scraperErrors[] = $message;
			Yii::warning($message);
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
			$url = trim($this->homepage, '/') . '/' . trim($parts['path'], '/'); 
		}
		return $url;
	}
	
	public function getScraperErrors()
	{
		return $this->_scraperErrors;
	}
	
	public static function renderStripImage($id)
	{
		if(($pos = strpos($id, '_')) !== false){
			$parts = explode('_', $id);
			$id = $parts[0];
			$index = $parts[1];
		}
		
		if($model = ComicStrip::find()->where(['_id' => new \MongoId($id)])->one()){
			if(is_array($model->img)){
				$image = Image::getImagine()->load($model->img[$index]->{'bin'});
			}else{
				$image = Image::getImagine()->load($model->img->{'bin'});
			}
			return $image->show('png');
		}
		return '';
	}
	
	public function indexExist($index)
	{
		try{
			$res = (new Client)->request(
				'GET', 
				$this->scrapeUrl($index), 
				[
					'headers' => [
						'User-Agent' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)'
					]
				]
			);
		}catch(ClientException $e){
			return false;
		}
		return true;
	}

	public function search()
	{
		foreach($this->attributes() as $field){
			$this->$field = null;
		}
		if($get = Yii::$app->getRequest()->get('Comic')){
			$this->attributes = $get;
		}
		
		$query = static::find();
		$query->filterWhere([
			'_id' => $this->_id ? new \MongoId($this->_id) : null,
			'title' => $this->title ? new \MongoRegex("/$this->title/") : null,
			'slug' => $this->slug ? new \MongoRegex("/$this->slug/") : null,
			'description' => $this->description ? new \MongoRegex("/$this->description/") : null,
			'abstract' => $this->abstract ? new \MongoRegex("/$this->abstract/") : null,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at
		]);
		
		return new ActiveDataProvider([
			'query' => $query
		]);
	}
}