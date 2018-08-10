<?php
namespace common\components;

class MongoDbTarget extends \yii\mongodb\log\MongoDbTarget
{
    /**
     * Stores log messages to MongoDB collection.
     */
    public function export()
    {

        $rows = [];
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            $rows[] = [
                'level' => $level,
                'category' => $category,
                'log_time' => $timestamp,
                'prefix' => $this->getMessagePrefix($message),
                'message' => $this->formatMessage($message),
            ];
        }

        $this->db->getCollection($this->logCollection)->batchInsert($rows);
    }
}
