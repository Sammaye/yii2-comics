<?php

namespace common\models;

use Yii;
use yii\base\Model;

class RequestComicForm extends Model
{
	public $email;
	public $url;
	public $name;
	
	public function rules()
	{
		return [
			[['url', 'name'], 'required'],
			['url', 'url'],
			['email', 'email'],
			['name', 'string', 'max' => 350]
		];
	}
	
	public function attributeLabels()
	{
		return [
			'name' => 'Comic Name',
			'url' => 'Comic Url'
		];
	}
}