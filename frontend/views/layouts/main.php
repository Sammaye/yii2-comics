<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use common\widgets\Alert;

$this->beginContent('@app/views/layouts/base.php') ?>
<div class="wrap<?= isset($this->params['wrapClass']) ? ' ' . $this->params['wrapClass'] : '' ?>">
    <?php
        NavBar::begin([
            'brandLabel' => Yii::t('app', 'comics'),
            'brandUrl' => Yii::$app->homeUrl,
            'options' => [
                'class' => 'navbar navbar-fixed-top',
            ],
        ]);
        $menuItems = [
            ['label' =>
                Yii::t('app', 'View Comics'),
                'url' => ['/comic']
            ],
            ['label' =>
                Yii::t('app', 'Help'),
                'url' => ['/site/help']
            ],
        ];
        if (Yii::$app->user->isGuest) {
            $menuItems[] = [
                'label' => Yii::t('app', 'Signup'),
                'url' => ['/site/signup']
            ];
            $menuItems[] = [
                'label' => Yii::t('app', 'Login'),
                'url' => ['/site/login']
            ];
        } else {
            $menuItems[] = [
                'label' => Yii::t('app', 'Settings'),
                'url' => ['user/update']
            ];
            $menuItems[] = [
                'label' => Yii::t(
                    'app',
                    'Logout ({username})',
                    ['username' => Yii::$app->user->identity->username]
                ),
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

    <?php if (isset($this->params['excludeContainer']) && $this->params['excludeContainer'] === true) { ?>
        <?= $content ?>
    <?php } else { ?>
        <div class="container">
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    <?php } ?>
</div>
<?php $this->endContent() ?>
