<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use common\components\ActiveRecord;
use yii\base\Security;
use yii\web\IdentityInterface;
use yii\data\ActiveDataProvider;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;

	const ROLE_USER = 'user';
	const ROLE_AFFILIATE = 'affiliate';
	const ROLE_STAFF = 'staff';
	const ROLE_ADMIN = 'admin';
	const ROLE_GOD = 'god';
	
	public $newPassword;
	public $oldPassword;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function($e){ return new \MongoDate(); }
            ],
        ];
    }

    /**
      * @inheritdoc
      */
     public function rules()
     {
         return [
             ['username', 'string', 'min' => 3, 'max' => 20],
             ['username', 'unique'],
             
             ['email', 'email'],
             ['email', 'unique'],
         
             ['status', 'default', 'value' => self::STATUS_ACTIVE],
             ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],

             ['role', 'default', 'value' => self::ROLE_USER],
             ['role', 'in', 'range' => [self::ROLE_USER, self::ROLE_STAFF, self::ROLE_AFFILIATE, self::ROLE_ADMIN, self::ROLE_GOD]],
             
             ['oldPassword', 'string', 'max' => 20, 'min' => 7],
             ['oldPassword', 'validateOldPassword'],
             
             ['newPassword', 'string', 'max' => 20, 'min' => 7],
             ['newPassword', 'validateNewPassword'],
             
             ['email_frequency', 'in', 'range' => array_keys($this->emailFrequencies())],
             
             [
             	[
             		'_id',
					'username',
					'email',
					'role',
					'status',
					'updated_at',
					'created_at'
				],
             	'safe',
             	'on' => 'search'
       		]
         ];
     }
     
     public function attributes()
     {
     	return [
     		'_id',
			'username',
			'password_hash',
			'password_reset_token',
			'email',
			'auth_key',
			'role',
			'status',
			'created_at',
			'updated_at',
			'comics',
			'email_frequency',
			'facebook_id',
			'google_id',
			'facebook_token',
			'testArray',
     	];
     }
     
     public function subdocuments()
     {
     	return [
			'testArray'
     	];
     }
     
     public function emailFrequencies()
     {
     	return [
     		'daily' => 'Daily', 
     		'weekly' => 'Weekly', 
     		'monthly' => 'Monthly', 
     		'paused' => 'Paused'
		];
     }
     
     public function validateOldPassword($attribute, $params)
     {
     	if(strlen(trim($this->oldPassword)) > 0){
     		if(strlen(trim($this->newPassword)) <= 0){
     			$this->addError($attribute, 'If you wish to change your password you must fill in both the old and new password fields');
     		}
     		if(!$this->validatePassword($this->oldPassword)){
     			$this->addError($attribute, 'The old password does not match what we have on record for you');
     		}
     	}
     }
     
     public function validateNewPassword($attribute, $params)
     {
     	if(strlen(trim($this->oldPassword)) <= 0 && strlen(trim($this->newPassword)) > 0){
     		$this->addError($attribute, 'You must confirm your current password by entering it into the old password field provided');
     	}
     }
     
     public function beforeSave($insert)
     {
     	if($this->newPassword){
     		$this->setPassword($this->newPassword);
     	}
     	return parent::beforeSave($insert);
     }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }
	
	/**
	 * Finds user by password reset token
	 *
	 * @param string $token password reset token
	 * @return static null
	 */
	public static function findByPasswordResetToken($token) {
		if (! static::isPasswordResetTokenValid($token)) {
			return null;
		}
		return static::findOne([ 
			'password_reset_token' => $token,
			'status' => self::STATUS_ACTIVE 
		]);
	}
	
	/**
	 * Finds out if password reset token is valid
	 *
	 * @param string $token password reset token
	 * @return boolean
	 */
	public static function isPasswordResetTokenValid($token) {
		if (empty($token)){
			return false;
		}
		$expire = Yii::$app->params['user.passwordResetTokenExpire'];
		$parts = explode( '_', $token );
		$timestamp = (int)end($parts);
		return $timestamp + $expire >= time();
	}

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = \Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = \Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = \Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    
    public function setSubscriptions($subs)
    {
    	$currentSubs = is_array($this->comics) ? $this->comics : [];
    	if(count($currentSubs) <= 0){
    		// Cannot resolve nuttin
    		return true;
    	}
    	$newSubs = [];
    	foreach($currentSubs as $k => $sub){
    		foreach($subs as $sk => $subKey){
    			if($subKey === (String)$sub['comic_id']){
    				$newSubs[$sk] = $sub;
    			}
    		}
    	}
    	ksort($newSubs);
    	$this->comics = $newSubs;
    	return true;
    }
    
    public function isSubscribed($comic_id)
    {
    	if($comic_id instanceof \MongoId){
    		$comic_id = (String)$comic_id;
    	}
    	
    	if(!is_array($this->comics)){
    		return false;
    	}
    	
    	foreach($this->comics as $comic){
    		if((String)$comic['comic_id'] === $comic_id){
    			return true;
    		}
    	}
    	return false;
    }
    
    public function search()
    {
    	foreach($this->attributes() as $field){
    		$this->$field = null;
    	}
    	if($get = Yii::$app->getRequest()->get('Comic')){
    		$this->attributes = $get;
    	}
    
    	$query = static::find();
    	$query->filterWhere([
    		'_id' => $this->_id ? new \MongoId($this->_id) : null,
    		'username' => $this->username ? new \MongoRegex("/$this->username/") : null,
    		'email' => $this->email ? new \MongoRegex("/$this->email/") : null,
    		'role' => $this->role,
    		'status' => $this->status,
    		'created_at' => $this->created_at,
    		'updated_at' => $this->updated_at
    	]);
    
    	return new ActiveDataProvider([
    		'query' => $query
    	]);
    }
}
