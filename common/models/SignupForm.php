<?php
namespace common\models;

use common\models\User;
use yii\base\Model;
use Yii;

class SignupForm extends Model
{
    const SCENARIO_ADMIN = 'admin';

    public $username;
    public $email;
    public $password;
    public $role;

    public function rules()
    {
        $rolesResult = Yii::$app->authManager->getRoles();
        $roles = [];
        foreach($rolesResult as $v) {
            $roles[] = $v->name;
        }

        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required'],
            [
                'username',
                'unique',
                'targetClass' => User::class,
                'message' => Yii::t('app', 'This username has already been taken')
            ],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            [
                'email',
                'unique',
                'targetClass' => User::class,
                'message' => Yii::t('app', 'This email address has already been taken')
            ],
            
            ['password', 'required'],
            ['password', 'string', 'min' => 6],

            ['role', 'required', 'on' => self::SCENARIO_ADMIN],
            ['role', 'in', 'range' => $roles, 'on' => self::SCENARIO_ADMIN]
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            $user = new User();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->role = $this->role;
            $user->setPassword($this->password);

            $user->generateAuthKey();
            if($user->save()){
                $this->addError(
                    'username',
                    Yii::t(
                        'app',
                        'Unknown error'
                    )
                );
            }

            $auth = \Yii::$app->authManager;
            $userRole = $auth->getRole('user');
            $auth->assign($userRole, $user->getId());

            return $user;
        }

        return null;
    }
}
