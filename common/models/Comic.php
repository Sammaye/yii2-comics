<?php

namespace common\models;

use GuzzleHttp\Exception\RequestException;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\imagine\Image;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\TransferStats;
use common\components\ActiveRecord;
use common\components\MongoDateValidator;
use common\models\ComicStrip;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Binary;

class Comic extends ActiveRecord
{
    const TYPE_DATE = 0;
    const TYPE_ID = 2;

    const SCENARIO_SEARCH = 'search';

    private static $_scrapers;
    private $_scrapeErrors = [];

    public $userAgents = [
        'Google Bot' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)',
        'Chrome User' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
    ];

    public function formName()
    {
        return 'Comic';
    }

    public static function collectionName()
    {
        return Inflector::camel2id(StringHelper::basename('Comic'), '_');
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function ($e) {
                    return new UTCDateTime(time() * 1000);
                }
            ],
        ];
    }

    public function rules()
    {
        $current_index = $first_index = $last_index = [
            '',
            'common\components\MongoDateValidator',
            'format' => 'php:' . Yii::$app->getFormatter()->fieldDateFormat,
            'mongoDateAttribute' => '',
            'when' => function ($model) {
                return $model->type === self::TYPE_DATE;
            },
            'whenClient' => "function (attribute, value) {
        		return $('#comic-type').val() == '" . self::TYPE_DATE . "';
    		}"
        ];

        $current_index[0] = $current_index['mongoDateAttribute'] = 'current_index';
        $first_index[0] = $first_index['mongoDateAttribute'] = 'first_index';
        $last_index[0] = $last_index['mongoDateAttribute'] = 'last_index';

        return [

            [
                [
                    'title',
                    'scrape_url',
                    'type',
                    'image_dom_path',
                    'current_index'
                ],
                'required'
            ],

            ['title', 'string', 'max' => 250],
            ['slug', 'string', 'max' => 250],
            ['description', 'string', 'max' => 1500],
            ['abstract', 'string', 'max' => 250],
            ['scrape_url', 'string', 'max' => 250],
            ['base_url', 'string', 'max' => 250],
            ['homepage', 'url'],
            ['author', 'string', 'max' => 400],
            ['author_homepage', 'url'],

            ['type', 'in', 'range' => array_keys($this->getTypes())],
            ['type', 'filter', 'filter' => 'intval'],

            ['scraper', 'in', 'range' => array_keys($this->getScrapers())],
            ['image_dom_path', 'string', 'max' => 400],
            ['nav_url_regex', 'string', 'max' => 400],
            ['nav_next_dom_path', 'string', 'max' => 400],
            ['nav_previous_dom_path', 'string', 'max' => 400],
            ['nav_page_number_dom_path', 'string', 'max' => 400],

            ['scraper_user_agent', 'string', 'max' => 1500],

            [
                'index_format',
                'default',
                'value' => 'Y-m-d',
                'when' => function ($model) {
                    return $model->type === self::TYPE_DATE;
                },
                'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . self::TYPE_DATE . "';
    			}"
            ],

            $current_index,
            $first_index,
            $last_index,

            //['index_format', 'default', '#^\d+$#'],
            [
                ['current_index', 'first_index', 'last_index'],
                'filter',
                'filter' => function ($value) {
                    return (String)$value;
                },
                'when' => function ($model) {
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
                'integer',
                'min' => 0,
                'max' => 1
            ],
            [
                ['active', 'live'],
                'filter',
                'filter' => 'intval'
            ],

            [
                [
                    'first_index',
                    'last_index'
                ],
                'required',
                'when' => function ($model) {
                    return $model->active == false;
                },
                'whenClient' => "function (attribute, value) {
        			return $('#comic-active').val() == 0;
    			}"
            ],
            [
                'current_index',
                'validateInactiveCurrentIndex',
                'when' => function ($model) {
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
                ],
                'safe',
                'on' => self::SCENARIO_SEARCH
            ]
        ];
    }

    public function validateIndexFormat($attribute, $params)
    {
        if (
            $this->type === self::TYPE_DATE &&
            (
                !preg_match('/[d]/i', $this->$attribute) ||
                !preg_match('/[m]/i', $this->$attribute) ||
                !preg_match('/[y]/i', $this->$attribute)
            )
        ) {
            $this->addError(
                $attribute,
                Yii::t(
                    'app',
                    'The index format must be valid syntax'
                )
            );
        } elseif ($this->type === self::TYPE_ID) {
            // There is nothing to validate here atm really
            //$this->addError($attribute, 'The index format must be valid syntax');
        } else {
            //$this->addError($attribute, 'Could not validate the index format since no type was set');
        }
    }

    public function validateIndexStep($attribute, $params)
    {
        $value = $this->$attribute;
        if ($this->type === self::TYPE_DATE) {
            if (preg_match('#^\d+$#', $value)) {
                // If it is an int then let's add the default "day" step
                $value = $value . ' day';
            }
            if (!preg_match('#^([0-9]+)\s+(year|month|week|day)#', $value)) {
                $this->addError(
                    $attribute,
                    Yii::t(
                        'app',
                        'The index step is not a valid syntax'
                    )
                );
            }
            $this->$attribute = $value;
        } elseif ($this->type === self::TYPE_ID) {
            if (!preg_match('#^\d+$#', $value)) {
                $this->addError(
                    $attribute,
                    Yii::t(
                        'app',
                        'The index step for ID should be an int'
                    )
                );
            }
            $value = (int)$value;
            if ($value <= 0) {
                $this->addError(
                    $attribute,
                    Yii::t(
                        'app',
                        'The index step must be greater than 0'
                    )
                );
            }
            $this->$attribute = $value;
        } else {
            //$this->addError($attribute, 'Could not validate the index step since no type was set');
        }
    }

    public function validateInactiveCurrentIndex($attribute, $params)
    {
        if (
            $this->type === self::TYPE_DATE &&
            $this->current_index->toDateTime()->getTimestamp() > $this->last_index->toDateTime()->getTimestamp()
        ) {
            $this->addError(
                $attribute,
                Yii::t(
                    'app',
                    'Inactive comics cannot have a current index after last index'
                )
            );
        } elseif (
            $this->type === self::TYPE_ID &&
            $this->isIndexInt($this->current_index) &&
            $this->current_index > $this->last_index
        ) {
            $this->addError(
                $attribute,
                Yii::t(
                    'app',
                    'Inactive comics cannot have a current index after last index'
                )
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
            'base_url',
            'image_dom_path',
            'nav_dom_path',
            'nav_url_regex',
            'nav_next_dom_path',
            'nav_previous_dom_path',
            'nav_page_number_dom_path',
            'scraper_user_agent',
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
        if (
            !isset($row['scraper']) ||
            !array_key_exists($row['scraper'], static::getScrapers())
        ) {
            return new static;
        }

        $className = '\common\scrapers\\' . $row['scraper'];
        if (!class_exists($className)) {
            // OMG Another Error
            throw new InvalidConfigException(
                Yii::t(
                    'app',
                    '{id} has a non-existant adapter: {class}',
                    [
                        'id' => (String)$row['_id'],
                        'class' => $className
                    ]
                )
            );
        }
        return new $className;
    }

    public function beforeSave($insert)
    {
        $this->slug = Inflector::slug($this->title);
        if ($this->isAttributeChanged('description') || $insert) {
            $this->abstract = StringHelper::truncate($this->description, 150);
        }

        // Nullify all empty fields to save a tad bit of space
        foreach ($this->attributes() as $k => $v) {
            if (is_string($this->$v) && strlen($this->$v) <= 0) {
                $this->$v = null;
            }
        }

        if(!$this->scraper_user_agent){
            $this->scraper_user_agent = $this->userAgents['Google Bot'];
        }

        return parent::beforeSave($insert);
    }

    public function getStrips()
    {
        return $this->hasMany(ComicStrip::class, ['comic_id' => '_id']);
    }

    public static function getScrapers()
    {
        if (self::$_scrapers === null) {
            $adapters = [];
            $dir = Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'scrapers';
            $d = dir($dir);

            while (false !== ($entry = $d->read())) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (pathinfo($entry, PATHINFO_EXTENSION) === 'php') {
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

    public function indexUrl($index, $protocol = null)
    {
        $index = $this->index($index);
        if ($this->type === self::TYPE_DATE) {
            return Url::to([
                'comic/view',
                'id' => (String)$this->_id,
                'index' => $index->toDateTime()->format('d-m-Y')
            ], $protocol);
        } elseif ($this->type === self::TYPE_ID) {
            return Url::to([
                'comic/view',
                'id' => (String)$this->_id,
                'index' => $index
            ], $protocol);
        }
        return null;
    }

    public function scrapeUrl($index)
    {
        $url = $this->scrape_url;
        $baseUrlScheme = parse_url(
            $this->base_url ?: $this->homepage,
            PHP_URL_SCHEME
        ) ?: 'http';
        $baseUrlHost = parse_url(
            $this->base_url ?: $this->homepage,
            PHP_URL_HOST
        );

        switch ($this->type) {
            case self::TYPE_DATE:
                $index = $index->toDateTime()->format($this->index_format);
                break;
            case self::TYPE_ID:
            default:
                break;
        }

        preg_match_all('#\{\$.[^\}]*\}#', $url, $matches);

        foreach ($matches[0] as $match) {
            $params = preg_split('#[:,]#', trim($match, '{}'));
            $operator = array_shift($params);

            if ($operator === '$value' || $operator === '$index'){
                $value = $index;
            } elseif ($operator === '$date') {
                $value = (new \DateTime)->format($params[0]);
            }

            $url = str_replace($match, $value, $url);
        }

        $urlParts = parse_url($url);
        if ($urlParts) {
            $host = null;
            if (!isset($urlParts['scheme']) && !isset($urlParts['host'])) {
                $host = $baseUrlScheme . '://' . $baseUrlHost . '/';
            } elseif (!isset($urlParts['scheme'])) {
                $host = $baseUrlScheme . '://';
            }

            if ($host) {
                $url = $host . ltrim($url, '/');
            }
        }

        return $url;
    }

    public function getCurrentIndexValue()
    {
        if ($this->current_index != null) {
            if ($this->type === self::TYPE_DATE) {
                return $this->current_index->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat);
            } elseif ($this->type === self::TYPE_ID) {
                return (String)$this->current_index;
            }
        }
        return $this->current_index;
    }

    public function getLastIndexValue()
    {
        if ($this->last_index != null) {
            if ($this->type === self::TYPE_DATE) {
                return $this->last_index->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat);
            } elseif ($this->type === self::TYPE_ID) {
                return (String)$this->last_index;
            }
        }
        return $this->last_index;
    }

    public function getFirstIndexValue()
    {
        if ($this->first_index != null) {
            if ($this->type === self::TYPE_DATE) {
                return $this->first_index->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat);
            } elseif ($this->type === self::TYPE_ID) {
                return (String)$this->first_index;
            }
        }
        return $this->first_index;
    }

    public function getLatestIndexValue()
    {
        if ($current_index = $this->getCurrentIndexValue()) {
            return $current_index;
        }
        return $this->getLastIndexValue();
    }

    public function index($index = null, $format = null, $toString = false)
    {
        $index = $index ?: $this->current_index;
        $format = $format ?: $this->index_format;
        if (
            $this->type === self::TYPE_DATE &&
            !$index instanceof UTCDateTime
        ) {
            if (
                (
                    new MongoDateValidator(['format' => 'php:' . $format])
                )->validate($index)
            ) {
                $index = new UTCDateTime(strtotime($index) * 1000);
                if ($toString) {
                    $index = $index->toDateTime()->format(Yii::$app->getFormatter()->fieldDateFormat);
                }
            } else {
                throw new InvalidArgumentException(
                    Yii::t(
                        'app',
                        'The index {index} is not a valid date',
                        ['index' => $index]
                    )
                );
            }
        } elseif ($this->type === self::TYPE_ID) {
            // Return a string since this is the standard for non-int ids as well
            $index = (String)$index;
        }
        return $index;
    }

    public function updateIndex($index, $save = true)
    {
        if ($this->active) {
            if (
                $this->type === self::TYPE_DATE &&
                $index->toDateTime()->getTimestamp() > $this->current_index->toDateTime()->getTimestamp()
            ) {
                $this->current_index = $index;
            } elseif (
                $this->type === self::TYPE_ID &&
                $this->isIndexInt($index) &&
                $index > $this->current_index
            ) {
                $this->current_index = $index;
            } elseif (
                $this->type === self::TYPE_ID &&
                !$this->isIndexInt($index)
            ) {
                $this->current_index = $index;
            }
        } else {
            $this->current_index = $index;
        }

        if ($save) {
            $this->save(false, ['current_index']);
        }
    }

    public function isIndexOutOfRange($index)
    {
        if ($this->active) {
            if (
                $this->type === self::TYPE_DATE &&
                $index->toDateTime()->getTimestamp() > $this->current_index->toDateTime()->getTimestamp()
            ) {
                return true;
            } elseif (
                $this->type === self::TYPE_ID &&
                $this->isIndexInt($index) &&
                $index > $this->current_index
            ) {
                return true;
            } elseif (
                $this->type === self::TYPE_ID &&
                !$this->isIndexInt($index) &&
                $index !== $this->current_index
            ) {
                // TODO figure out how to detect if a sting range is out of range, this could be done by checking the next field on the strip
                //return true;
            }
        } else {
            if (
                $this->type === self::TYPE_DATE &&
                $index->toDateTime()->getTimestamp() > $this->last_index->toDateTime()->getTimestamp()
            ) {
                return true;
            } elseif (
                $this->type === self::TYPE_ID &&
                $this->isIndexInt($index) &&
                $index > $this->last_index
            ) {
                return true;
            } elseif (
                $this->type === self::TYPE_ID &&
                !$this->isIndexInt($index) &&
                $index === $this->last_index
            ) {
                return true;
            }
        }
        return false;
    }

    public function isIndexInt($value)
    {
        if (preg_match('#^[0-9]+$#', $value)) {
            return true;
        }
        return false;
    }

    public function current($index = null, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($index ?: $this->current_index);
        if (!$ignoreCurrent && $this->isIndexOutOfRange($index)) {
            return null;
        }
        return $this->findStrip($index, $data);
    }

    public function previous(ComicStrip $strip, array $data = [])
    {
        if ($strip->previous) {
            return $this->findStrip($strip->previous, $data);
        } elseif ($this->nav_previous_dom_path) {
            // Try and redownload and see if there is a previous now
            if ($this->scrapeStrip($strip) && $strip->previous && $strip->save()) {
                // If we have a previous now then let's get that
                $strip = $this->findStrip($strip->previous, $data);
                return $strip;
            }
            return null; // TODO Experimental
        }

        // Else we will ty and guess it
        $index = $this->index($strip->index);

        if ($this->type === self::TYPE_DATE) {
            $strip = $this->findStrip(new UTCDateTime(
                strtotime("-" . ($this->index_step ?: '1 day'), $index->toDateTime()->getTimestamp()) * 1000
            ));
        } elseif ($this->type === self::TYPE_ID && $this->isIndexInt($index)) {
            $indexStep = $this->index_step ?: 1;
            if (($index - $indexStep) <= 0) {
                return null;
            }
            $strip = $this->findStrip($index - $indexStep, $data);
        } else {
            return null;
        }

        if (!$strip) {
            // As a last resort, to try and compensate for
            // odd schedules, do we have any previously?
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => ['$lt' => $index]])
                ->orderBy(['index' => SORT_DESC])
                ->one();
        }
        return $strip;
    }

    public function next(ComicStrip $strip, $scrape = false, array $data = [])
    {
        if ($strip->next) {
            return $this->findStrip($strip->next, $data);
        } elseif (
            $scrape &&
            $this->nav_next_dom_path &&
            $this->scrapeStrip($strip) &&
            $strip->next &&
            $strip->save()
        ) {
            // If we have a next now then let's get that
            $strip = $this->findStrip($strip->next, $data);
            return $strip;
        } elseif ($this->nav_next_dom_path) {
            return null; // TODO Experimental
        }

        // Else we will try and guess it
        $index = $this->index($strip->index);

        $nextIndex = null;
        if ($this->type === self::TYPE_DATE) {
            $nextIndex = new UTCDateTime(
                strtotime("+" . ($this->index_step ?: '1 day'), $index->toDateTime()->getTimestamp()) * 1000
            );
        } elseif ($this->type === self::TYPE_ID && $this->isIndexInt($index)) {
            $nextIndex = $index + ($this->index_step ?: 1);
        } else {
            return null;
        }

        $nextIndex = $this->index($nextIndex);

        if (
            !$nextIndex ||
            (!$scrape && $this->isIndexOutOfRange($nextIndex))
        ) {
            return null;
        }

        $strip = $this->findStrip($nextIndex, $data);

        if (!$strip) {
            // As a last resort, to try and compensate for
            // odd schedules, do we have any next?
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => ['$gt' => $index]])
                ->orderBy(['index' => SORT_DESC])
                ->one();
        }
        return $strip;
    }

    public function findStrip($index, array $data = [], $scrape = true)
    {
        $index = $this->index($index);

        $model = ComicStrip::find()
            ->where(['comic_id' => $this->_id, 'index' => $index])
            ->one();

        if ($model) {
            return $model;
        } elseif ($scrape) {
            if (!$model) {
                $model = new ComicStrip();
                $model->comic_id = $this->_id;
                $model->index = $index;

                foreach ($data as $k => $v) {
                    $model->$k = $v;
                }
            }

            if ($this->scrapeStrip($model) && $model->save()) {
                return $model;
            }
        }

        return null;
    }

    public function scrapeStrip(&$model, $url = null)
    {
        $imageUrl = null;

        $baseUrl = rtrim($this->base_url ?: $this->scrape_url, '/');
        $baseUrlScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
        $baseUrlHost = parse_url($baseUrl, PHP_URL_HOST);
        if (!$baseUrlHost) {
            // As a last resort we will check the homepage link
            $baseUrl = rtrim($this->homepage, '/');
            $baseUrlScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
            $baseUrlHost = parse_url($baseUrl, PHP_URL_HOST);
        }

        $url = $url ?: $this->scrapeUrl($model->index);

        $domPath = preg_split('#>>#', $this->image_dom_path);
        foreach ($domPath as $k => $v) {
            $domPath[$k] = preg_split('#\|\|#', $v);
        }

        // TODO handle more complex DOM paths, such as ones which are multi-page
        $domPath = end($domPath);

        $dom = $this->getScrapeDom($url);

        if (!$dom) {
            return $this->addScrapeError(
                '{id} could not instantiate DOMDocument Object for {url}',
                [
                    'id' => (String)$this->_id,
                    'url' => $url,
                ]
            );
        }

        foreach ($domPath as $path) {
            $elements = $dom->query($path);

            if ($elements->length <= 0) {
                continue;
            }

            foreach ($elements as $element) {
                $imageUrl = $element->getAttribute('src');
                break;
            }

            if ($imageUrl) {
                break;
            }
        }

        if (!$imageUrl) {
            $this->addScrapeError(
                '{id} could not find img with src for {url}',
                [
                    'id' => (String)$this->_id,
                    'url' => $url
                ]
            );
        } else {
            $imageUrlParts = parse_url($imageUrl);
            if ($imageUrlParts) {
                $imageUrlHost = null;
                if (!isset($imageUrlParts['scheme']) && !isset($imageUrlParts['host'])) {
                    $imageUrlHost = $baseUrlScheme . '://' . $baseUrlHost . '/';
                } elseif (!isset($imageUrlParts['scheme'])) {
                    $imageUrlHost = $baseUrlScheme . '://';
                }

                if ($imageUrlHost) {
                    $imageUrl = $imageUrlHost . ltrim($imageUrl, '/');
                }
            }
            $model->image_url = $imageUrl;
        }

        if ($this->nav_next_dom_path && $this->nav_previous_dom_path) {
            $navDomElements = [
                'previous' => $dom->query($this->nav_previous_dom_path),
                'next' => $dom->query($this->nav_next_dom_path),
            ];
            $navUrlRegex = $this->nav_url_regex
                ?
                : preg_quote(
                    preg_replace('#\{\$value\}|\{\$index\}#', '', $baseUrl),
                    '#'
                ) . '(?<index>[A-Za-z0-9-_]+)';

            foreach ($navDomElements as $k => $element) {
                $matches = [];

                if ($element->length <= 0) {
                    continue;
                }

                $navLinkUrl = $element[0]->getAttribute('href');
                preg_match_all("#$navUrlRegex#", $navLinkUrl, $matches);

                if (!isset($matches['index'][0])) {
                    return $this->addScrapeError(
                        '{id} could not parse navigation URL {url} for the field {field}',
                        [
                            'id' => (String)$this->_id,
                            'url' => $navLinkUrl,
                            'field' => $k === 'previous' ? 'nav_previous_dom_path' : 'nav_next_dom_path',
                        ]
                    );
                }
                $model->$k = $this->index($matches['index'][0], $this->index_format, true);
            }
        }

        $model->url = $url;

        try {
            if ($model->image_url) {
                // Sometimes people like to put crappy special characters into file names
                if (pathinfo($model->image_url, PATHINFO_EXTENSION)) {
                    $filename = pathinfo($model->image_url, PATHINFO_FILENAME);
                    $encodedFilename = rawurlencode($filename);
                    $imageUrl = str_replace($filename, $encodedFilename, $model->image_url);
                }

                if (($binary = file_get_contents($imageUrl))) {
                    $model->image_md5 = md5($binary);
                    $model->img = new Binary($binary, Binary::TYPE_GENERIC);
                    $model->skip = 0;
                    return true;
                }
            }

            throw new \Exception;
        } catch (\Exception $e) {
            // the file probably had a problem beyond our control
            // As such define this as a skip strip since I cannot store it
            $model->skip = 1;
            return true;
        }
        return false;
    }

    /**
     * Used specifically by the scraper to get new strips
     * @param bool $force
     * @return array|\common\models\ComicStrip|null|\yii\mongodb\ActiveRecord
     */
    public function scrapeCron($force = false)
    {
        if (!$this->live) {
            return $this->addScrapeError(
                '{title}({id}) is marked as not live',
                [
                    'title' => $this->title,
                    'id' => (String)$this->_id,
                ]
            );
        }

        $timeToday = (new \DateTime('now'))->setTime(0, 0)->getTimestamp();

        $currentStrip = $this->current();
        $archiveRotated = false;

        if (!$this->active) {
            // Detect if index is at least position
            // If it is then cycle
            if (
                $this->type === self::TYPE_DATE &&
                $currentStrip->index->toDateTime()->getTimestamp() == $this->last_index->toDateTime()->getTimestamp()
            ) {
                $strip = $this->findStrip($this->first_index);
                $archiveRotated = true;
            } elseif (
                $this->type === self::TYPE_ID &&
                $currentStrip->index == $this->last_index
            ) {
                $strip = $this->findStrip($this->first_index);
                $archiveRotated = true;
            }
        }

        if (!$currentStrip) {
            return $this->addScrapeError(
                'Could not find any strip for {title} ({id}) by the index {index}',
                [
                    'title' => $this->title,
                    'id' => (String)$this->_id,
                    'index' => $this->current_index
                ]
            );
        }

        if (
            $currentStrip->date instanceof UTCDateTime &&
            $currentStrip->date->toDateTime()->getTimestamp() === $timeToday
        ) {
            $strip = $currentStrip;
            //return $strip;
        } elseif (!$archiveRotated) {
            if (
                (
                $strip = $this->next(
                    $currentStrip,
                    true,
                    $this->active
                        ? ['date' => new UTCDateTime($timeToday * 1000)]
                        : []
                )
                ) === null
            ) {
                return $this->addScrapeError(
                    '{title} ({id}) could not find next from {url}',
                    [
                        'title' => $this->title,
                        'id' => (String)$this->_id,
                        'url' => $this->scrapeUrl($currentStrip->index)
                    ]
                );
            }
        }

        $this->updateIndex($strip->index, false);
        $this->last_checked = new UTCDateTime($timeToday * 1000);
        if (!$this->save(false, ['last_checked', 'current_index'])) {
            return $this->addScrapeError(
                'Could not save last checked and current_index for {id}',
                ['id' => (String)$this->_id]
            );
        }

        do {
            $has_next = false;
            if ($strip && $this->active && ($strip->next || $force)) {
                $strip = $this->next(
                    $strip,
                    true,
                    $this->active
                        ? ['date' => new UTCDateTime($timeToday * 1000)]
                        : []
                );

                if ($strip) {
                    $this->updateIndex($strip->index, false);
                    $this->last_checked = new UTCDateTime($timeToday * 1000);
                    if (!$this->save(false, ['last_checked', 'current_index'])) {
                        return $this->addScrapeError(
                            'Could not save last checked and current_index for {id}',
                            ['id' => (String)$this->_id]
                        );
                    } else {
                        $has_next = true;
                    }
                }
            }
        } while ($has_next);
    }

    public function getScrapeDom(&$url, $ignoreErrors = false)
    {
        try {
            $res = (new Client)->request(
                'GET',
                $url,
                [
                    'headers' => [
                        'User-Agent' => $this->scraper_user_agent ?: $this->userAgents['Chrome User']
                    ],
                    'on_stats' => function (TransferStats $stats) use (&$url) {
                        $url = $stats->getEffectiveUri();
                    }
                ]
            );
        } catch (RequestException $e) {
            // Log the exception
            return $this->addScrapeError(
                '{id} returned {response} for {url}',
                [
                    'id' => (String)$this->_id,
                    'response' => $e instanceof RequestException && $e->hasResponse()
                        ? $e->getResponse()->getStatusCode()
                        : $e->getMessage(),
                    'url' => $url
                ],
                $ignoreErrors
            );
        }

        $url = $url->__toString();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHtml($res->getBody());
        libxml_clear_errors();
        $el = new \DOMXPath($doc);
        return $el;
    }

    public function addScrapeError($message, $params = [], $ignore = false)
    {
        if (!$ignore) {
            $message = Yii::t('app', $message, $params);
            $this->_scrapeErrors[] = $message;
            Yii::warning($message, 'comic\\' . (String)$this->_id);
        }
        return false;
    }

    public function getScrapeErrors()
    {
        return $this->_scrapeErrors;
    }

    public function clearScrapeErrors()
    {
        $this->_scrapeErrors = [];
    }

    public function indexExist($index)
    {
        try {
            $res = (new Client)->request(
                'GET',
                $this->scrapeUrl($index),
                [
                    'headers' => [
                        'User-Agent' => $this->scraper_user_agent ?: $this->userAgents['Chrome User']
                    ]
                ]
            );
        } catch (ClientException $e) {
            return false;
        }
        return true;
    }

    public function search()
    {
        foreach ($this->attributes() as $field) {
            $this->$field = null;
        }
        if ($get = Yii::$app->getRequest()->get($this->formName())) {
            $this->attributes = $get;
        }

        $query = static::find();
        $query->filterWhere([
            '_id' => $this->_id ? new ObjectID($this->_id) : null,
            'title' => $this->title ? new Regex($this->title) : null,
            'slug' => $this->slug ? new Regex($this->slug) : null,
            'description' => $this->description ? new Regex($this->description) : null,
            'abstract' => $this->abstract ? new Regex($this->abstract) : null,
        ]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    public static function renderStripImage($id)
    {
        if (($pos = strpos($id, '_')) !== false) {
            $parts = explode('_', $id);
            $id = $parts[0];
            $index = $parts[1];
        }

        if ($model = ComicStrip::find()->where(['_id' => new ObjectID($id)])->one()) {
            if (is_array($model->img)) {
                $image = Image::getImagine()->load($model->img[$index]->getData());
            } else {
                $image = Image::getImagine()->load($model->img->getData());
            }

            ob_start();
            $image->show('png');
            $image_raw = ob_get_contents();
            ob_clean();

            \Yii::$app->response->format = yii\web\Response::FORMAT_RAW;
            \Yii::$app->response->data = $image_raw;

        }
        return \Yii::$app->response;
    }
}
