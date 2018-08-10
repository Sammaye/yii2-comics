<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use common\components\Controller;
use yii\base\DynamicModel;
use yii\helpers\Json;
use common\models\Comic;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;

class ComicController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['subscribe', 'unsubscribe'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'request', 'render-image'],
                        'roles' => ['?', '@']
                    ]
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->actionView();
    }

    public function actionView($id = null, $index = null)
    {
        $this->layout = 'tabbedComics';

        /**
         * @var Comic
         */
        $comic = null;
        $condition = ['live' => 1];

        if ($id) {
            $condition['_id'] = new ObjectID($id);
        }

        if (
            !(
                $comic = Comic::find()
                    ->where($condition)
                    ->orderBy(['title' => SORT_ASC])
                    ->one()
            )
        ) {
            return $this->render('comicNotFound');
        }

        if (!$current = $comic->current($index)) {
            return $this->render('comicStripNotFound', ['model' => $comic]);
        }

        $current->comic = $comic;
        $previous = $comic->previous($current);
        $next = $comic->next($current);

        return $this->render(
            'view',
            [
                'model' => $comic,
                'comicStrip' => $current,
                'previousStrip' => $previous,
                'nextStrip' => $next,
            ]
        );
    }

    public function actionSubscribe()
    {
        if (
            ($comic_id = Yii::$app->getRequest()->get('comic_id')) &&
            ($model = Comic::find()->where(['_id' => new ObjectID($comic_id)])->one())
        ) {
            $user = Yii::$app->user->identity;
            if ($user->addComic($model->_id)) {
                return Json::encode([
                    'success' => true,
                    'message' => Yii::t(
                        'app',
                        'You subscribed to {title}',
                        ['title' => $model->title]
                    )
                ]);
            }
            return Json::encode([
                'success' => false,
                'message' => Yii::t(
                    'app',
                    'Unknown error'
                )
            ]);
        }
        return Json::encode([
            'success' => false,
            'message' => Yii::t(
                'app',
                'Comic not found'
            )
        ]);
    }

    public function actionUnsubscribe()
    {
        if (
            ($comic_id = Yii::$app->getRequest()->get('comic_id')) &&
            ($model = Comic::find()->where(['_id' => new ObjectID($comic_id)])->one())
        ) {
            $user = Yii::$app->user->identity;
            if ($user->removeComic($model->_id)) {
                return Json::encode([
                    'success' => true,
                    'message' => Yii::t(
                        'app',
                        'You unsubscribed from {title}',
                        ['title' => $model->title]
                    )
                ]);
            }
            return Json::encode([
                'success' => false,
                'message' => Yii::t(
                    'app',
                    'Unknown error'
                )
            ]);
        }
        return Json::encode([
            'success' => false,
            'message' => Yii::t(
                'app',
                'Comic not found'
            )
        ]);
    }

    public function actionRequest()
    {
        $model = $this->comicRequestForm();

        if ($model->load($_POST) && $model->validate()) {
            /// send email
            Yii::$app->mailer
                ->compose('requestComic', ['model' => $model])
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['supportName']])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject(
                    Yii::t(
                        'app',
                        "Comic Request for Sammaye's Comics"
                    )
                )
                ->send();
            return Json::encode([
                'success' => true,
                'message' => Yii::t(
                    'app',
                    'Request has been received, thank you!'
                )
            ]);
        } else {
            return Json::encode([
                'success' => false,
                'errors' => $model->getErrors(),
                'message' => Yii::t(
                    'app',
                    'Could not send your request because:'
                )
            ]);
        }
    }

    public function actionRenderImage($id)
    {
        return Comic::renderStripImage($id);
    }

    public function comicRequestForm()
    {
        return (
            new DynamicModel([
                'url',
                'name',
                'email'
            ])
        )
            ->addRule(['url', 'name'], 'required')
            ->addRule('url', 'url')
            ->addRule('email', 'email')
            ->addRule('name', 'string', ['max' => 350]);
    }
}
