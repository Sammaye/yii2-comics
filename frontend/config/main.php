<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

$commonConfig = array_merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-local.php')
);

return [
    'id' => 'cly-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
    	'session' => [
    		'cookieParams' => [],
    		'name' => 'sess_cookie'
    	],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'request' => [
        	'cookieValidationKey' => $params['request.cookieValidationKey']
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => $commonConfig['components']['frontendUrlManager'],
    ],
    'params' => $params,
];
