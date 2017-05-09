<?php

namespace common\models;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\InvalidParamException;
use common\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use yii\imagine\Image;
use yii\mongodb\validators\MongoDateValidator;
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
    private $_scrapeErrors;

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
            'yii\mongodb\validators\MongoDateValidator',
            'format' => 'php:d/m/Y',
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
                    'dom_path',
                    'current_index'
                ],
                'required'
            ],

            ['title', 'string', 'max' => 250],
            ['slug', 'string', 'max' => 250],
            ['description', 'string', 'max' => 1500],
            ['abstract', 'string', 'max' => 250],
            ['scrape_url', 'string', 'max' => 250],
            ['homepage', 'url'],
            ['author', 'string', 'max' => 400],
            ['author_homepage', 'url'],

            ['type', 'in', 'range' => array_keys($this->getTypes())],
            ['type', 'filter', 'filter' => 'intval'],

            ['scraper', 'in', 'range' => array_keys($this->getScrapers())],
            ['dom_path', 'string', 'max' => 400],

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

    public function getCurrentIndexValue()
    {
        if ($this->current_index != null) {
            if ($this->type === self::TYPE_DATE) {
                return $this->current_index->toDateTime()->format('d/m/Y');
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
                return $this->last_index->toDateTime()->format('d/m/Y');
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
                return $this->first_index->toDateTime()->format('d/m/Y');
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

    public function index($index = null)
    {
        $index = $index ?: $this->current_index;
        if (
            $this->type === self::TYPE_DATE &&
            !$index instanceof UTCDateTime
        ) {
            if (
                (
                    new MongoDateValidator(['format' => 'php:d-m-Y'])
                )->validate($index)
            ) {
                $index = new UTCDateTime(strtotime($index) * 1000);
            } else {
                throw new InvalidParamException(
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
                $index > $this->current_index
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
                $index > $this->current_index
            ) {
                return true;
            }
        } else {
            if (
                $this->type === self::TYPE_DATE &&
                $index->toDateTime()->getTimestamp() > $this->last_index->toDateTime()->getTimestamp()
            ) {
                return true;
            } elseif (
                $this->type === self::TYPE_ID &&
                $index > $this->last_index
            ) {
                return true;
            }
        }
        return false;
    }

    public function scrapeUrl($index)
    {
        switch ($this->type) {
            case self::TYPE_DATE:
                $index = $index->toDateTime()->format($this->index_format);
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

        if ($this->type === self::TYPE_DATE) {
            $strip = $this->getStrip(new UTCDateTime(
                strtotime("-" . ($this->index_step ?: '1 day'), $index->toDateTime()->getTimestamp()) * 1000
            ));
        } elseif ($this->type === self::TYPE_ID) {
            $indexStep = $this->index_step ?: 1;
            if (($index - $indexStep) <= 0) {
                return null;
            }
            $strip = $this->getStrip($index - $indexStep, $data);
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

    public function next(ComicStrip $cStrip, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($cStrip->index);

        $nextIndex = null;
        if ($this->type === self::TYPE_DATE) {
            $nextIndex = new UTCDateTime(
                strtotime("+" . ($this->index_step ?: '1 day'), $index->toDateTime()->getTimestamp()) * 1000
            );
        } elseif ($this->type === self::TYPE_ID) {
            $nextIndex = $index + ($this->index_step ?: 1);
        }

        $nextIndex = $this->index($nextIndex);

        if (
            !$nextIndex ||
            (!$ignoreCurrent && $this->isIndexOutOfRange($nextIndex))
        ) {
            return null;
        }

        if (
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => $nextIndex])
                ->one()
        ) {
            return $strip;
        } else {
            $strip = $this->downloadStrip($nextIndex, $data);

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
    }

    public function current($index = null, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($index ?: $this->current_index);
        if (!$ignoreCurrent && $this->isIndexOutOfRange($index)) {
            return null;
        }
        return $this->getStrip($index, $data);
    }

    public function getStrip($index, array $data = [])
    {
        $index = $this->index($index);
        if (
            $strip = ComicStrip::find()
                ->where(['comic_id' => $this->_id, 'index' => $index])
                ->one()
        ) {
            return $strip;
        } else {
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
        $archiveRotated = false;

        if (!$this->active) {
            // Detect if index is at least position
            // If it is then cycle
            if (
                $this->type === self::TYPE_DATE &&
                $strip->index->toDateTime()->getTimestamp() == $this->last_index->toDateTime()->getTimestamp()
            ) {
                $strip = $this->getStrip($this->first_index);
                $archiveRotated = true;
            } elseif (
                $this->type === self::TYPE_ID &&
                $strip->index == $this->last_index
            ) {
                $strip = $this->getStrip($this->first_index);
                $archiveRotated = true;
            }
        }

        if (!$strip) {
            Yii::warning(
                Yii::t(
                    'app',
                    'Could not find any strip for {title} ({id}) by the index {index}',
                    [
                        'title' => $this->title,
                        'id' => (String)$this->_id,
                        'index' => $this->current_index
                    ]
                )
            );
            return null;
        }

        if (
            $strip->date instanceof UTCDateTime &&
            $strip->date->toDateTime()->getTimestamp() === $timeToday
        ) {
            //return $strip;
        } elseif (!$archiveRotated) {
            if (
                (
                $strip = $this->next(
                    $strip,
                    true,
                    $this->active
                        ? ['date' => new UTCDateTime($timeToday * 1000)]
                        : []
                )
                ) === null
            ) {
                /*
                Yii::warning(
                    'Could not get strip for ' . $comic->title
                    . '(' . (String)$comic->_id . ') by the index '
                    . $index
                );
                */
                return null;
            }
        }

        $this->updateIndex($strip->index, false);
        $this->last_checked = new UTCDateTime($timeToday * 1000);
        if (!$this->save(false, ['last_checked', 'current_index'])) {
            Yii::warning(
                Yii::t(
                    'app',
                    'Could not save last checked and current_index for {id}',
                    ['id' => (String)$this->_id]
                )
            );
        }
        return $strip;
    }

    public function downloadStrip($index, array $data = [])
    {
        $strip = new ComicStrip();
        $strip->comic_id = $this->_id;
        $strip->index = $index;
        foreach ($data as $k => $v) {
            $strip->$k = $v;
        }
        if (!$this->populateStrip($strip) || !$strip->save()) {
            return null;
        }
        return $strip;
    }

    public function populateStrip(&$model, $url = null)
    {
        $imgUrl = null;

        if (!$model->url) {
            $doc = $this->xPath($url ?: $this->scrapeUrl($model->index));
            if (strpos($this->dom_path, '||') !== false) {
                $paths = preg_split('#\|\|#', $this->dom_path);
            } else {
                $paths = [$this->dom_path];
            }

            if ($doc) {
                foreach ($paths as $domPath) {
                    $elements = $doc->query($domPath);
                    if ($elements) {
                        foreach ($elements as $element) {
                            $imgUrl = $element->getAttribute('src');
                        }
                    }
                    if ($imgUrl) {
                        break;
                    }
                }
            }

            if (!$imgUrl) {
                $this->addScrapeError(
                    Yii::t(
                        'app',
                        '{id} could not find img with src for {url}',
                        [
                            'id' => (String)$this->_id,
                            'url' => $this->scrapeUrl($model->index)
                        ]
                    )
                );
                return false;
            }

            $parts = parse_url($imgUrl);

            if ($parts) {
                if (
                    !isset($parts['scheme']) &&
                    isset($parts['host'])
                ) {
                    $imgUrl = 'http://' . trim($imgUrl, '//');
                } elseif (
                    (
                        !isset($parts['scheme']) ||
                        !isset($parts['host'])
                    ) &&
                    isset($parts['path'])
                ) {
                    // The URL is relative as such add the homepage onto the beginning
                    $imgUrl = trim($this->homepage, '/') . '/' . trim($parts['path'], '/');
                }
            }
            $model->url = $imgUrl;
        }

        try {
            if (($model->url) && ($binary = file_get_contents($model->url))) {
                $model->img = new Binary($binary, Binary::TYPE_GENERIC);
                return true;
            }
        } catch (\Exception $e) {
            // the file probably had a problem beyond our control
            // As such define this as a skip strip since I cannot store it
            $model->skip = 1;
            return true;
        }
        return false;
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
            return $image->show('png');
        }
        return '';
    }

    public function indexExist($index)
    {
        try {
            $res = (new Client)->request(
                'GET',
                $this->scrapeUrl($index),
                [
                    'headers' => [
                        'User-Agent' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)'
                    ]
                ]
            );
        } catch (ClientException $e) {
            return false;
        }
        return true;
    }

    public function xPath($url, $ignoreErrors = false)
    {
        try {
            $res = (new Client)->request(
                'GET',
                $url,
                [
                    'headers' => [
                        'User-Agent' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)'
                    ]
                ]
            );
        } catch (ClientException $e) {
            // Log the exception
            $this->addScrapeError(
                (String)$this->_id . ' returned ' .
                $e->getResponse()->getStatusCode()
                . ' for ' . $url,
                $ignoreErrors
            );
            return null;
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHtml($res->getBody());
        libxml_clear_errors();
        $el = new \DOMXPath($doc);
        return $el;
    }

    public function addScrapeError($message, $ignore = false)
    {
        if (!$ignore) {
            $this->_scrapeErrors[] = $message;
            Yii::warning($message);
        }
    }

    public function getScrapeErrors()
    {
        return $this->_scrapeErrors;
    }

    public function clearScrapeErrors()
    {
        $this->_scrapeErrors = [];
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
}