<?php

namespace ExternalImporter\application\libs\pextractor\parser\parsers;

defined('\ABSPATH') || exit;

use ExternalImporter\application\libs\pextractor\parser\Product;

/**
 * WehkampnlAdvanced class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class WehkampnlAdvanced extends AdvancedParser
{

    public function parseLinks()
    {
        $path = array(
            ".//a[contains(@class, 'UI_ProductTile_tile')]/@href",
        );

        return $this->xpathArray($path);
    }

    public function parsePagination()
    {
        $path = array(
            ".//nav[contains(@class, 'blaze-row')]//li/a[contains(@href, 'PI=') or contains(@href, 'pagina=')]/@href",
        );

        return $this->xpathArray($path);
    }

    public function parseCategoryPath()
    {
        $paths = array(
            ".//ul[@class='show-for-tablet-portrait UI_Breadcrumb_list padding-horizontal-small color-black-opacity-88 text-overflow position-relative list-reset margin-reset margin-right-auto']//a",
        );

        $categs = $this->xpathArray($paths);
        array_shift($categs);
        array_shift($categs);
        return $categs;
    }

    public function parseTitle()
    {
        return $this->xpathScalar(array(".//h3[@class='type-heading-m margin-vertical-medium color-black-opacity-88']"));
    }

    public function parseOldPrice()
    {
        $paths = array(
            ".//span[@class='position-relative type-price-discount ct-text-secondary']",
            ".//*[contains(@class, 'position-relative UI_Currency_scratch')]", ".//*[@class='buying-area__price']//span[@class='position-relative UI_Currency_scratch font-weight-light margin-right-xsmall font-size-regular']",
        );

        return $this->xpathScalar($paths);
    }

    public function parseImage()
    {
        if ($images = $this->parseImages())
            return reset($images);
    }

    public function parseImages()
    {
        $images = array();
        $results = $this->xpathArray(".//ul[contains(@class, 'FullScreenDialog')]//li//img/@data-src");
        foreach ($results as $img)
        {
            $img = add_query_arg('w', '2048', $img);
            $img = add_query_arg('h', '3072', $img);

            $images[] = $img;
        }
        return $images;
    }

    public function getFeaturesXpath()
    {
        return array(
            array(
                'name' => ".//div[contains(@class, 'Specifications')]//th[1]",
                'value' => ".//div[contains(@class, 'Specifications')]//td[2]",
            ),
        );
    }

    public function parseFeatures()
    {
        $features = array();

        if (preg_match('~"bullets":\[(.+?)\]~', $this->html, $matches))
        {
            $bullets = explode(',', $matches[1]);
            foreach ($bullets as $bullet)
            {
                $bullet = trim($bullet, '"');
                $parts = explode(': ', $bullet);

                if (count($parts) != 2)
                    continue;

                $features[] = array('name' => $parts[0], 'value' => $parts[1]);
            }
        }

        if (!$features)
            $features = parent::parseFeatures();

        if (preg_match('~"gtin13":"(\d+)"~', $this->html, $matches))
            $features[] = array('name' => 'EAN', 'value' => $matches[1]);

        return $features;
    }

    public function parseCurrencyCode()
    {
        return 'EUR';
    }

    public function afterParseFix(Product $product)
    {
        $product->image = add_query_arg('w', '2048', $product->image);
        $product->image = add_query_arg('h', '3072', $product->image);

        return $product;
    }
}
