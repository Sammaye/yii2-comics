<?php
namespace frontend\controllers;

use Yii;
use common\models\LoginForm;
use common\models\PasswordResetRequestForm;
use common\models\ResetPasswordForm;
use common\models\SignupForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use common\components\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\User;
use yii\web\HttpException;

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
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
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
    	
    	if(!($authclient = Yii::$app->getRequest()->get('authclient'))){
    		// boom
    		throw new HttpException(403, 'Something went terribly wrong in 
    			the social network and they have given off a bad request');
    	}
    	
    	if($authclient === 'facebook'){
    		
    		// Nomralise googlemail to gmail
    		$gmailEmail = preg_replace('/googlemail.com/', 'gmail.com', $attributes['email']);
    		$googleEmail = preg_replace('/gmail.com/', 'googlemail.com', $attributes['email']);

    		if(
    			!($user = User::find()->where(['facebook_id' => $attributes['id']])->one()) && 
    			!($user = User::find()->where(['or', ['email' => $gmailEmail], ['email' => $googleEmail]])->one())
			){
				// New user
				$user = new User;
				$user->username = $attributes['name'] . rand(100, 3234567);
				$user->email = $attributes['email'];
    		}
    		
    		$user->facebook_id = $attributes['id'];
    		if(!$user->save()){
    			// Boom
    			throw new HttpException(403, 'Could not seem to save your user,
    				you may wish to visit this sites help section for support');
    		}
    	}elseif($authclient === 'google'){
    		
    		$email = null;
    		foreach($attributes['emails'] as $e){
    			if(
    				(
    					preg_match('/googlemail.com/', $e['value']) || 
    					preg_match('/gmail.com/', $e['value']) 
    				) && 
    				$e['type'] === 'account'
				){
    				$email = $e['value'];
    				break;
    			}
    		}
    		
    		if(!$email){
    			// boom
    			throw new HttpException(403, 'Could not seem to get an email for your account');
    		}
    		
    		$gmailEmail = preg_replace('/googlemail.com/', 'gmail.com', $email);
    		$googleEmail = preg_replace('/gmail.com/', 'googlemail.com', $email);
    		
    		if(
    			!($user = User::find()->where(['google_id' => $attributes['id']])->one()) &&
    			!($user = User::find()->where(['or', ['email' => $gmailEmail], ['email' => $googleEmail]])->one())
    		){
    			// New user
    			$user = new User;
    			$user->username = $attributes['displayName'] . rand(100, 3234567);
    			$user->email = $email;
    		}
    		
    		$user->google_id = $attributes['id'];
    		if(!$user->save()){
    			// Boom
    			throw new HttpException(403, 'Could not seem to save your user, 
    				you may wish to visit this sites help section for support');
    		}
    	}else{
    		// Boom again
    		throw new HttpException(403, 'That is not currently a valid sign in method');
    	}
    	
    	// then log them in
    	$model = new LoginForm();
    	$model->email = $user->email;
    	if ($model->login(false)) {
    	} else {
    		// Boom
    		throw new HttpException(403, 'Could not seem to log you in however, everything 
    			else succeeded. You could try again or visit the help section for support');
    	}
    }

    public function actionIndex()
    {
    	if(Yii::$app->getUser()->identity){
    		return $this->redirect(['comic/index']);
    	}else{
        	return $this->render('index');
    	}
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
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }
    
    public function actionConfirmLogin()
    {
    	if (\Yii::$app->user->isGuest) {
    		$this->goHome();
    	}
    	$model = new LoginForm();
    	if ($model->load($_POST) && $model->login()) {
    		return $this->goBack();
    	} else {
    		return $this->render('confirmLogin', [
    			'model' => $model,
    		]);
    	}
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionHelp()
    {
        return $this->render('help');
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

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->getSession()->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}