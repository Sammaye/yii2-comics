<?php
use yii\helpers\Html;
use frontend\assets\AppAsset;
use common\widgets\SummariseAsset;

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
        <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,800,700,600' rel='stylesheet' type='text/css'>
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
        <?= $content ?>

        <div class="modal fade" id="ajaxErrorModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">
                            <?= Yii::t('app', 'OMGZ!!! there was an error!!!1') ?>
                        </h4>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger error-message"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?= Yii::t('app', 'Dayum, okay!') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->endBody() ?>

        <script>
            (function (i, s, o, g, r, a, m) {
                i['GoogleAnalyticsObject'] = r;
                i[r] = i[r] || function () {
                        (i[r].q = i[r].q || []).push(arguments)
                    }, i[r].l = 1 * new Date();
                a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
                a.async = 1;
                a.src = g;
                m.parentNode.insertBefore(a, m)
            })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
            ga('create', 'UA-56876004-1', 'auto');
            ga('send', 'pageview');
        </script>
    </body>
</html>
<?php $this->endPage() ?>