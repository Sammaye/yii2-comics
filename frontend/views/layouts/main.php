<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use common\widgets\Alert;

$this->beginContent('@app/views/layouts/base.php') ?>
    <div class="wrap<?= isset($this->params['wrapClass']) ? ' ' . $this->params['wrapClass'] : '' ?>">
        <?php
            NavBar::begin([
                'brandLabel' => 'c!y',
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar navbar-fixed-top',
                ],
            ]);
            $menuItems = [
            	['label' => 'View Comics', 'url' => ['/comic']],
                ['label' => 'Help', 'url' => ['/site/help']],
            ];
            if (Yii::$app->user->isGuest) {
                $menuItems[] = ['label' => 'Signup', 'url' => ['/site/signup']];
                $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
            } else {
            	$menuItems[] = [
					'label' => 'Settings',
					'url' => ['user/settings']
            	];
                $menuItems[] = [
                    'label' => 'Logout (' . Yii::$app->user->identity->username . ')',
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ];
            }
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => $menuItems,
            ]);
            NavBar::end();
        ?>

        <?php if(isset($this->params['excludeContainer']) && $this->params['excludeContainer'] === true){ ?>
        <?= $content ?>
        <?php }else{ ?>
        <div class="container">
        <?= Alert::widget() ?>
        <?= $content ?>
        </div>
        <?php } ?>
    </div>
<?php $this->endContent() ?>
