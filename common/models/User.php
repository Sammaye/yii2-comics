<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use common\components\ActiveRecord;
use yii\web\IdentityInterface;
use yii\data\ActiveDataProvider;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_BANNED = 5;
    const STATUS_ACTIVE = 10;

    const SCENARIO_SEARCH = 'search';
    const SCENARIO_ADMIN = 'admin';

    public $role;

    public $oldPassword;
    public $newPassword;
    public $confirmPassword;

    public $adminSetPassword;

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function ($e) {
                    return new UTCDateTime(time() * 1000);
                }
            ],
        ];
    }

    public function rules()
    {
        return [

            ['status', 'required'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [
                self::STATUS_DELETED,
                self::STATUS_BANNED,
                self::STATUS_ACTIVE
            ]],

            ['username', 'required'],
            ['username', 'string', 'min' => 3, 'max' => 20],
            ['username', 'unique'],

            ['email', 'required'],
            ['email', 'email'],
            ['email', 'unique'],

            [
                'email_frequency',
                'in',
                'range' => array_keys($this->emailFrequencies())
            ],

            [
                [
                    'oldPassword',
                    'newPassword',
                    'confirmPassword'
                ],
                'filter',
                'filter' => 'trim'
            ],
            [
                [
                    'oldPassword',
                    'newPassword',
                    'confirmPassword'
                ],
                'string',
                'min' => 6
            ],
            ['oldPassword', function ($attribute, $params, $validator) {
                if (
                    !\Yii::$app->security->validatePassword(
                        $this->$attribute,
                        $this->password_hash
                    )
                ) {
                    $this->addError(
                        $attribute,
                        Yii::t(
                            'app',
                            "The password entered does not match the old password"
                        )
                    );
                    return false;
                }
                return true;
            }],
            [
                [
                    'newPassword',
                    'confirmPassword'
                ],
                'required', 'when' => function ($model) {
                    return strlen(trim($this->oldPassword)) > 0;
                }
            ],
            ['confirmPassword', function ($attribute, $params, $validator) {
                if ($this->newPassword !== $this->$attribute) {
                    $this->addError(
                        $attribute,
                        Yii::t('app', "You must confirm your new password")
                    );
                    return false;
                }
                return false;
            }],

            ['adminSetPassword', 'filter', 'filter' => 'trim'],
            ['adminSetPassword', 'string', 'min' => 6],
            ['adminSetPassword', 'required', 'on' => [self::SCENARIO_ADMIN]],

            [
                [
                    '_id',
                    'username',
                    'email',
                    'status',
                ],
                'safe',
                'on' => self::SCENARIO_SEARCH
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
            'status',
            'comics',
            'email_frequency',
            'facebook_id',
            'google_id',
            'facebook_token',
            'last_feed_sent',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    public function emailFrequencies()
    {
        return [
            'daily' => Yii::t('app', 'Daily'),
            'weekly' => Yii::t('app','Weekly'),
            'monthly' => Yii::t('app', 'Monthly'),
            'paused' => Yii::t('app', 'Paused')
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->newPassword) {
            $this->setPassword($this->newPassword);
        }
        if (
            $this->status === self::STATUS_DELETED &&
            !$this->deleted_at instanceof UTCDateTime
        ) {
            $this->deleted_at = new UTCDateTime(time() * 1000);
        }
        return parent::beforeSave($insert);
    }

    public function queueForDelete()
    {
        $this->status = self::STATUS_DELETED;
        $this->deleted_at = new UTCDateTime(time()*1000);
        if($this->save(false)){
            return true;
        }
        return false;
    }

    public static function findIdentity($id)
    {
        if (!$id instanceof ObjectID) {
            $id = new ObjectID($id);
        }
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param  string $username
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
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
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
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int)end($parts);
        return $timestamp + $expire >= time();
    }

    public function getId()
    {
        return (String)$this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string $password password to validate
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

    public function setRole($role = null)
    {
        if (
            $role &&
            ($roleItem = Yii::$app->authManager->getRole($role))
        ) {
            Yii::$app->authManager->revokeAll($this->id);
            Yii::$app->authManager->assign($roleItem, $this->id);
        }
    }

    public function addComic($id)
    {
        $comics = is_array($this->comics) ? $this->comics : [];
        foreach($comics as $comic){
            if((String)$comic['comic_id'] === (String)$id){
                return true;
            }
        }

        $comics[] = [
            'date' => new UTCDateTime(time()*1000),
            '_id' => $id instanceof ObjectID ? $id : new ObjectID($id)
        ];
        $this->comics = $comics;

        return $this->update(false, ['comics']);
    }

    public function removeComic($id)
    {
        $comics = is_array($this->comics) ? $this->comics : [];
        foreach($comics as $k => $comic){
            if((String)$comic['comic_id'] === (String)$id){
                unset($comics[$k]);
            }
        }
        $this->comics = $comics;
        return $this->update(false, ['comics']);
    }

    public function modifyComics($subs)
    {
        $currentSubs = is_array($this->comics) ? $this->comics : [];
        if (count($currentSubs) <= 0) {
            // Cannot resolve nuttin
            return true;
        }
        $newSubs = [];
        foreach ($currentSubs as $k => $sub) {
            foreach ($subs as $sk => $subKey) {
                if ($subKey === (String)$sub['comic_id']) {
                    $newSubs[$sk] = $sub;
                }
            }
        }
        ksort($newSubs);
        $this->comics = $newSubs;
        return true;
    }

    public function hasComic($id)
    {
        if ($id instanceof ObjectID) {
            $id = (String)$id;
        }

        if (!is_array($this->comics)) {
            return false;
        }

        foreach ($this->comics as $comic) {
            if ((String)$comic['comic_id'] === $id) {
                return true;
            }
        }
        return false;
    }

    public function search()
    {
        foreach ($this->attributes() as $field) {
            $this->$field = null;
        }
        if ($get = Yii::$app->getRequest()->get($this->formName())) {
            $this->attributes = $get;
        }

        $query = static::find();
        $query->filterWhere([
            '_id' => $this->_id ? new ObjectID($this->_id) : null,
            'username' => $this->username ? new Regex($this->username) : null,
            'email' => $this->email ? new Regex($this->email) : null,
            'status' => $this->status,
        ]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
