<?php

namespace common\models;

use Yii;
use common\components\ActiveRecord;
use yii\data\ActiveDataProvider;
use common\models\Comic;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

class ComicStrip extends ActiveRecord
{
    const SCENARIO_SEARCH = 'search';

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
        $index = $next = $previous = [
            '',
            'common\components\MongoDateValidator',
            'format' => 'php:' . Yii::$app->getFormatter()->fieldDateFormat,
            'mongoDateAttribute' => '',
            'when' => function ($model) {
                return $model->comic->type === Comic::TYPE_DATE;
            },
            'whenClient' => "function (attribute, value) {
        		return $('#comic-type').val() == '" . Comic::TYPE_DATE . "';
    		}"
        ];

        $index[0] = $index['mongoDateAttribute'] = 'index';
        $next[0] = $next['mongoDateAttribute'] = 'next';
        $previous[0] = $previous['mongoDateAttribute'] = 'previous';

        $rules = [
            [['comic_id'], 'required'],
            ['comic_id', 'yii\mongodb\validators\MongoIdValidator', 'forceFormat' => 'object'],

            ['url', 'string', 'max' => 250],
            ['image_url', 'string', 'max' => 250],

            ['skip', 'integer', 'min' => 0, 'max' => 1],
            ['skip', 'filter', 'filter' => 'intval'],

            $index,
            $next,
            $previous,

            /*
            [
                ['index', 'next', 'previous'],
                'integer',
                'when' => function($model){
                    return $model->comic->type === Comic::TYPE_ID;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#comic-type').val() == '" . Comic::TYPE_ID . "';
                }"
            ],
            */
            [
                ['index', 'next', 'previous'],
                'filter',
                'filter' => function ($value) {
                    return (String)$value;
                },
                'when' => function ($model) {
                    return $model->comic->type === Comic::TYPE_ID;
                },
                'whenClient' => "function (attribute, value) {
        			return $('#comic-type').val() == '" . Comic::TYPE_ID . "';
    			}"
            ],
            ['index', 'unique', 'targetAttribute' => ['index', 'comic_id']],

            [
                'date',
                'common\components\MongoDateValidator',
                'format' => 'php:' . Yii::$app->getFormatter()->fieldDateFormat,
                'mongoDateAttribute' => 'date',
                'max' => (new \DateTime('now'))->format(Yii::$app->getFormatter()->fieldDateFormat),
                'min' => (new \DateTime('1600-01-01'))->format(Yii::$app->getFormatter()->fieldDateFormat),
            ],

            [
                [
                    '_id',
                    'comic_id',
                    'url',
                    'image_url',
                    'index',
                    'updated_at',
                    'created_at'
                ],
                'safe',
                'on' => [self::SCENARIO_SEARCH]
            ],
        ];
        return $rules;
    }

    public function attributes()
    {
        return [
            '_id',
            'comic_id',
            'url',
            'image_url',
            'img',
            'image_md5',
            'index',
            'skip',
            'date',
            'next',
            'previous',
            'updated_at',
            'created_at'
        ];
    }

    public function attributeLabels()
    {
        return [
            'skip' => Yii::t('app', 'Do not download this strip')
        ];
    }

    public function setComic($rows)
    {
        return $this->populateRelation('comic', $rows);
    }

    public function getComic()
    {
        return $this->hasOne(Comic::class, ['_id' => 'comic_id']);
    }

    public function search($comic_id)
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
            'comic_id' => $comic_id,
            'url' => $this->url ? new Regex($this->url) : null,
        ]);

        if ($this->comic->type === Comic::TYPE_DATE) {
            $query->filterWhere([
                'index' =>
                    $this->index
                        ? new UTCDateTime(strtotime($this->index) * 1000)
                        : null
            ]);
        } elseif ($this->comic->type === Comic::TYPE_ID) {
            $query->filterWhere([
                'index' => $this->index ? $this->index : null
            ]);
        }

        $query->filterWhere([
            'created_at' =>
                $this->created_at
                    ? new UTCDateTime(strtotime($this->created_at) * 1000)
                    : null,
            'updated_at' =>
                $this->updated_at
                    ? new UTCDateTime(strtotime($this->updated_at) * 1000)
                    : null
        ]);
        return new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_DESC]]
        ]);
    }
}
