<?php

namespace common\components;

use Yii;
use yii\debug\Module as BaseDebugModule;

class DebugModule extends BaseDebugModule
{
    private $_basePath;

    protected function checkAccess()
    {
        $user = Yii::$app->getUser();

        if (
            $user->identity &&
            $user->can('admin')
        ) {
            return true;
        }
        return parent::checkAccess();
    }

    /**
     * Returns the root directory of the module.
     * It defaults to the directory containing the module class file.
     * @return string the root directory of the module.
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass(new BaseDebugModule('debug'));
            $this->_basePath = dirname($class->getFileName());
        }

        return $this->_basePath;
    }
}