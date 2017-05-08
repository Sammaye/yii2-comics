<?php

namespace common\scrapers;

use Yii;
use common\models\Comic;
use common\models\ComicStrip;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Binary;

class CommitStrip extends Comic
{
    public function previous(ComicStrip $strip, array $data = [])
    {
        if($strip->previous){
            return $this->getStrip($strip->previous, $data);
        }else{
            // Try and redownload and see if there is a previous now
            $strip = $this->downloadStrip($strip->index, $data);
            if($strip->previous){
                // If we have a previous now then let's get that
                $strip = $this->downloadStrip($strip->previous, $data);
                return $strip;
            }
        }
        
        // If we have no previous here then let's just return null
        return null;
    }
    
    public function next(ComicStrip $strip, $ignoreCurrent = false, array $data = [])
    {
        if(
            !$ignoreCurrent &&
            $strip->index->toDateTime()->getTimestamp() >= $this->current_index->toDateTime()->getTimestamp()
        ){
            return null;
        }
        
        if($strip->next){
            return $this->getStrip($strip->next, $data);
        }else{
            // Try and redownload and see if there is a next now
            $strip = $this->downloadStrip($strip->index, $data);
            if($strip->next){
                // If we have a next now then let's get that
                $strip = $this->downloadStrip($strip->next, $data);
                return $strip;
            }elseif($ignoreCurrent){
                // $ignoreCurrent will normally be from admin 
                // functions such as the scraper
                Yii::warning(
                    Yii::t(
                        'app',
                        '{title} ({id}) could not find next from {url}',
                        [
                            'title' => $this->title,
                            'id' => (String)$this->_id,
                            'url' => $this->scrapeUrl($strip->index)
                        ]
                    )
                );
            }
        }
        
        // If we have no next here then let's just return null
        return null;
    }
    
    public function downloadStrip($index, array $data = [])
    {
		$model = ComicStrip::find()->where(['comic_id' => $this->_id, 'index' => $index])->one();
		
        if($model){
            // If the document existed as we updated it then just return a findOne of it
            if(
                $this->populateStrip($model) && 
                $model->save(['next'])
            ){
		        return $model;
		    }
		}elseif(!$model){
    		$model = new ComicStrip();
    		$model->comic_id = $this->_id;
    		$model->index = $index;

    		foreach($data as $k => $v){
    			$model->$k = $v;
    		}

    		if($this->populateStrip($model) && $model->save()){
    			return $model;
    		}
		}
		return null;
    }
    
    public function populateStrip(&$model, $url = null)
    {
        $dayDoc = $this->xPath($this->scrapeUrl($model->index));

		$elements = $dayDoc->query(
            "//div[@id='content']/div[@class='excerpts']/div[@class='excerpt']/section/a"
        );
        
        $urls = [];
		if($elements){
			foreach($elements as $element){
				$urls[] = $element->getAttribute('href');
			}
		}
		
		$comicDocs = [];
		$imgs = [];
		foreach($urls as  $url){
		    $comicDocs[] = $dom = $this->xPath($url);

    		$elements = $dom->query($this->dom_path);
    		if($elements){
    			foreach($elements as $element){
    				$imgs[] = $element->getAttribute('src');
    			}
    		}
		}
		
		// Now that I have the images and the doms let's find the next and previous links
        // The first in the row is the last and the last is the first
        if(count($comicDocs) > 1){
            $next = $this->nextLink($comicDocs[0]);
            $previous = $this->previousLink($comicDocs[0]);
        }else{
            $next = $this->nextLink($comicDocs[0]);
            $previous = $this->previousLink($comicDocs[count($comicDocs) - 1]);
        }
        
        $model->url = $this->scrapeUrl($model->index);
        $model->next = $next;
        $model->previous = $previous;

        foreach($imgs as $k => $url){
            $imgs[$k] = new Binary(file_get_contents($url), Binary::TYPE_GENERIC);
        }
        $model->img = $imgs;
        
        return true;
    }
    
    public function nextLink($stripDom)
    {
        return $this->navLink('next', $stripDom);
    }
    
    public function previousLink($stripDom)
    {
        return $this->navLink('previous', $stripDom);
    }
    
    public function navLink($type, $stripDom)
    {
        $elements = $stripDom->query(
            "//div[@id='content']/div[@class='swiper-wrapper']/div[@class='swiper-slide']/nav[@class='nav-single']/span[@class='nav-$type']/a"
        );
        
		if($elements){
			foreach($elements as $element){
                // Only ever need the first one
                $matches = [];
                $baseUrl = str_replace('{$value}', '', $this->scrape_url);
                preg_match_all(
                    '#^' . preg_quote($baseUrl) . '([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/.*$#', 
                    $element->getAttribute('href'), 
                    $matches
                );
                if(count($matches) <= 0){
                    return null;
                }

                $date = new UTCDateTime(
                    mktime(0, 0, 0, $matches[2][0], $matches[3][0], $matches[1][0])*1000
                );
                return $date;
			}
		}
    }
}