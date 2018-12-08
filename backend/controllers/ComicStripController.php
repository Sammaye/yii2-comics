<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\Comic;
use common\models\ComicStrip;
use MongoDB\BSON\ObjectID;

class ComicStripController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['staff'],
                    ],
                ],
            ],
        ];
    }

    public function actionCreate($comic_id)
    {
        $model = new ComicStrip([
            'comic_id' => new ObjectID($comic_id)
        ]);

        if ($model->load($_POST)) {
            if ($model->validate()) {
                // This currently done without care as to its outcome
                if (!$model->skip) {
                    $model->comic->scrapeStrip($model);
                    if (count($model->comic->getScrapeErrors()) > 0) {
                        foreach ($model->comic->getScrapeErrors() as $error) {
                            $model->addError('url', $error);
                        }
                    }
                } else {
                    $model->url = $model->comic->scrapeUrl($model->index);
                }

                if (count($model->getErrors()) <= 0 && $model->save(false)) {
                    return $this->redirect(['comic/update', 'id' => (string)$model->comic_id]);
                }
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        if ($model = ComicStrip::find()->where(['_id' => new ObjectID($id)])->one()) {
            if ($model->load($_POST) && $model->save()) {
                return $this->redirect(['comic/update', 'id' => $model->comic_id]);
            }
            return $this->render('update', ['model' => $model]);
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function actionDelete($id)
    {
        if (
            ($model = ComicStrip::find()->where(['_id' => new ObjectID($id)])->one()) &&
            $model->delete()
        ) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('app', 'Strip deleted')
            );
        } else {
            Yii::$app->getSession()->setFlash(
                'error',
                Yii::t('app', 'Unknown error')
            );
        }
        return $this->redirect(['comic/update', 'id' => (String)$model->comic_id]);
    }

    public function actionRefreshScrape($id)
    {
        if ($model = ComicStrip::find()->where(['_id' => new ObjectID($id)])->one()) {
            $model->url = null;
            $model->img = null;
            if ($model->comic->scrapeStrip($model) && $model->save()) {
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t(
                        'app',
                        'The scrape information for this strip was refreshed'
                    )
                );
                return $this->redirect(['update', 'id' => $id]);
            }
        }
        Yii::$app->getSession()->setFlash(
            'error',
            Yii::t(
                'app',
                'The scrape information for this strip was not refreshed'
            )
        );
        return $this->redirect(['update', 'id' => $id]);
    }

    public function actionRenderImage($id)
    {
        return Comic::renderStripImage($id);
    }
}
