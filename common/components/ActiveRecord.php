<?php

namespace common\components;

use Yii;
use yii\mongodb\ActiveRecord as BaseActiveRecord;

class ActiveRecord extends BaseActiveRecord
{
    private $_formName;

    public function formName()
    {
        if($this->_formName){
            return $this->_formName;
        }

        $reflector = new ReflectionClass($this);
        return $reflector->getShortName();
    }

    public function setFormName($formName)
    {
        $this->_formName = $formName;
    }
}