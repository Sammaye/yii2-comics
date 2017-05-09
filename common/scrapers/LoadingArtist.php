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
	public function updateIndex($index, $save = true)
	{
		$this->current_index = $index;
		if($save){
			$this->save(false, ['current_index']);
		}
	}
    
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
            $strip->index >= $this->current_index
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
                $model->save(false, ['next'])
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
                Yii::warning(
                    Yii::t(
                        'app',
                        '{id} returned {message} for {url}',
                        [
                            'id' => (String)$this->_id,
                            'message' => $e->getResponse()->getStatusCode(),
                            'url' => $url
                        ]
                    )
                );
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