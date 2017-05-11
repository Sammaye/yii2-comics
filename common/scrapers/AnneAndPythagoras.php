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
    public function populateStrip(&$model, $url = null)
    {
        $imgUrl = null;

        if (!$model->url) {
            $doc = $this->xPath($url ?: $this->scrapeUrl($model->index));

            if ($doc) {

                $images = [];

                $scripts = $doc->query("//script");
                foreach ($scripts as $s) {
                    # see if there are any matches for var datePickerDate in the script node's contents
                    if (preg_match('#CarouselAssets: (.*?), CarouselCaption#', $s->nodeValue, $matches)) {
                        # the date itself (captured in brackets) is in $matches[1]
                        $images = json_decode($matches[1], true);
                    }
                }

                if (!$images) {
                    //raise error
                    $this->addScrapeError(
                        Yii::t(
                            'app',
                            '{id} could not find img array from JS for {url}',
                            [
                                'id' => (String)$this->_id,
                                'url' => $this->scrapeUrl($model->index)
                            ]
                        )
                    );
                    return false;
                }

                foreach ($images as $k => $v) {
                    if ($k === (int)$model->index) {
                        $imgUrl = $v['src'];
                    }
                }
            }

            if (!$imgUrl) {
                $this->addScrapeError(
                    Yii::t(
                        'app',
                        '{id} ({index}) could not find img with src for {url}',
                        [
                            'id' => (String)$this->_id,
                            'index' => $model->index,
                            'url' => $this->scrapeUrl($model->index)
                        ]
                    )
                );
                return false;
            }

            $parts = parse_url($imgUrl);

            if ($parts) {
                if (
                    !isset($parts['scheme']) &&
                    isset($parts['host'])
                ) {
                    $imgUrl = 'http://' . trim($imgUrl, '//');
                } elseif (
                    (
                        !isset($parts['scheme']) ||
                        !isset($parts['host'])
                    ) &&
                    isset($parts['path'])
                ) {
                    // The URL is relative as such add the homepage onto the beginning
                    $imgUrl = trim($this->homepage, '/') . '/' . trim($parts['path'], '/');
                }
            }
            $model->url = $imgUrl;
        }

        try {
            if (($model->url) && ($binary = file_get_contents($model->url))) {
                $model->img = new Binary($binary, Binary::TYPE_GENERIC);
                return true;
            }
        } catch (\Exception $e) {
            // the file probably had a problem beyond our control
            // As such define this as a skip strip since I cannot store it
            $model->skip = 1;
            return true;
        }
        return false;
    }
}