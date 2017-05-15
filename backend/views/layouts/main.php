<?php
use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use common\widgets\Alert;
use common\widgets\SummariseAsset;

;

/**
 * @var \yii\web\View $this
 * @var string $content
 */
AppAsset::register($this);
SummariseAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php Yii::$app->getResponse()->getHeaders()->set('X-UA-Compatible', 'IE=edge'); ?>
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,800,700,600' rel='stylesheet' type='text/css'>
        <?php $this->head() ?>

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <?php $this->beginBody() ?>

        <div class="wrap">
            <?php
            if (Yii::$app->getUser()->identity) {

                NavBar::begin([
                    'brandLabel' => Yii::t('app', 'Admin'),
                    'brandUrl' => Yii::$app->homeUrl,
                    'options' => [
                        'class' => 'navbar-inverse navbar-fixed-top',
                    ],
                    'innerContainerOptions' => ['class' => 'container-fluid'],
                ]);
                $menuItems = [
                    [
                        'label' => Yii::t('app', 'Users'),
                        'url' => ['user/index']
                    ],
                    [
                        'label' => Yii::t('app', 'Comics'),
                        'url' => ['comic/index']
                    ],
                    [
                        'label' => Yii::t('app', 'Exit'),
                        'url' => Yii::$app->frontendUrlManager->createUrl(['site/index']),
                    ]
                ];
                echo Nav::widget([
                    'options' => ['class' => 'navbar-nav navbar-right'],
                    'items' => $menuItems,
                ]);
                NavBar::end();

            }
            ?>

            <div class="container-fluid<?= Yii::$app->getUser()->identity ? ' container-w-head' : '' ?>">
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </div>

        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
