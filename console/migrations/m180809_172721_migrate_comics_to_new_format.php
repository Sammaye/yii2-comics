<?php

use common\models\Comic;
use common\models\ComicStrip;

use MongoDB\BSON\ObjectId;

class m180809_172721_migrate_comics_to_new_format extends \yii\mongodb\Migration
{
    public function up()
    {
        // Garfield
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('545128516803fa67038b456a')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@class='comic']/div[@class='comic-display']/div[@class='comic-arrows']/img",
                    'nav_url_regex' => "\/comic\/(?<index>[0-9]{4}\/[0-9]{2}\/[0-9]{2})",
                    'nav_previous_dom_path' => "//div[contains(@class,'comic-controls-pager')]/div[1]/a",
                    'nav_next_dom_path' => "//div[contains(@class,'comic-controls-pager')]/div[2]/a",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // U.S. Acres
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('545aa65d6803fa64038b4567')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@class='comic']/div[@class='comic-display']/div[@class='comic-arrows']/img",
                    'nav_url_regex' => "\/comic\/(?<index>[0-9]{4}\/[0-9]{2}\/[0-9]{2})",
                    'nav_previous_dom_path' => "//div[contains(@class,'comic-controls-pager')]/div[1]/a",
                    'nav_next_dom_path' => "//div[contains(@class,'comic-controls-pager')]/div[2]/a",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // Dilbert
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('54721ee16803fa60038b4569')],
            [
                '$set' => [
                    'image_dom_path' => "//section[@class='comic-item']/div[@class='img-comic-container']/a[@class='img-comic-link']/img",
                    'nav_url_regex' => "\/strip\/(?<index>[0-9]{4}-[0-9]{2}-[0-9]{2})",
                    'nav_previous_dom_path' => "//div[@class='nav-comic nav-left']/a",
                    'nav_next_dom_path' => "//div[@class='nav-comic nav-right']/a",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // xkcd
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('5473b75f6803fa62038b4569')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@id='comic']/a/img||//div[@id='comic']/img",
                    'nav_url_regex' => "\/(?<index>[0-9]+)\/",
                    'nav_previous_dom_path' => "//ul[@class='comicNav']/li[2]/a",
                    'nav_next_dom_path' => "//ul[@class='comicNav']/li[4]/a",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // Cyanide and Happiness
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('55c72cfe47ac182a5c8b4572')],
            [
                '$set' => [
                    'image_dom_path' => "//img[@id='main-comic']",
                    'nav_url_regex' => "\/comics\/(?<index>[0-9]+)\/",
                    'nav_previous_dom_path' => "//a[@class='nav-previous ']",
                    'nav_next_dom_path' => "//a[@class='nav-next ']",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // CommitStrip
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('5733a92d47ac180b538b4568')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@class='entry-content']/p/img",
                    'nav_url_regex' => "\/en\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(?<index>[A-Za-z-_0-9]+)",
                    'nav_previous_dom_path' => "//div[@id='content']/div[@class='swiper-container']/div[@class='swiper-wrapper']/div[@class='swiper-slide']/nav[@class='nav-single']/span[@class='nav-previous']/a",
                    'nav_next_dom_path' => "//div[@id='content']/div[@class='swiper-container']/div[@class='swiper-wrapper']/div[@class='swiper-slide']/nav[@class='nav-single']/span[@class='nav-next']/a",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // LoadingArtist
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('574779ef9d992554f11a8de8')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@class='comic']/a/img||//div[@class='comic']/img",
                    'nav_url_regex' => "\/comic\/(?<index>[A-Za-z-_0-9]+)\/",
                    'nav_previous_dom_path' => "//a[@class='prev bottom ir ir-prev']",
                    'nav_next_dom_path' => "//a[@class='next bottom ir ir-next']",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // Saturday Morning Breakfast Cereal
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('591427469d992505181ac7d4')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@id='cc-comicbody']/a/img||//img[@id=\"cc-comic\"]",
                    'nav_url_regex' => "\/comic\/(?<index>[A-Za-z-_0-9]+)",
                    'nav_previous_dom_path' => "//nav[@class='cc-nav']/a[@class='cc-prev']",
                    'nav_next_dom_path' => "//nav[@class='cc-nav']/a[@class='cc-next']",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        // Anne & Pythagoras
        $this->update(
            Comic::collectionName(),
            ['_id' => new ObjectId('591427c99d9925051673f486')],
            [
                '$set' => [
                    'image_dom_path' => "//div[@class=\"wsb-canvas-page-container\"]/div[@id=\"wsb-canvas-template-container\"]",
                ],
                '$unset' => [
                    'dom_path' => '',
                ]
            ]
        );

        /**
         * Now that all the comics are updated I need to do the comic strips
         *
         * @var Comic $cachedComic
         * @var ComicStrip $strip
         */
        $cachedComic = null;
        $migratedCount = 0;
        foreach (ComicStrip::find()->each() as $strip) {
            if ($cachedComic->_id !== $strip->comic_id) {
                $cachedComic = $strip->comic;
            }

            if ($cachedComic->_id == new ObjectId('5733a92d47ac180b538b4568')) {
                $url = $strip->url;
                $dom = $cachedComic->getScrapeDom($url);
                $entriesDone = 0;

                $elements = $dom->query(
                    "//div[@id='content']/div[@class='excerpts']/div[@class='excerpt']/section/a"
                );

                $urls = [];
                if ($elements) {
                    foreach ($elements as $element) {
                        $urls[] = $element->getAttribute('href');
                    }
                }

                foreach ($urls as $url) {
                    preg_match('#\/en\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(?<index>[A-Za-z-_0-9]+)#', $url, $matches);

                    if (
                        $cachedComic->getStrip($matches['index'][0], [
                            'date' => $strip->date,
                            'created_at' => $strip->created_at,
                           'updated_at' => $strip->updated_at,
                        ])
                    ) {
                        $entriesDone++;
                    }
                }

                if ($entriesDone > 0) {
                    $strip->delete();
                    $migratedCount++;
                }
            } elseif ($cachedComic->scrapeStrip($strip) && $strip->save()) {
                if ($cachedComic->_id == new ObjectId('591427c99d9925051673f486')) {
                    $strip->url = null;
                    $strip->save();
                }
                $migratedCount++;
            }

            if (($migratedCount % 10) === 0) {
                echo "$migratedCount strips" . "\n";
            }
        }
    }

    public function down()
    {
        echo "m180809_172721_migrate_comics_to_new_format cannot be reverted.\n";

        return false;
    }
}
