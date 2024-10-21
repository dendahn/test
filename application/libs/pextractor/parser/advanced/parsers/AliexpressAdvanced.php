<?php

namespace ExternalImporter\application\libs\pextractor\parser\parsers;

defined('\ABSPATH') || exit;

use ExternalImporter\application\libs\pextractor\parser\Product;
use ExternalImporter\application\libs\pextractor\ExtractorHelper;

use function ExternalImporter\prnx;

/**
 * AliexpressAdvanced class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class AliexpressAdvanced extends AdvancedParser
{

    protected $_product = array();
    protected $_c;

    public function getHttpOptions()
    {
        $user_agent = array('ia_archiver');
        return array('user-agent' => $user_agent[array_rand($user_agent)]);
    }

    protected function preParseProduct()
    {
        $this->_getProduct();
        return parent::preParseProduct();
    }

    public function parseLinks()
    {
        $urls = array();

        if ($urls = $this->xpathArray(".//a[@class='pic-rind']/@href"))
        {
            foreach ($urls as $i => $url)
            {
                $urls[$i] = strtok($url, '?');
            }

            return $urls;
        }

        if (preg_match_all('~productDetailUrl":"(.+?)"~', $this->html, $matches))
        {
            $urls = $matches[1];
            foreach ($urls as $i => $url)
            {
                $urls[$i] = strtok($url, '?');
            }

            return $urls;
        }

        if (preg_match_all('~"productId":"(\d+)"~', $this->html, $matches))
        {
            foreach ($matches[1] as $i => $id)
            {
                $urls[] = 'https://www.aliexpress.com/item/' . $id . '.html';
            }
            return $urls;
        }
    }

    public function parsePagination()
    {
        $path = array(
            ".//div[@id='pagination-bottom']//a/@href",
        );

        return $this->xpathArray($path);
    }

    public function _getProduct()
    {
        if (!preg_match('/window\.runParams = .+?data: (.+?)\n/ims', $this->html, $matches))
            return;

        $result = json_decode($matches[1], true);

        if (!$result || !isset($result['productInfoComponent']))
            return false;

        $this->_product = $result;
    }

    public function parseTitle()
    {
        if (isset($this->_product['productInfoComponent']['subject']))
            return $this->_product['productInfoComponent']['subject'];

        $xpath = array(
            ".//h1[@itemprop='name']",
            ".//div[@class='product-title']",
            ".//h1",
        );

        return $this->xpathScalar($xpath);
    }

    public function parseDescription()
    {
        $xpath = array(
            ".//div[contains(@class, 'ProductDescription-module_content')]",
        );

        if ($d = $this->xpathScalar($xpath, true))
            return $d;

        if (!preg_match('/"descriptionUrl":"(.+?)"/', $this->html, $matches))
            return '';

        $d = $this->getRemote($matches[1]);
        $d = preg_replace('/<script.?>.+?<\/script>/', '', $d);
        return $d;
    }

    public function parseFeatures()
    {
        if (!isset($this->_product['productPropComponent']['props']) || !is_array($this->_product['productPropComponent']['props']))
            return array();

        $features = array();
        foreach ($this->_product['productPropComponent']['props'] as $prop)
        {
            $feature = array();
            $feature['name'] = \sanitize_text_field($prop['attrName']);
            $feature['value'] = \sanitize_text_field($prop['attrValue']);
            $features[] = $feature;
        }

        return $features;
    }

    public function parsePrice()
    {
        if (isset($this->_product['priceComponent']['discountPrice']['minActivityAmount']['value']))
            return $this->_product['priceComponent']['discountPrice']['minActivityAmount']['value'];

        if (isset($this->_product['priceComponent']['origPrice']['minAmount']['value']))
            return $this->_product['priceComponent']['origPrice']['minAmount']['value'];

        $xpath = array(
            ".//div[contains(@class, 'Product_Price__container')]//span[contains(@class, 'product-price-current')]",
            ".//*[@id='j-multi-currency-price']//*[@itemprop='lowPrice']",
            ".//dl[@class='product-info-current']//span[@itemprop='price' or @itemprop='lowPrice']",
            ".//div[@class='cost-box']//b",
            ".//*[@id='j-sku-discount-price']",
            ".//*[@id='j-sku-price']//*[@itemprop='lowPrice']",
            ".//*[@id='j-sku-price']",
            ".//div/span[contains(@class, 'uniformBannerBoxPrice')]",
        );

        $price = $this->xpathScalar($xpath);

        if ($price)
        {
            $parts = explode('-', $price);
            $price = $parts[0];
        }

        $price = str_replace(' ', '', $price);

        return $price;
    }

    public function parseOldPrice()
    {
        if (isset($this->_product['priceComponent']['origPrice']['minAmount']['value']))
            return $this->_product['priceComponent']['origPrice']['minAmount']['value'];

        $xpath = array(
            ".//div[contains(@class, 'Product_Price__container')]//span[contains(@class, 'product-price-origin')]",
            ".//dl[@class='product-info-original']//span[@id='sku-price']",
            ".//dl[@class='product-info-current']//span[@itemprop='price' or @itemprop='lowPrice']",
            ".//*[@id='j-sku-price']",
        );

        $price = $this->xpathScalar($xpath);

        if ($price)
        {
            $parts = explode('-', $price);
            $price = $parts[0];
        }

        $price = str_replace(' ', '', $price);

        return $price;
    }

    public function parseImage()
    {
        if ($images = $this->parseImages())
            return reset($images);

        $xpath = array(
            ".//figure[contains(@class, 'Product_Gallery')]//img/@src",
            ".//div[@id='img']//div[@class='ui-image-viewer-thumb-wrap']/a/img/@src",
            ".//*[@id='j-detail-gallery-main']//img/@src",
        );

        $img = $this->xpathScalar($xpath);
        $img = str_replace('.jpg_640x640', '.jpg_350x350', $img);

        return $img;
    }

    public function parseImages()
    {
        if (!preg_match('/"imagePathList":\["(.+?)"\],/', $this->html, $matches))
            return array();

        return explode('","', $matches[1]);
    }

    public function parseCurrencyCode()
    {
        if ($this->_c)
            return $this->_c;

        if ($this->_product && isset($this->_product['priceComponent']['origPrice']['minAmount']['currency']))
            return $this->_product['priceComponent']['origPrice']['minAmount']['currency'];

        $price = $this->xpathScalar(".//div/span[contains(@class, 'uniformBannerBoxPrice')]");

        $currency = $this->xpathScalar(".//*[@itemprop='priceCurrency']/@content");
        if (!$currency)
            $currency = 'USD';
        return $currency;
    }

    public function parseReviews()
    {
        if (!preg_match('~(\d+)\.html~', $this->getUrl(), $matches))
            return;

        $url = 'https://feedback.aliexpress.com/pc/searchEvaluation.do?productId=' . $matches[1] . '&lang=en_US&page=1&pageSize=100&filter=all&sort=complex_default';

        $response = $this->getRemoteJson($url);
        if (!$response || !isset($response['data']['evaViewList']))
            return array();

        $results = array();
        foreach ($response['data']['evaViewList'] as $r)
        {
            $review = array();
            if (!isset($r['buyerTranslationFeedback']))
                continue;

            $review['review'] = $r['buyerTranslationFeedback'];

            if (isset($r['buyerEval']))
                $review['rating'] = ExtractorHelper::ratingPrepare($r['buyerEval'] / 20);

            if (isset($r['buyerName']))
                $review['author'] = $r['buyerName'];

            if (isset($r['created_time']))
                $review['date'] = strtotime($r['evalDate']);

            $results[] = $review;
        }
        return $results;
    }

    public function afterParseFix(Product $product)
    {
        //$product->description = '';
        return $product;
    }
}
