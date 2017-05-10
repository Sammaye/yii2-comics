<?php

namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use common\components\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use common\models\User;
use common\models\LoginForm;
use common\models\PasswordResetRequestForm;
use common\models\ResetPasswordForm;
use common\models\SignupForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successCallback'],
            ],
        ];
    }

    public function successCallback($client)
    {
        $attributes = $client->getUserAttributes();
        $token = $client->getAccessToken();

        if (!($authclient = Yii::$app->getRequest()->get('authclient'))) {
            // boom
            throw new HttpException(
                403,
                Yii::t(
                    'app',
                    'Social network returned a bad request'
                )
            );
        }

        if ($authclient === 'facebook') {

            $field = 'facebook_id';
            $id = ArrayHelper::getValue($attributes, 'id');
            $username = Inflector::slug(
                ArrayHelper::getValue($attributes, 'name') . rand(100, 3234567),
                ''
            );
            $email = ArrayHelper::getValue($attributes, 'email');
            if (!$email) {
                Yii::$app->session->setFlash(
                    'error',
                    Yii::t(
                        'app',
                        'Your Facebook account has no email, please add one'
                    )
                );
                return Url::to(['site/signup']);
            }

        } elseif ($authclient === 'google') {

            $field = 'google_id';
            $id = ArrayHelper::getValue($attributes, 'id');
            $username = Inflector::slug(
                ArrayHelper::getValue($attributes, 'displayName') . rand(100, 3234567),
                ''
            );
            $email = null;

            $emails = ArrayHelper::getValue($attributes, 'emails');
            foreach ($emails as $e) {
                if (
                    (
                        preg_match('/googlemail.com/', $e['value']) ||
                        preg_match('/gmail.com/', $e['value'])
                    ) &&
                    $e['type'] === 'account'
                ) {
                    $email = $e['value'];
                    break;
                }
            }

            if (!$email) {
                Yii::$app->session->setFlash(
                    'error',
                    Yii::t(
                        'app',
                        'Your Google account has no email, please add one'
                    )
                );
                return Url::to(['site/signup']);
            }
        } else {
            // Boom again
            throw new HttpException(
                403,
                Yii::t(
                    'app',
                    'That is not currently a valid sign in method'
                )
            );
        }

        // Nomralise googlemail to gmail
        $gmailEmail = preg_replace('/googlemail.com/', 'gmail.com', $email);
        $googleEmail = preg_replace('/gmail.com/', 'googlemail.com', $email);

        if (
            !($user = User::find()->where([$field => $attributes['id']])->one()) &&
            !($user = User::find()->where(['email' => ['$in' => [$gmailEmail, $googleEmail]]])->one())
        ) {
            // New user
            $user = new User;
            $user->username = $username;
            $user->email = $email;
        }

        $user->$field = $id;

        if (!$user->save()) {
            // Boom
            throw new HttpException(
                403,
                Yii::t(
                    'app',
                    'Could not save your data, visit the help section for support'
                )
            );
        }

        // then log them in
        $model = new LoginForm();
        $model->email = $user->email;
        if ($model->login(false)) {
        } else {
            // Boom
            throw new HttpException(
                403,
                Yii::t(
                    'app',
                    'Could not log you in. Try again or visit the help section for support'
                )
            );
        }
    }

    public function actionIndex()
    {
        if (Yii::$app->getUser()->identity) {
            return $this->redirect(['comic/index']);
        } else {
            return $this->render('index');
        }
    }

    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->redirect(['comic/index']);
                }
            }
        }

        return $this->render(
            'signup',
            [
                'model' => $model,
            ]
        );
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render(
                'login',
                [
                    'model' => $model,
                ]
            );
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash(
                    'success',
                    Yii::t(
                        'app',
                        'Check your email for further instructions'
                    )
                );

                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash(
                    'error',
                    Yii::t(
                        'app',
                        'Sorry, we are unable to reset password for email provided'
                    )
                );
            }
        }

        return $this->render(
            'requestPasswordResetToken',
            [
                'model' => $model,
            ]
        );
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if (
            $model->load(Yii::$app->request->post()) &&
            $model->validate() &&
            $model->resetPassword()
        ) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t(
                    'app',
                    'New password was saved'
                )
            );
            return $this->goHome();
        }

        return $this->render(
            'resetPassword',
            [
                'model' => $model,
            ]
        );
    }


    public function actionHelp()
    {
        return $this->render('help');
    }
}