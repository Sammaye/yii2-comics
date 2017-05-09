<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Comic;

if ($model->type === Comic::TYPE_DATE) {
    $this->title = Yii::t(
        'app',
        'View {title} from {index}',
        ['title' => $model->title, 'index' => $comicStrip->index->toDateTime()->format('d-m-Y')]
    );
    $this->registerJs("
	$('#datepicker').datepicker({
		dateFormat : 'dd-mm-yy',
		changeMonth: true,
		changeYear: true,
		maxDate: '" . $model->getLatestIndexValue() . "'
	});
	");
} else {
    $this->title = Yii::t(
        'app',
        'View {title} for {index}',
        ['title' => $model->title, 'index' => $comicStrip->index]
    );
}

$this->registerJs("
$('#datepicker').on('change', function(e){
	$(this).parents('form').submit();
});

$(document).on('click', '.btn-subscribe', function(e){
	e.preventDefault();
	$.get('" . Url::to(['comic/subscribe']) . "', {comic_id: '" . (String)$model->_id . "'}, null, 'json')
	.done(function(data){
		if(data.success){
			var btn = $('.btn-subscribe');
			btn.find('span').removeClass('glyphicon-ok').addClass('glyphicon-remove');
			btn.get(0).lastChild.nodeValue = ' Remove from email';
			btn.addClass('btn-unsubscribe btn-danger').removeClass('btn-subscribe btn-success');
		}
	});
});

$(document).on('click', '.btn-unsubscribe', function(e){
	e.preventDefault();
	$.get('" . Url::to(['comic/unsubscribe']) . "', {comic_id: '" . (String)$model->_id . "'}, null, 'json')
	.done(function(data){
		if(data.success){
			var btn = $('.btn-unsubscribe');
			btn.find('span').removeClass('glyphicon-remove').addClass('glyphicon-ok')
			btn.get(0).lastChild.nodeValue = ' Add to my email';
			btn.addClass('btn-subscribe btn-success').removeClass('btn-unsubscribe btn-danger');
		}
	});
});
");

$this->params['comic_id'] = (String)$model->_id;

?>
<div class="comic-info-outer">
    <div class="row">
        <div class="col-md-35 col-sm-30">
            <?php if ($model->description) {
                echo Html::tag('p', $model->description);
            } ?>
            <?php
            if ($model->author || $model->homepage) {
                echo Html::beginTag('p', ['class' => 'text-muted']);
                if ($model->author) {
                    if (!$model->author_homepage) {
                        echo Yii::t('app', 'By {name}', ['name' => $model->author]);
                    } else {
                        echo Yii::t(
                            'app',
                            'By {name}',
                            ['name' => Html::a(
                                $model->author,
                                $model->author_homepage,
                                ['rel' => 'nofollow', 'target' => '_blank']
                            )]
                        );
                    }
                }
                if ($model->homepage) {
                    echo Html::a(
                        Yii::t('app', 'Homepage'),
                        $model->homepage,
                        ['class' => 'comic-homepage', 'rel' => 'nofollow', 'target' => '_blank']
                    );
                }
                echo Html::endTag('p');
            } ?>
        </div>
        <div class="col-md-10 col-md-push-2 col-sm-18">
            <?php
            if (
                ($user = Yii::$app->getUser()->identity) &&
                ($user->hasComic($model->_id))
            ) {
                ?>
                <a href="#" class="btn btn-lg btn-danger btn-unsubscribe">
                    <span class="glyphicon glyphicon-remove"></span>
                    <?= Yii::t('app', 'Remove from email') ?>
                </a>
            <?php } else { ?>
                <a href="#" class="btn btn-lg btn-success btn-subscribe">
                    <span class="glyphicon glyphicon-ok"></span>
                    <?= Yii::t('app', 'Add to my email') ?>
                </a>
            <?php } ?>
        </div>
    </div>
</div>

<div class="comic-date-picker">
    <form method="get" action="<?= Url::to(['comic/view', 'id' => (String)$model->_id]) ?>">
        <div>
            <?php if ($previousStrip) { ?>
                <a href="<?= $model->indexUrl($previousStrip->index) ?>" class="btn btn-lg btn-default">&laquo;</a>
            <?php } else { ?>
                <a href="#" disabled="disabled" class="btn btn-lg btn-default">&laquo;</a>
            <?php } ?>

            <input
                type="text"
                class="form-control input-lg"
                name="index"
                id="datepicker"
                value="<?= $model->type === Comic::TYPE_DATE ? $comicStrip->index->toDateTime()->format('d-m-Y') : $comicStrip->index ?>"
            />

            <?php if ($nextStrip) { ?>
                <a href="<?= $model->indexUrl($nextStrip->index) ?>" class="btn btn-lg btn-default">&raquo;</a>
            <?php } else { ?>
                <a href="#" disabled="disabled" class="btn btn-lg btn-default">&raquo;</a>
            <?php } ?>
        </div>
    </form>
</div>
<div class="comic-view-item">
    <?php if ($comicStrip->skip) { ?>
        <div class="strip-not-archived">
            <a href="<?= $comicStrip->url ?>" target="_blank" rel="nofollow">
                <?= Yii::t(
                    'app',
                    'This strip is not compatible with c!y but you can click here to view it on their site'
                ) ?>
            </a>
        </div>
    <?php } elseif (is_array($comicStrip->img)) {
        ?><a href="<?= $model->scrapeUrl($comicStrip->index) ?>" rel="nofollow" target="_blank">
        <?php foreach ($comicStrip->img as $k => $img) { ?>
            <img
                src="<?= Url::to(['comic/render-image', 'id' => (String)$comicStrip->_id . '_' . $k]) ?>"
                class="img-responsive comic-img"
            />
        <?php } ?>
        </a><?php
    } else { ?>
        <a href="<?= $model->scrapeUrl($comicStrip->index) ?>" rel="nofollow" target="_blank">
            <img
                src="<?= Url::to(['comic/render-image', 'id' => (String)$comicStrip->_id]) ?>"
                class="img-responsive comic-img"
            />
        </a>
    <?php } ?>
</div>