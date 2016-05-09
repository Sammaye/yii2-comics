<?php

namespace common\scrapers;

use common\models\Comic;
use common\models\ComicStrip;

class CommitStrip extends Comic
{
    public function previous(ComicStrip $cStrip, array $data = [])
    {
        $index = $this->index($cStrip->index);

        if($cStrip->previous){
            return $this->getStrip($cStrip->previous, $data);
        }else{
            // Try and redownload and see if there is a next now
            $cStrip = $this->downloadStrip($cStrip->index, $data);
            if($cStrip->previous){
                // If we have a next now then let's get that
                $strip = $this->downloadStrip($cStrip->previous, $data);
                return $strip;
            }
        }
        
        // If we have no next here then let's just return null
        return null;
    }
    
    public function next(ComicStrip $cStrip, $ignoreCurrent = false, array $data = [])
    {
        $index = $this->index($cStrip->index);

        if(
            !$ignoreCurrent && 
            $cStrip->index->sec >= $this->current_index->sec
        ){
            return null;
        }
        
        if($cStrip->next){
            return $this->getStrip($cStrip->next, $data);
        }else{
            // Try and redownload and see if there is a next now
            $cStrip = $this->downloadStrip($cStrip->index, $data);
            if($cStrip->next){
                // If we have a next now then let's get that
                $strip = $this->downloadStrip($cStrip->next, $data);
                return $strip;
            }
        }
        
        // If we have no next here then let's just return null
        return null;
    }
    
    public function downloadStrip($index)
    {
        
    }
}