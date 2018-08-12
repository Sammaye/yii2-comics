<?php
namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use common\components\ActiveRecord;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

class Log extends ActiveRecord
{
    const SCENARIO_SEARCH = 'search';

    public function rules()
    {
        return [
            [
                [
                    '_id',
                    'level',
                    'category',
                    'prefix',
                    'message',
                    'log_time',
                ],
                'safe',
                'on' => [self::SCENARIO_SEARCH]
            ],
        ];
    }

    public function attributes()
    {
        return [
            '_id',
            'level',
            'category',
            'prefix',
            'message',
            'log_time',
        ];
    }

    public function search($comic_id = null)
    {
        foreach ($this->attributes() as $field) {
            $this->$field = null;
        }

        if ($get = Yii::$app->getRequest()->get($this->formName())) {
            $this->attributes = $get;
        }

        if ($comic_id) {
            $this->category = 'comic\\' . (String)$comic_id;
        }

        $query = static::find();
        $query->filterWhere([
            '_id' => $this->_id ? new ObjectID($this->_id) : null,
            'level' => $this->level ? new Regex($this->level) : null,
            'category' => $this->category ? new Regex(preg_quote($this->category, '/')) : null,
            'prefix' => $this->prefix ? new Regex($this->prefix) : null,
            'message' => $this->message ? new Regex(preg_quote($this->message, '/')) : null,
            'created_at' =>
                $this->log_time
                    ? new UTCDateTime(strtotime($this->log_time) * 1000)
                    : null,
        ]);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['log_time' => SORT_DESC]]
        ]);
    }
}
