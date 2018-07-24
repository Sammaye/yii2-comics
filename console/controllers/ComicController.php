<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;
use common\models\Comic;
use common\models\User;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use yii\console\ExitCode;

class ComicController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->getUrlManager()->setBaseUrl('https://sammaye.com/comics/');
        Yii::$app->getUrlManager()->setHostInfo('https://sammaye.com/comics/');

        return parent::beforeAction($action);
    }

    public function actionForceScrape($comic_id = null)
    {
        return $this->actionScrape($comic_id, true);
    }

    public function actionScrape($comic_id = null, $force = false)
    {
        if ($comic_id) {
            $comic_id = $comic_id instanceof ObjectID
                ? $comic_id
                : new ObjectID($comic_id);

            if (
                $comic = Comic::find()
                    ->where(['_id' => $comic_id, 'live' => 1])
                    ->one()
            ) {
                $strip = $comic->scrapeStrip();

                $timeToday = (new \DateTime('now'))->setTime(0, 0)->getTimestamp();
                do {
                    $has_next = false;
                    if ($strip && $comic->active && ($strip->next || $force)) {
                        $strip = $comic->next(
                            $strip,
                            true,
                            $comic->active
                                ? [
                                    'date' => new UTCDateTime($timeToday * 1000)
                                ]
                                : []
                        );

                        if ($strip) {
                            $comic->updateIndex($strip->index, false);
                            $comic->last_checked = new UTCDateTime($timeToday * 1000);
                            if (!$comic->save(false, ['last_checked', 'current_index'])) {
                                Yii::warning(
                                    Yii::t(
                                        'app',
                                        'Could not save last checked and current_index for {id}',
                                        ['id' => (String)$this->_id]
                                    )
                                );
                            } else {
                                $has_next = true;
                            }
                        }
                    }
                } while ($has_next);
                return ExitCode::OK;
            } else {
                Yii::error(
                    Yii::t(
                        'app',
                        'Could not find comic {id}',
                        ['id' => (String)$comic->_id]
                    )
                );
                return ExitCode::UNSPECIFIED_ERROR;
            }

        } else {
            foreach (
                Comic::find()
                    ->where(['live' => 1])
                    ->each()
                as $comic
            ) {
                $strip = $comic->scrapeStrip();

                $timeToday = (new \DateTime('now'))->setTime(0, 0)->getTimestamp();
                do {
                    $has_next = false;
                    if ($strip && $comic->active && ($strip->next || $force)) {
                        $strip = $comic->next(
                            $strip,
                            true,
                            $comic->active
                                ? [
                                    'date' => new UTCDateTime($timeToday * 1000)
                                ]
                                : []
                        );

                        if ($strip) {
                            $comic->updateIndex($strip->index, false);
                            $comic->last_checked = new UTCDateTime($timeToday * 1000);
                            if (!$comic->save(false, ['last_checked', 'current_index'])) {
                                Yii::warning(
                                    Yii::t(
                                        'app',
                                        'Could not save last checked and current_index for {id}',
                                        ['id' => (String)$this->_id]
                                    )
                                );
                            } else {
                                $has_next = true;
                            }
                        }
                    }
                } while ($has_next);
            }
            return ExitCode::OK;
        }
    }

    public function actionEmail($freq = null)
    {
        $timeToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $condition = [
            '$or' =>
                [
                    ['last_feed_sent' => ['$lt' => new UTCDateTime($timeToday * 1000)]],
                    ['last_feed_sent' => null]
                ]
        ];

        if ($freq) {
            if ($freq === 'daily' || $freq === 'weekly' || $freq === 'monthly') {
                $condition['email_frequency'] = $freq;
            } else {
                Yii::error(
                    Yii::t(
                        'app',
                        'Frequency must be either daily, weekly, or monthly'
                    )
                );
            }
        }

        foreach (
            User::find()
                ->where($condition)
                ->orderBy(['_id' => SORT_ASC])
                ->each()
            as $user
        ) {
            if (
                $user->last_feed_sent instanceof UTCDateTime &&
                $user->last_feed_sent->toDateTime()->getTimestamp() === $timeToday
            ) {
                continue;
            }
            $user->last_feed_sent = new UTCDateTime($timeToday * 1000);

            $strips = [];

            if ($user->email_frequency == 'weekly') {
                $timeAgo = strtotime('7 days ago', $timeToday);
            } elseif ($user->email_frequency == 'monthly') {
                $timeAgo = strtotime('1 month ago', $timeToday);
            } else {
                $timeAgo = strtotime('1 day ago', $timeToday);
            }

            if (!is_array($user->comics)) {
                return false;
            }

            foreach ($user->comics as $sub) {
                if (
                    $comic = Comic::find()
                        ->where(['_id' => $sub['comic_id'], 'live' => 1])
                        ->one()
                ) {
                    if ($comic->active) {
                        $condition = [
                            'comic_id' => $comic->_id,
                            'date' => ['$gt' => new UTCDateTime($timeAgo * 1000)]
                        ];
                    } else {
                        $condition = [
                            'comic_id' => $comic->_id,
                            'index' => $comic->current_index
                        ];
                    }

                    if (
                        $strip = ComicStrip::find()
                            ->where($condition)
                            ->orderBy(['date' => SORT_DESC])
                            ->one()
                    ) {
                        $strip->comic = $comic;
                        $strips[] = $strip;
                    }
                }
            }

            // Else let's just ignore it silently
            if (!$user->save(false, ['last_feed_sent'])) {
                Yii::warning(
                    Yii::t(
                        'app',
                        'User: {id} could not be saved',
                        ['id' => (String)$user->_id]
                    )
                );
            }

            Yii::$app->getMailer()
                ->compose('comicFeed', ['strips' => $strips])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['supportName']])
                ->setTo($user->email)
                ->setSubject(
                    Yii::t(
                        'app',
                        'Your comics Feed for {date}',
                        ['date' => date('d-m-Y')]
                    )
                )
                ->send();
        }
        return ExitCode::OK;
    }

    public function actionCheckTimeOfNew($comic_id)
    {
        if (
            !(
                $comic = Comic::find()
                    ->where(['_id' => new ObjectID($comic_id)])
                    ->one()
            )
        ) {
            Yii::error(
                Yii::t('app', 'That comic could not be found')
            );
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $strip = $comic->current();
        $index = $comic->next(
            $strip,
            true
        )->index;

        while (true) {
            if ($comic->indexExist($index)) {
                Yii::info(
                    Yii::t(
                        'app',
                        'Index {index} exists',
                        [
                            'index' => $comic->type === self::TYPE_DATE
                                ? date('d-m-Y', $index->toDateTime()->getTimestamp())
                                : $index
                        ]
                    )
                );
                return ExitCode::OK;
            }
            sleep(3600);
        }
    }
}
