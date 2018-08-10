<?php

namespace common\scrapers;

use Yii;
use common\models\Comic;
use common\models\ComicStrip;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Binary;

class AnneAndPythagoras extends Comic
{
    public function scrapeStrip(&$model, $url = null)
    {
        $imageUrl = null;

        $baseUrl = rtrim($this->base_url ?: $this->scrape_url, '/');
        $baseUrlScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
        $baseUrlHost = parse_url($baseUrl, PHP_URL_HOST);
        if (!$baseUrlHost) {
            // As a last resort we will check the homepage link
            $baseUrl = rtrim($this->homepage, '/');
            $baseUrlScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
            $baseUrlHost = parse_url($baseUrl, PHP_URL_HOST);
        }

        $url = $url ?: $this->scrapeUrl($model->index);
        $dom = $this->getScrapeDom($url);

        if (!$dom) {
            return $this->addScrapeError(
                '{id} could not instantiate DOMDocument Object for {url}',
                [
                    'id' => (String)$this->_id,
                    'url' => $url,
                ]
            );
        }

        $images = [];

        $scripts = $dom->query("//script");
        foreach ($scripts as $s) {
            # see if there are any matches for var datePickerDate in the script node's contents
            if (preg_match('#CarouselAssets: (.*?), CarouselCaption#', $s->nodeValue, $matches)) {
                # the date itself (captured in brackets) is in $matches[1]
                $images = json_decode($matches[1], true);
            }
        }

        if (!$images) {
            //raise error
            return $this->addScrapeError(
                '{id} could not find img array from JS for {url}',
                [
                    'id' => (String)$this->_id,
                    'url' => $url
                ]
            );
        }

        foreach ($images as $k => $v) {
            if ($k === (int)$model->index) {
                $imageUrl = $v['src'];
            }
        }

        if (!$imageUrl) {
            return $this->addScrapeError(
                '{id} ({index}) could not find img with src for {url}',
                [
                    'id' => (String)$this->_id,
                    'index' => $model->index,
                    'url' => $url
                ]
            );
        }

        $imageUrlParts = parse_url($imageUrl);
        if ($imageUrlParts) {
            $imageUrlHost = null;
            if (!isset($imageUrlParts['scheme']) && !isset($imageUrlParts['host'])) {
                $imageUrlHost = $baseUrlScheme . '://' . $baseUrlHost . '/';
            } elseif (!isset($imageUrlParts['scheme'])) {
                $imageUrlHost = $baseUrlScheme . '://';
            }

            if ($imageUrlHost) {
                $imageUrl = $imageUrlHost . ltrim($imageUrl, '/');
            }
        }
        $model->image_url = $imageUrl;
        $model->next = isset($images[(int)$model->index + 1]) ? (int)$model->index + 1 : null;
        $model->previous = isset($images[(int)$model->index - 1]) ? (int)$model->index - 1 : null;

        try {
            // Sometimes people like to put crappy special characters into file names
            if (pathinfo($model->image_url, PATHINFO_EXTENSION)) {
                $filename = pathinfo($model->image_url, PATHINFO_FILENAME);
                $encodedFilename = rawurlencode($filename);
                $imageUrl = str_replace($filename, $encodedFilename, $model->image_url);
            }

            if (($model->image_url) && ($binary = file_get_contents($imageUrl))) {
                $model->image_md5 = md5($binary);
                $model->img = new Binary($binary, Binary::TYPE_GENERIC);
                return true;
            }

            throw new \Exception;
        } catch (\Exception $e) {
            // the file probably had a problem beyond our control
            // As such define this as a skip strip since I cannot store it
            $model->skip = 1;
            return true;
        }
        return false;
    }
}
