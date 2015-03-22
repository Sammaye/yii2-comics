<?php
namespace common\widgets;

use Yii;
use yii\web\AssetBundle;

class Select2Asset extends AssetBundle
{
	public $css = ['css/select2.css'];
	public $js = ['js/select2.js'];
	public $depends = [
		'yii\web\JqueryAsset'
	];
	public $version = '3.4.5';
	public $sourcePath = '@vendor/ivaynberg/select2/dist';
	
	public function init()
	{
	}
}