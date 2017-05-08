<?php

use yii\helpers\Html;

Yii::$app->response->setStatusCode(404);

$this->title = Yii::t('app', '#404 ZOMG Comic Not Found!');
?>
<div class="comic-view-not-found">
    <div class="alert alert-danger">
        <?= Yii::t('app', 'Comic was not found on this site') ?>
    </div>
    <p><?= Yii::t(
        'app',
        'If you would like to see this comic be added to this site you can {url}',
        [
            'url' => Html::a(
                Yii::t('app', 'demand it!'),
                '#',
                [
                    'data-toggle' => 'modal',
                    'data-target' => '.request-comic-modal'
                ]
            )
        ]
    ) ?></p>
</div>