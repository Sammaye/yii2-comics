<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
    	'session' => [
    		'cookieParams' => [],
    		'name' => 'sess_cookie'
    	],
        'request' => [
        	'class' => 'common\components\Request',
        	'enableCsrfValidation' => true,
        	'csrfRoutes' => [
		        'site/login',
		        'site/signup',
		        'site/request-password-reset',
		        'site/reset-password'
        	],
        	'cookieValidationKey' => $params['request.cookieValidationKey']
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    'params' => $params,
];
