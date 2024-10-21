<?php

namespace ExternalImporter\application\components\scrap;

defined('\ABSPATH') || exit;

/**
 * ScrapeowlScrap class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class ScrapeowlScrap extends Scrap
{
    const SLUG = 'scrapeowl';

    public function doAction($url, $args)
    {
        if (!$this->needSendThrough($url))
            return $url;

        $url = 'https://api.scrapeowl.com/v1/scrape?api_key=' . urlencode($this->getToken()) . '&json_response=false&url=' . urlencode($url);
        $url = \apply_filters('ei_parse_url_' . $this->getSlug(), $url);

        return $url;
    }
}
