<?php

namespace ExternalImporter\application\libs\pextractor\parser\parsers;

defined('\ABSPATH') || exit;

/**
 * EdxorgAdvanced class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class EdxorgAdvanced extends AdvancedParser
{
    public function parseLinks()
    {
        if ($urls = $this->_parseLinksSearch())
            return $urls;

        $path = array(
            ".//div[@class='discovery-card-inner-wrapper']//a/@href",
        );

        return $this->xpathArray($path);
    }

    protected function _parseLinksSearch()
    {
        // search page
        if (!$query = parse_url($this->getUrl(), PHP_URL_QUERY))
            return array();
        parse_str($query, $arr);
        if (!isset($arr['q']))
            return array();

        $request_url = 'https://igsyv1z1xi-dsn.algolia.net/1/indexes/*/queries?x-algolia-agent=Algolia%20for%20JavaScript%20(4.17.0)%3B%20Browser%20(lite)%3B%20JS%20Helper%20(3.12.0)&x-algolia-api-key=1f72394b5b49fc876026952685f5defe&x-algolia-application-id=IGSYV1Z1XI';

        $response = \wp_remote_post($request_url, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => '{"requests":[{"indexName":"product","params":"clickAnalytics=true&facetFilters=%5B%22product%3ACourse%22%5D&facets=%5B%22product%22%2C%22availability%22%2C%22level%22%2C%22language%22%2C%22partner%22%2C%22program_type%22%2C%22skills.skill%22%2C%22subject%22%5D&filters=(product%3A%22Course%22%20OR%20product%3A%22Program%22%20OR%20product%3A%22Executive%20Education%22%20OR%20product%3A%22Boot%20Camp%22%20OR%20product%3A%222U%20Degree%22)%20AND%20NOT%20blocked_in%3A%22UA%22%20AND%20(allowed_in%3A%22null%22%20OR%20allowed_in%3A%22UA%22)&hitsPerPage=1000&page=0&query=' . $arr['q'] . '&tagFilters="}]}',
            'method' => 'POST'
        ));
        if (\is_wp_error($response))
            return array();

        $body = \wp_remote_retrieve_body($response);
        if (!$body)
            return array();
        $js_data = json_decode($body, true);

        if (!$js_data || !isset($js_data['results'][0]['hits']))
            return array();

        $urls = array();
        foreach ($js_data['results'][0]['hits'] as $hit)
        {
            $urls[] = $hit['marketing_url'];
        }
        return $urls;
    }

    public function parseTitle()
    {
        $paths = array(
            ".//div[@class='program']//div[@class='title']",
        );

        return $this->xpathScalar($paths);
    }

    public function parseDescription()
    {
        $description = '';

        if ($pieces = $this->xpathArray(".//div[contains(@class, 'program-body')]//ul//li"))
            $description .= '<h3>What you will learn</h3><ul><li>' . join('</li><li>', $pieces) . '</li></ul>';

        if ($d = $this->xpathScalar(".//div[contains(@class, 'program-body')]//div[@class='overview-info']", true))
            $description .= '<h3>Program Overview</h3>' . $d;

        $titles = $this->xpathArray(".//div[contains(@class, 'program-body')]//ol[@class='pathway']//div[contains(@class, 'collapsible-title')]");
        $bodies = $this->xpathArray(".//div[contains(@class, 'program-body')]//ol[@class='pathway']//div[contains(@class, 'collapsible-body')]//*[contains(@class, '-3')]", true);
        if ($titles && count($titles) == count($bodies))
        {
            $description .= '<h3>Courses in this program</h3>';
            foreach ($titles as $i => $title)
            {
                $description .= '<h4>' . $title . '</h4>';

                if ($i == count($title) - 1)
                    $description .= '<ul>' . $bodies[$i] . '</ul>';
                else
                    $description .= '<p>' . $bodies[$i] . '</p>';
            }
        }

        if ($description)
            return $description;

        if ($d = $this->xpathScalar(".//div[@class='course-description']", true))
            $description .= '<h3>About this course</h3>' . $d;

        if ($pieces = $this->xpathArray(".//div[@class='course-description d-flex flex-column']//ul//li"))
            $description .= '<h3>What you will learn</h3><ul><li>' . join('</li><li>', $pieces) . '</li></ul>';

        return $description;
    }

    public function parsePrice()
    {
        $paths = array(
            ".//div[@class='details']//div[@class='main d-flex flex-wrap']/text()",
            ".//div[@class='details']//div[@class='main d-flex flex-wrap']/span/text()",
            ".//div[@class='program-price d-flex flex-wrap']",
        );

        return $this->xpathScalar($paths);
    }

    public function parseOldPrice()
    {
        $paths = array(
            ".//div[@class='details']//div[@class='font-weight-normal']//s",
        );

        return $this->xpathScalar($paths);
    }

    public function parseManufacturer()
    {
        $paths = array(
            ".//div[@class='partner']//img/@alt",
        );

        return $this->xpathScalar($paths);
    }

    public function parseCategoryPath()
    {
        $paths = array(
            ".//ol[@class='breadcrumb-list list-inline']//li[@class='breadcrumb-item']/a",
        );

        if ($categs = $this->xpathArray($paths))
        {
            array_shift($categs);
            return $categs;
        }
    }

    public function getFeaturesXpath()
    {
        return array(
            array(
                'name' => ".//ul[@class='list-group list-group-flush w-100']//div[@class='col d-flex']//span",
                'value' => ".//ul[@class='list-group list-group-flush w-100']//*[@class='col']",
            ),
        );
    }
}
