<?php

namespace common\scrapers;

use Yii;
use common\models\Comic;
use common\models\ComicStrip;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Binary;

class LoadingArtist extends Comic
{
    public function previous(ComicStrip $cStrip, array $data = [])
    {
        if($cStrip->previous){
            return $this->getStrip($cStrip->previous, $data);
        }else{
            // Try and redownload and see if there is a previous now
            $cStrip = $this->downloadStrip($cStrip->index, $data);
            if($cStrip->previous){
                // If we have a previous now then let's get that
                $strip = $this->downloadStrip($cStrip->previous, $data);
                return $strip;
            }
        }
        
        // If we have no previous here then let's just return null
        return null;
    }
    
    public function next(ComicStrip $cStrip, $ignoreCurrent = false, array $data = [])
    {
        if(
            !$ignoreCurrent && 
            $cStrip->index >= $this->current_index
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
            }elseif($ignoreCurrent){
                // $ignoreCurrent will normally be from admin 
                // functions such as the scraper
                Yii::warning(
                    $this->title . '(' 
                    . (String)$this->_id . ') could not find next from ' 
                    . $this->scrapeUrl($cStrip->index)
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
		    if($next){
    		    $model->next = $next;
    		    if(
    		        $this->populateStrip($model) && 
    		        $model->save(['next'])
                ){
    		        return $model;
    		    }
		    }else{
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
        $url = null;
        $dayDoc = $this->xPath($this->scrapeUrl($model->index));

		if(strpos($this->dom_path, '||') !== false){
			$paths = preg_split('#\|\|#', $this->dom_path);
		}else{
			$paths = [$this->dom_path];
		}
		
		foreach($paths as $domPath){
			$elements = $dayDoc->query($domPath);
			if($elements){
				foreach($elements as $element){
					$url = $element->getAttribute('src');
				}
			}
			if($url){
				break;
			}
		}

        if(!$url){
            $this->addScrapeError(
                'Could get comic but not image for ' 
                . $this->scrapeUrl($model->index)
            );
            return false;
        }

        $next = $this->nextLink($dayDoc);
        $previous = $this->previousLink($dayDoc);
        
        $model->url = $url;
        $model->next = $next;
        $model->previous = $previous;

        $model->img = new Binary(file_get_contents($url), Binary::TYPE_GENERIC);
        return true;
    }
    
    public function nextLink($stripDom)
    {
        return $this->navLink('next', $stripDom);
    }
    
    public function previousLink($stripDom)
    {
        return $this->navLink('prev', $stripDom);
    }
    
    public function navLink($type, $stripDom)
    {
        if($type === 'prev'){
            $elements = $stripDom->query(
                "//a[@class='prev bottom ir ir-$type']"
            );
        }else{
            $elements = $stripDom->query(
                "//a[@class='next bottom ir ir-$type']"
            );
        }
		if($elements){
			foreach($elements as $element){
                // Only ever need the first one
                
                $matches = [];
                $baseUrl = str_replace('{$value}', '', $this->scrape_url);
                preg_match_all(
                    '#^' . preg_quote($baseUrl) . '(.*)\/$#', 
                    $element->getAttribute('href'), 
                    $matches
                );
                if(count($matches) <= 0){
                    return null;
                }

                $id = $matches[1][0];
                return $id;
			}
		}
    }
    
    public function xPath($url, $ignoreErrors = false)
    {
        try{
			$res = (new Client)->request(
				'GET', 
				$url, 
				[
					'headers' => [
						'User-Agent' => 'Googlebot/2.1 (http://www.googlebot.com/bot.html)'
					]
				]
			);
		}catch(ClientException $e){
			// Log the exception
			if(!$ignoreErrors){
                $message = (String)$this->_id . ' returned ' . 
                    $e->getResponse()->getStatusCode()  
                    . ' for ' . $url;
                Yii::warning($message);
			}
			return null;
		}
		
		$doc = new \DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHtml($res->getBody());
		libxml_clear_errors();
		$el = new \DOMXPath($doc);
		return $el;
    }
}