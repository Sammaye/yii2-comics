<?php

namespace common\scrapers;

use Yii;
use common\models\Comic;
use common\models\ComicStrip;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class CommitStrip extends Comic
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
            }else{
                Yii::warning(
                    $model->title . '(' 
                    . (String)$this->_id . ') could not find next from ' 
                    . $this->scrapeUrl($cStrip->index)
                );
            }
        }
        
        // If we have no next here then let's just return null
        return null;
    }
    
    public function downloadStrip($index, $data)
    {
        $dayDoc = $this->xPath($this->scrapeUrl($index));

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
		
		$existModel = ComicStrip::find()->where(['comic_id' => $this->_id, 'index' => $index])->one();
		
		if($existModel){
		    // If the document existed as we updated it then just return a findOne of it
		    if($next){
    		    $existModel->next = $next;
    		    if($existModel->save(['next'])){
    		        return $existModel;
    		    }
		    }else{
		        return $existModel;
		    }
		}elseif(!$existModel){
    		$model = new ComicStrip();
    		$model->comic_id = $this->_id;
    		$model->index = $index;
    		$model->url = $this->scrapeUrl($index);
    		$model->next = $next;
    		$model->previous = $previous;
    		foreach($data as $k => $v){
    			$model->$k = $v;
    		}
    		foreach($imgs as $k => $url){
    		    $imgs[$k] = new \MongoBinData(file_get_contents($url));
    		}
    		$model->img = $imgs;
    		if($model->save()){
    			return $model;
    		}
		}
		return null;
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

                $date = new \MongoDate(
                    mktime(0, 0, 0, $matches[2][0], $matches[3][0], $matches[1][0])
                );
                return $date;
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