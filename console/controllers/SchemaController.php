<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\ComicStrip;
use common\models\Comic;
use common\models\User;
use yii\helpers\Url;
use common\components\Query;

class SchemaController extends Controller
{
    public function actionComics()
    {
        $query = (new Query)
            ->from('comic');
            
        foreach($query->each() as $comic){
            $model = new Comic;
            $model->live = 1;
            $model->active = 1;
            $model->index_format = $comic['date_format'];
            $model->index_step = $comic['day_step'];
            
            if($comic['is_increment']){
                $model->type = Comic::TYPE_ID;
            }else{
                $model->type = Comic::TYPE_DATE;
            }
            
            foreach($model->attributes() as $k => $v){
                if(array_key_exists($v, $comic)){
                    $model->$v = $comic[$v];
                }
            }

            if(!$model->validate()){
                Yii::warning(
                    'Could not validate ' 
                    . $model->title 
                    . ' (' . (String)$model->_id . ') because: ' 
                    . var_export($model->getErrors(), true)
                );
            }
            
            Comic::deleteAll(['_id' => $model->_id]);
            $model->setIsNewRecord(true);
            
            if(!$model->save()){
                Yii::warning(
                    'Could not save ' 
                    . $model->title 
                    . ' (' . (String)$model->_id . ')'
                );
            }
            Yii::getLogger()->flush(true);
        }
    }
    
    public function actionStrips()
    {
        $query = (new Query)
            ->from('comic_strip');
            
        foreach($query->each() as $strip){
            $model = new ComicStrip;
            
            $model->index = $comic['inc_id'] ?: $comic['date'];

            foreach($model->attributes() as $k => $v){
                if(array_key_exists($k, $strip)){
                    $model = $strip[$k];
                }
            }
            
            if(!$model->validate()){
                Yii::warning(
                    'Could not validate ' 
                    . (String)$model->_id . ' because: ' 
                    . var_export($model->getErrors(), true)
                );
            }
            
            Comic::deleteAll(['_id' => $model->_id]);
            $model->setIsNewRecord(true);
            
            if(!$model->save()){
                Yii::warning(
                    'Could not save ' . (String)$model->_id
                );
            }
            Yii::getLogger()->flush(true);
        }
    }
}