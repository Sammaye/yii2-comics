<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

require_once(__DIR__ . '/../../vendor/yiisoft/yii2/helpers/ArrayHelper.php');
$commonConfig = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-local.php')
);

return [
    'id' => 'comics-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
    	'session' => [
    		'cookieParams' => [],
    		'name' => 'sess_cookie'
    	],
        'request' => [
            'baseUrl' => '/system',
        	'cookieValidationKey' => $params['request.cookieValidationKey']
        ],
        'urlManager' => $commonConfig['components']['backendUrlManager'],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    'params' => $params,
];
