<?php

namespace ExternalImporter\application\libs\pextractor\parser\parsers;

defined('\ABSPATH') || exit;

use ExternalImporter\application\libs\pextractor\client\XPath;
use ExternalImporter\application\libs\pextractor\client\Dom;
use ExternalImporter\application\libs\pextractor\parser\Product;

use function ExternalImporter\prn;
use function ExternalImporter\prnx;

/**
 * AmazoncomParser class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class AmazonAdvanced extends AdvancedParser
{

    public function getHttpOptions()
    {
        $user_agent = array('DuckDuckBot', 'facebot', 'ia_archiver');

        /*
        if (in_array($this->host, array('amazon.com', 'amazon.pl', 'amazon.de', 'amazon.in')))
            return array('user-agent' => $user_agent[array_rand($user_agent)]);
        */

        return parent::getHttpOptions();
    }

    protected function preParseProduct()
    {
        // html fix
        $this->html = preg_replace('/<table id="HLCXComparisonTable".+?<\/table>/uims', '', $this->html);

        $html = preg_replace('/<head>.+?<\/head>/uims', '', $this->html);
        if ($html)
            $this->html = $html;

        $this->html = preg_replace('/<script.*?>.*?<\/script>/uims', '', $this->html);
        $this->html = preg_replace('/<style.*?>.*?<\/style>/uims', '', $this->html);

        $this->xpath = new XPath(Dom::createFromString($this->html));
        return true;
    }

    public function parseLinks()
    {
        $path = array(
            ".//div[@class='a-section a-spacing-none']/h2/a/@href",
            ".//h2/a[@class='a-link-normal a-text-normal']/@href",
            ".//h2/a[@class='a-link-normal s-link-style a-text-normal']/@href",
            ".//h2/a[contains(@class, 'a-link-normal')]/@href",
            ".//*[@data-component-type='s-product-image']//a[@class='a-link-normal']/@href",
            ".//*[@class='aok-inline-block zg-item']/a[@class='a-link-normal']/@href",
            ".//h3[@class='newaps']/a/@href",
            ".//div[@id='resultsCol']//a[contains(@class,'s-access-detail-page')]/@href",
            ".//div[@class='zg_title']/a/@href",
            ".//div[@id='rightResultsATF']//a[contains(@class,'s-access-detail-page')]/@href",
            ".//div[@id='atfResults']/ul//li//div[contains(@class,'a-column')]/a/@href",
            ".//div[@id='mainResults']//li//a[@title]/@href",
            ".//*[@id='zg_centerListWrapper']//a[@class='a-link-normal' and not(@title)]/@href",
            ".//h5/a[@class='a-link-normal a-text-normal']/@href",
            ".//span[@class='a-link-normal s-no-outline']/@href",
            ".//span[@class='a-list-item']//a[contains(@href, '/dp/')]/@href",
            ".//div[contains(@class, '-gridRow')]//a[@class='a-link-normal' and not(contains(@href, 'product-reviews'))]/@href",
            ".//a[contains(@href, '/dp/')]/@href",

        );

        if (!$urls = $this->xpathArray($path))
            return array();

        // picassoRedirect fix
        foreach ($urls as $i => $url)
        {
            if (!strstr($url, '/gp/slredirect/picassoRedirect.html/'))
                continue;
            $parts = parse_url($url);
            if (empty($parts['query']))
                continue;
            parse_str($parts['query'], $output);
            if (empty($output['url']))
                continue;

            $urls[$i] = $output['url'];
        }

        foreach ($urls as $i => $url)
        {
            if (strstr($url, 'sspa/click'))
                unset($urls[$i]);

            if ($asin = self::parseAsinFromUrl($url))
                $urls[$i] = '/dp/' . $asin . '/';
        }

        return $urls;
    }

    public function parsePagination()
    {
        $path = array(
            ".//ul[@class='a-pagination']//li[@class='a-normal']/a/@href",
            ".//div[@id='pagn']//span[@class='pagnLink']/a/@href",
            //".//span[@clas='s-pagination-strip']//a/@href",
            ".//span[@class='s-pagination-strip']//a[@class='s-pagination-item s-pagination-button']/@href",
        );

        $urls = $this->xpathArray($path);

        foreach ($urls as $i => $url)
        {
            if (preg_match('/&page=(\d+)/', $url, $matches))
            {
                $url = \add_query_arg('__mk_pl_PL', false, $url);
                $url = \add_query_arg('ref', false, $url);
                $url = \add_query_arg('page', false, $url);
                $urls[$i] = \add_query_arg('page', $matches[1], $url);
            }
        }

        return $urls;
    }

    public function parseTitle()
    {
        $paths = array(
            ".//h1[@id='title']/span",
            ".//*[@id='fine-ART-ProductLabelArtistNameLink']",
            ".//h1",
        );

        return $this->xpathScalar($paths);
    }

    public function parseDescription()
    {
        $path = array(
            ".//div[@id='featurebullets_feature_div']//span[@class='a-list-item']",
            ".//div[@id='featurebullets_feature_div']//li",
            ".//h3[@class='product-facts-title']//..//li",
        );

        if ($results = $this->xpathArray($path))
        {
            $results = array_map('\sanitize_text_field', $results);
            $key = array_search('Make sure this fits by entering your model number.', $results);
            if ($key !== false)
                unset($results[$key]);
            return '<ul><li>' . implode("</li><li>\n", $results) . '</li></ul>';
        }

        $result = $this->xpathScalar(".//script[contains(.,'iframeContent')]");
        if ($result && preg_match('/iframeContent\s=\s"(.+?)"/msi', $result, $match))
        {
            $res = urldecode($match[1]);
            if (preg_match('/class="productDescriptionWrapper">(.+?)</msi', $res, $match))
                return trim($match[1]);
        }

        $paths = array(
            ".//*[@id='bookDescription_feature_div']/noscript/div",
            ".//*[@id='productDescription']//*[@class='productDescriptionWrapper']",
            ".//*[@id='productDescription']/p/*[@class='btext']",
            ".//*[@id='bookDescription_feature_div']/noscript",
            ".//*[@class='dv-simple-synopsis dv-extender']",
            ".//*[@id='bookDescription_feature_div']//noscript/div",
            ".//div[@id='bookDescription_feature_div']",
            ".//*[@id='productDescription']/p",

        );

        if ($description = $this->xpathScalar($paths, true))
            return $description;

        if (preg_match('/bookDescEncodedData = "(.+?)",/', $this->html, $matches))
            return html_entity_decode(urldecode($matches[1]));

        if (preg_match('/(<div id="bookDescription_feature_div".+?)<a href="/ims', $this->html, $matches))
            return $matches[1];

        return '';
    }

    public function parsePrice()
    {
        if (!$this->parseInStock())
            return 0;

        $paths = array(
            ".//span[@id='subscriptionPrice']//span[@data-a-color='price']//span[@class='a-offscreen']",
            ".//table[@class='a-lineitem a-align-top']//span[@data-a-color='price']//span[@class='a-offscreen']",
            ".//*[contains(@class, 'priceToPay')]//*[@class='a-offscreen']",
            ".//*[@class='a-price aok-align-center reinventPricePriceToPayMargin priceToPay']",
            ".//div[@class='a-section a-spacing-none aok-align-center']//span[@class='a-offscreen']",
            ".//span[contains(@class, 'a-price') and contains(@class, 'priceToPay')]//span[@class='a-offscreen']",
            ".//h5//span[@id='price']",
            ".//span[@class='a-price a-text-price header-price a-size-base a-text-normal']//span[@class='a-offscreen']",
            ".//span[@class='a-price a-text-price a-size-medium apexPriceToPay']//span[@class='a-offscreen']",
            ".//span[contains(@class, 'priceToPay')]",
            ".//div[@class='a-section a-spacing-small a-spacing-top-small']//a/span[@class='a-size-base a-color-price']",
            ".//div[@class='a-section a-spacing-none aok-align-center']//span[@class='a-offscreen']",
            ".//*[@id='priceblock_dealprice']",
            ".//span[@id='priceblock_ourprice']",
            ".//span[@id='priceblock_saleprice']",
            ".//input[@name='displayedPrice']/@value",
            ".//*[@id='unqualifiedBuyBox']//*[@class='a-color-price']",
            ".//*[@class='dv-button-text']",
            ".//*[@id='cerberus-data-metrics']/@data-asin-price",
            ".//div[@id='olp-upd-new-freeshipping']//span[@class='a-color-price']",
            ".//span[@id='rentPrice']",
            ".//span[@id='newBuyBoxPrice']",
            ".//div[@id='olp-new']//span[@class='a-size-base a-color-price']",
            ".//span[@id='unqualified-buybox-olp']//span[@class='a-color-price']",
            ".//span[@id='price_inside_buybox']",
            ".//span[@class='slot-price']//span[@class='a-size-base a-color-price a-color-price']",
            ".//span[@class='a-button-inner']//span[contains(@class, 'a-color-price')]",
            ".//div[@id='booksHeaderSection']//span[@id='price']",
            ".//div[@class='a-box-inner a-padding-base']//span[@class='a-color-price aok-nowrap']",
            ".//span[@id='kindle-price']",
            ".//span[contains(@class, 'a-price')]//span/@aria-hidden",

        );

        $price = $this->xpathScalar($paths);

        if (!$price && $price = $this->xpathScalar(".//span[@id='priceblock_ourprice']//*[@class='buyingPrice' or @class='price-large']"))
        {
            if ($cent = $this->xpathScalar(".//span[@id='priceblock_ourprice']//*[@class='verticalAlign a-size-large priceToPayPadding' or @class='a-size-small price-info-superscript']"))
                $price = $price . '.' . $cent;
        }

        if (strstr($price, ' - '))
        {
            $tprice = explode('-', $price);
            $price = $tprice[0];
        }

        return $price;
    }

    public function parseOldPrice()
    {
        $paths = array(
            ".//*[not(@class='pricePerUnit')]//span[@class='a-price a-text-price a-size-base']//span[@class='a-offscreen']",
            ".//*[@id='price']//span[@class='a-text-strike']",
            ".//div[@id='price']//td[contains(@class,'a-text-strike')]",
            "(.//*[@id='price']//span[@class='a-text-strike'])[2]",
            ".//*[@id='buyBoxInner']//*[contains(@class, 'a-text-strike')]",
            ".//*[@id='price']//span[contains(@class, 'priceBlockStrikePriceString')]",
            ".//span[@id='rentListPrice']",
            ".//span[@id='listPrice']",
            ".//span[@class='a-size-small a-color-secondary aok-align-center basisPrice']//span[@class='a-price a-text-price']/span[@class='a-offscreen']",

        );

        return $this->xpathScalar($paths);
    }

    public function parseImage()
    {
        $paths = array(
            ".//img[@id='miniATF_image']/@src",
            ".//img[@id='landingImage']/@data-old-hires",
            ".//img[@id='landingImage']/@data-a-dynamic-image",
            ".//img[@id='landingImage']/@src",
            ".//img[@id='ebooksImgBlkFront']/@src",
            ".//*[@id='fine-art-landingImage']/@src",
            ".//*[@class='dv-dp-packshot js-hide-on-play-left']//img/@src",
            ".//*[@id='main-image']/@src",
            ".//div[@id='mainImageContainer']/img/@src",
            ".//img[@id='imgBlkFront' and not(contains(@src, 'data:image'))]/@src",
            ".//div[@id='imgTagWrapperId']//img/@src",
        );

        $img = $this->xpathScalar($paths);

        if (preg_match('/^data:image/', $img))
            $img = '';

        if (preg_match('/"(https:\/\/.+?)"/', $img, $matches))
            $img = $matches[1];

        if (!$img)
        {
            $dynamic = $this->xpathScalar(".//img[@id='landingImage' or @id='imgBlkFront']/@data-a-dynamic-image");
            if (preg_match('/"(https:\/\/.+?)"/', $dynamic, $matches))
                $img = $matches[1];
        }
        if (!$img)
        {
            $img = $this->xpathScalar(".//img[@id='imgBlkFront']/@src");
            if (preg_match('/^data:image/', $img))
                $img = '';
        }

        if (!$img)
        {
            $img = $this->xpathScalar(".//*[contains(@class, 'imageThumb thumb')]/img/@src");
            $img = preg_replace('/\._.+?\_.jpg/', '.jpg', $img);
        }

        $img = str_replace('._SL1500_.', '._AC_SL520_.', $img);
        $img = str_replace('._SL1200_.', '._AC_SL520_.', $img);
        $img = str_replace('._SL1000_.', '._AC_SL520_.', $img);
        $img = str_replace('._AC_SL1500_.', '._AC_SL520_.', $img);
        $img = str_replace('._AC_UL1192_.', '._AC_SL520_.', $img);

        return $img;
    }

    public function parseImages()
    {
        $images = array();
        $results = $this->xpathArray(".//div[@id='altImages']//ul/li[position() > 1]//img[contains(@src, '.jpg') and not(contains(@src, 'play-icon-overlay'))]/@src");
        foreach ($results as $img)
        {
            if (strstr($img, 'play-button'))
                continue;

            $img = preg_replace('/,\d+_\.jpg/', '.jpg', $img);
            $img = preg_replace('/\._.+?_\.jpg/', '.jpg', $img);
            $img = preg_replace('/\._SX\d+_SY\d+_.+?\.jpg/', '.jpg', $img);

            $images[] = $img;
        }
        return $images;
    }

    public function parseManufacturer()
    {
        $paths = array(
            ".//div[@id='mbc']/@data-brand",
            ".//a[@id='bylineInfo']",
            ".//*[@id='byline']//*[contains(@class, 'contributorNameID')]",
        );

        return str_replace(array('Brand:', 'Visit the ', 'Visita lo Store di', ' Store',), '', $this->xpathScalar($paths));
    }

    public function parseSku()
    {
        return self::parseAsinFromUrl($this->getUrl());
    }

    private static function parseAsinFromUrl($url)
    {
        $regex = '~/(?:exec/obidos/ASIN/|o/|gp/product/|gp/offer-listing/|(?:(?:[^"\'/]*)/)?dp/|)([0-9A-Z]{10})(?:(?:/|\?|\#)(?:[^"\'\s]*))?~isx';
        if (preg_match($regex, $url, $matches))
            return $matches[1];
        else
            return '';
    }

    public function parseInStock()
    {
        if ($this->xpathScalar(".//div[@id='availability']/span[contains(@class,'a-color-success')]"))
            return true;

        $availability = trim((string) $this->xpathScalar(".//div[@id='availability']//span[@class='a-size-medium a-color-success']"));

        if ($availability == 'Currently unavailable.' || $availability == 'Şu anda mevcut değil.' || $availability == 'Attualmente non disponibile.' || $availability == 'Momenteel niet verkrijgbaar.' || $availability == 'Non disponibile.' || $availability == 'غير متوفر حالياً.')
            return false;

        return true;
    }

    public function parseCategoryPath()
    {
        return $this->xpathArray(".//div[@id='wayfinding-breadcrumbs_feature_div']//li//a");
    }

    public function getFeaturesXpath()
    {

        return array(
            array(
                'name' => ".//table[contains(@id, 'productDetails_techSpec_section')]//th",
                'value' => ".//table[contains(@id, 'productDetails_techSpec_section')]//td",
            ),
            array(
                'name' => ".//table[contains(@id, 'technicalSpecifications_section')]//th",
                'value' => ".//table[contains(@id, 'technicalSpecifications_section')]//td",
            ),
            array(
                'name' => ".//table[contains(@id, 'productDetails_detailBullets_sections')]//th",
                'value' => ".//table[contains(@id, 'productDetails_detailBullets_sections')]//td",
            ),
            array(
                'name-value' => ".//*[@id='productDetailsTable']//li[not(@id) and not(@class)]",
                'separator' => ":",
            ),
            array(
                'name' => ".//*[@id='prodDetails']//td[@class='label']",
                'value' => ".//*[@id='prodDetails']//td[@class='value']",
            ),
            array(
                'name' => ".//*[contains(@id, 'technicalSpecifications_section')]//th",
                'value' => ".//*[contains(@id, 'technicalSpecifications_section')]//td",
            ),
            array(
                'name-value' => ".//div[@id='technical-data']//li",
                'separator' => ":",
            ),
            array(
                'name-value' => ".//div[@id='detail-bullets']//li",
                'separator' => ":",
            ),
            array(
                'name' => ".//div[@id='detailBullets_feature_div']//li/span/span[1]",
                'value' => ".//div[@id='detailBullets_feature_div']//li/span/span[2]",
            ),
            array(
                'name' => ".//div[@id='tech']//table//td[1]",
                'value' => ".//div[@id='tech']//table//td[2]",
            ),
        );
    }

    public function parseFeatures()
    {
        if ($features = parent::parseFeatures())
            return $features;

        if (!preg_match('/<div id="detailBullets_feature_div">.+?<\/div>/ims', $this->html, $matches))
            return array();

        $xpath = new XPath(Dom::createFromString($matches[0]));

        $names = $xpath->xpathArray(".//ul//li/span/span[1]");
        $values = $xpath->xpathArray(".//ul//li/span/span[2]");

        if (!$names || !$values || count($names) != count($values))
            return array();

        $features = array();
        for ($i = 0; $i < count($names); $i++)
        {
            $feature = array();
            $names[$i] = str_replace(":", ' ', $names[$i]);
            $feature['name'] = ucfirst(\sanitize_text_field(trim($names[$i], " \r\n:-")));
            $feature['name'] = \ExternalImporter\application\helpers\TextHelper::clear_utf8($names[$i]);
            $feature['value'] = trim(\sanitize_text_field($values[$i]), " \r\n:-");
            if (in_array($feature['name'], array('Condition')))
                continue;
            $features[] = $feature;
        }

        return $features;
    }

    public function getReviewsXpath()
    {
        //echo $this->xpath->getDom()->saveHtml(); exit;
        return array(
            array(
                'review' => ".//div[contains(@class, 'cr-lightbox-review-body')]",
                'rating' => ".//*[@id='cm-cr-dp-review-list']//span[@class='a-icon-alt']",
                'author' => ".//*[@id='cm-cr-dp-review-list']//span[@class='a-profile-name']",
                'date' => ".//*[@id='cm-cr-dp-review-list']//span[contsains(@class, 'review-date')]",
            ),
            array(
                'review' => ".//*[contains(@class, 'reviews-content')]//*[contains(@data-hook, 'review-body')]//div[@data-hook]",
                'rating' => ".//*[contains(@class, 'reviews-content')]//*[@data-hook='review-star-rating' or @data-hook='cmps-review-star-rating']",
                'author' => ".//*[contains(@class, 'reviews-content')]//*[@class='a-profile-name']",
                'date' => ".//*[contains(@class, 'reviews-content')]//*[@data-hook='review-date']",
            ),
            array(
                'review' => ".//div[@id='cm-cr-dp-review-sort-type']",
                'rating' => ".//div[@id='cm-cr-dp-review-list']//i[contains(@class, 'a-icon-star')]/span",
                'author' => ".//div[@id='cm-cr-dp-review-list']//span[@class='a-profile-name']",
                'date' => ".//div[@id='cm-cr-dp-review-list']//div[contains(@class, 'review-date')]",
            ),
            array(
                'review' => ".//*[@id='revMH']//*[contains(@id, 'revData-dpReviewsMostHelpful')]/div[@class='a-section']",
                'rating' => ".//*[@id='revMH']//a[@class='noTextDecoration']",
                'author' => ".//*[@id='revMH']//span[@class='a-color-secondary']/span[@class='a-color-secondary']",
                'date' => ".//*[@id='revMH']//span[@class='a-icon-alt']",
            ),
            array(
                'review' => ".//*[@id='cm-cr-dp-review-list']//*[@data-hook='review-body']",
                'rating' => ".//*[@id='cm-cr-dp-review-list']//i[@data-hook='review-star-rating']",
                'author' => ".//*[@id='cm-cr-dp-review-list']//span[@class='a-profile-name']",
                'date' => ".//*[@id='cm-cr-dp-review-list']//span[@data-hook='review-date']",
            ),

        );
    }

    public function parseCurrencyCode()
    {
        if (strstr($this->parsePrice(), 'USD'))
            return 'USD';
        if (strstr($this->parsePrice(), 'AUD'))
            return 'AUD';

        switch ($this->host)
        {
            case 'amazon.com.au':
                return 'AUD';
            case 'amazon.com.br':
                return 'BRL';
            case 'amazon.ca':
                return 'CAD';
            case 'amazon.fr':
                return 'EUR';
            case 'amazon.de':
                return 'EUR';
            case 'amazon.in':
                return 'INR';
            case 'amazon.it':
                return 'EUR';
            case 'amazon.co.jp':
                return 'JPY';
            case 'amazon.com.mx':
                return 'MXN';
            case 'amazon.sg':
                return 'SGD';
            case 'amazon.es':
                return 'EUR';
            case 'amazon.com.tr':
                return 'TRY';
            case 'amazon.ae':
                return 'AED';
            case 'amazon.co.uk':
                return 'GBP';
            case 'amazon.com':
                return 'USD';
            case 'amazon.nl':
                return 'EUR';
            case 'amazon.sa':
                return 'SAR';
        }
    }

    public function parseGtin()
    {
        $paths = array(
            ".//*[@itemprop='gtin8']/@content",
            ".//*[@itemprop='gtin12']/@content",
            ".//*[@itemprop='gtin13']/@content",
            ".//*[@itemprop='gtin14']/@content",
            ".//*[@itemprop='isbn']/@content",
            ".//*[@itemprop='ean']/@content",
        );
        return $this->xpathScalar($paths);
    }

    public function afterParseFix(Product $product)
    {
        if ($product->features)
        {
            foreach ($product->features as $i => $feature)
            {
                if ($feature['name'] == 'Item model number')
                    $product->mpn = $feature['value'];

                if (in_array($feature['name'], array('Best Sellers Rank', 'Amazon Best Sellers Rank', 'Average Customer Review', 'ASIN', 'Customer Reviews', 'Amazon Bestsellers Rank', 'Best Sellers Rank')))
                    unset($product->features[$i]);
            }
            $product->features = array_values($product->features);
        }

        $product->description = str_replace('<li>Clicca qui per verificare la compatibilità di questo prodotto con il tuo modello</li>', '', (string) $product->description);

        return $product;
    }
}
