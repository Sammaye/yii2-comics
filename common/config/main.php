<?php

$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'name' => 'comics',
	'timeZone' => 'UTC',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'extensions' => require(__DIR__ . '/../../vendor/yiisoft/extensions.php'),
    'controllerMap' => [
        'mongodb-migrate' => 'yii\mongodb\console\controllers\MigrateController'
    ],
    'components' => [
        'log' => [
			'traceLevel' => 3,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
				],
				[
					'class' => 'yii\log\EmailTarget',
					'levels' => ['error'],
					'except' => ['yii\web\HttpException:404'],
					'message' => [
						'from' => [$params['errorEmail'] => 'Comics Errors'],
						'to' => [$params['adminEmail']],
						'subject' => 'comics Website Error',
					],
				],
				[
                    'class' => 'yii\log\EmailTarget',
                    'levels' => ['warning'],
                    'categories' => ['application'],
                    'message' => [
						'from' => [$params['errorEmail'] => 'Comics Errors'],
						'to' => [$params['adminEmail']],
						'subject' => 'comics Website Warning',
                    ],
                ],
	        ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => 'common\components\ses\Mailer',
	        'viewPath' => '@common/mail',
        ],
        'assetManager' => [
	        'bundles' => [
		        'yii\bootstrap\BootstrapAsset' => [
			        'basePath' => '@webroot',
			        'baseUrl' => '@web',
			        'sourcePath' => null,
		        ],
		        'yii\bootstrap\BootstrapPluginAsset' => [
			        'basePath' => '@webroot',
			        'baseUrl' => '@web',
			        'sourcePath' => null,
		        ]
	        ],
        ],
        'authManager' => [
	        'class' => 'yii\mongodb\rbac\MongoDbManager',
	        'defaultRoles' => ['guest'],
        ],
        'frontendUrlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'cache' => null,
            'baseUrl' => '/',
            'rules' => [
                'comic/<id:[\d\w]{24,}>/<date:.[^/]*>'=>'comic/view',

                '<controller:[\w-]+>/<id:[\d\w]{24,}>'=>'<controller>/view',
                '<controller:[\w-]+>/<action:[\w-]+>/<id:[\d\w]{24,}>'=>'<controller>/<action>',
                '<controller:[\w-]+>/<action:[\w-]+>'=>'<controller>/<action>',
            ]
        ],
        'backendUrlManager' => [
            'class' => 'yii\web\UrlManager',
	        'enablePrettyUrl' => true,
	        'showScriptName' => false,
	        'cache' => null,
            'baseUrl' => '/system',
	        'rules' => [
		        '<controller:[\w-]+>/<id:[\d\w]{24,}>'=>'<controller>/view',
		        '<controller:[\w-]+>/<action:[\w-]+>/<id:[\d\w]{24,}>'=>'<controller>/<action>',
		        '<controller:[\w-]+>/<action:[\w-]+>'=>'<controller>/<action>',
	        ]
        ],
        'formatter' => ['class' => 'common\components\Formatter'],
        
        # You will need to setup your environments to add these application keys
        'authClientCollection' => [
	        'class' => 'yii\authclient\Collection',
	        'clients' => [
	        	'google' => [
	        		'class' => 'yii\authclient\clients\GoogleOAuth',
                    'viewOptions' => ['popupWidth' => 900, 'popupHeight' => 700],
        		],
        		'facebook' => [
        			'class' => 'yii\authclient\clients\Facebook',
                    'viewOptions' => ['popupWidth' => 900, 'popupHeight' => 700],
        		],
        	],
        ]
    ],
];
