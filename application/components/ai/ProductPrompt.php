<?php

namespace ExternalImporter\application\components\ai;

use ExternalImporter\application\admin\AiConfig;
use ExternalImporter\application\libs\pextractor\parser\Product;
use ExternalImporter\application\helpers\TextHelper;
use ExternalImporter\application\helpers\WooHelper;

use function ExternalImporter\prnx;

defined('\ABSPATH') || exit;

/**
 * ProductPrompt class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */

class ProductPrompt extends Prompt
{
    const MAX_INPUT_CONTENT_LENGTH = 10000;
    const MAX_REVIEW_LENGTH = 1000;

    protected $product;
    protected $product_new;

    public function setProduct(Product $product)
    {
        $this->product = clone $product;
    }

    public function setProductNew(Product $product)
    {
        $this->product_new = $product;
    }

    public function rephraseProductTitle()
    {
        $prompt = "Rephrase the product title: \"%title%\".";
        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function translateProductTitle()
    {
        $prompt = "Translate the product title into %lang%: \"%title%\".";
        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function shortenProductTitle()
    {
        $prompt = "Shorten the product title to 5-7 words: \"%title%\".";
        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function generateQuestionProductTitle()
    {
        $prompt = "Write a list of 5 questions related to the product \"%title%\".\n\nQuestions list:";
        $titles = ContentHelper::listToArray($this->query($prompt));
        shuffle($titles);
        $title = reset($titles);
        return ContentHelper::prepareProductTitle($title);
    }

    public function generateGuideProductTitle()
    {
        $prompt = "Write a list of 5 titles for buying guide about the product \"%title%\".\n\nTitles:";
        $titles = ContentHelper::listToArray($this->query($prompt));
        shuffle($titles);
        $title = reset($titles);
        return ContentHelper::prepareProductTitle($title);
    }

    public function generateReviewProductTitle()
    {
        $prompt = "Write a title for review post about \"%title%\". Keep it short.\n\nTitle:";
        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function generateHowToUseTitle()
    {
        $prompt = "Write a title for how to use post about \"%title%\". Keep it short.\n\nTitle:";
        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function rewriteProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Rewrite the following product description of the product titled \"%title%\".";
        $prompt .= "\n\nProduct description:\n%description%";
        $prompt .= "\n\nResult:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function paraphraseProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Paraphrase the following product description of the product titled \"%title%\".";
        $prompt .= "\n\nProduct description:\n%description%";
        $prompt .= "\n\nResult:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function translateProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Save HTML fomatting in answer. Translate to %lang% the product description below:";
        $prompt .= "\n%description_html%";

        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function summarizeProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Summarize the following product description of the product titled \"%title%\".";
        $prompt .= "\n\nProduct description:\n%description%";
        $prompt .= "\n\nResult:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function bulletPointsProductDescription()
    {
        if (!$this->product->description && !$this->product->features)
            return '';

        $prompt = "Summarize the product description below in bullet points list. Answer from 5 to 8 Bullet points.";
        $prompt .= "\nProduct title: %title%.";
        $prompt .= "\nProduct description: %description%";
        if ($this->product->features)
            $prompt .= "\nProduct specifications: %features%";
        $prompt .= "\n\nBullet points:";
        if (!$list = ContentHelper::listToArray($this->query($prompt)))
            return array();

        return '<ul><li>' . join('</li><li>', $list) . '</li><ul>';
    }

    public function turnIntoAdvertisingProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Turn into advertising the following product description of the product titled \"%title%\".";
        $prompt .= "\n\nProduct description:\n%description%";
        $prompt .= "\n\nResult:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function ctaTextProductDescription()
    {
        if (!$this->product->description)
            return '';

        $prompt = "Write a few sentences CTA for the product \"%title%\".";
        $prompt .= "\n\nProduct description:\n%description%";
        $prompt .= "\n\nCTA:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function craftProductDescription()
    {
        $prompt = "Craft a product description for the product \"%title%\".";
        if ($this->product->features)
            $prompt .= "\nProduct specifications: %features%";
        $prompt .= "\n\nProduct description:";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function writeParagraphsProductDescription()
    {
        $prompt = "Write a few paragraphs about the product: \"%title%\".";
        if ($this->product->description)
            $prompt .= "\nProduct description: %description%";
        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function writeArticleProductDescription()
    {
        $prompt = "Product title: \"%title%\"";
        if ($this->product->description)
            $prompt .= "\n\nProduct description:\n%description%";
        if ($this->product->features)
            $prompt .= "\n\nProduct features:\n%features%";

        $prompt .= "\n\nWrite an article about the product ";
        if ($this->product_new->title != $this->product->title)
            $prompt .= "and titled: \"%title_new%\". ";
        else
            $prompt .= ". ";

        $prompt .= "Format in html. Do not include CSS styles.";
        $prompt .= "\n\n\n<html><body>[ARTICLE TEXT]<body></html>";

        return ContentHelper::prepareArticle($this->query($prompt), $this->product_new->title);
    }

    public function writeBuyersGuideProductDescription()
    {
        $prompt = "Product title: \"%title%\"";
        if ($this->product->description)
            $prompt .= "\n\nProduct description:\n%description%";
        if ($this->product->features)
            $prompt .= "\n\nProduct features:\n%features%";

        $prompt .= "\n\nWrite a Buying Guide on how to select \"%title%\".";

        $prompt .= "Divide the Guide with subheadings. Format in html. Do not include CSS styles.";

        return ContentHelper::prepareArticle($this->query($prompt), $this->product_new->title);
    }

    public function writeReviewProductDescription()
    {
        $prompt = "Product title: \"%title%\"";
        if ($this->product->description)
            $prompt .= "\n\nProduct description:\n%description%";
        if ($this->product->reviews)
            $prompt .= "\n\nUser feedback:\n%reviews%";

        $prompt .= "\n\n\nWrite your own review for the product. ";

        if (rand(0, 1) and $this->product->reviews)
            $prompt .= "You can take into account some user feedback. ";

        if (rand(0, 1))
            $prompt .= "Add a few subheadings. ";

        if (rand(0, 4) == 4)
            $prompt .= "Include Pros and Cons. ";

        $prompt .= " Format in html. Do not include CSS styles.";
        $prompt .= "\n\n\n<html><body>[YOUR REVIEW TEXT]<body></html>";

        return ContentHelper::prepareArticle($this->query($prompt), $this->product_new->title);
    }

    public function writeHowToUseProductDescription()
    {
        $prompt = "Product title: \"%title%\"";
        if ($this->product->description)
            $prompt .= "\n\nProduct description:\n%description%";
        if ($this->product->features)
            $prompt .= "\n\nProduct features:\n%features%";

        $prompt .= "\n\nWrite an instruction of How to use this product for beginners. ";
        $prompt .= "Add subheadings. Use lists. Format in html. Do not include CSS styles.";
        $prompt .= "\n\n\n<html><body>[HOW TO USE]<body></html>";

        return ContentHelper::prepareArticle($this->query($prompt), $this->product_new->title);
    }

    protected function prepareParams(array $params, $prompt = '')
    {
        if (!$this->product)
            return $params;

        if (!isset($params['title']))
            $params['title'] = $this->product->title;

        if (!isset($params['description']))
            $params['description'] = ContentHelper::htmlToText(TextHelper::truncate($this->product->description, self::MAX_INPUT_CONTENT_LENGTH));

        if (!isset($params['description_html']))
            $params['description_html'] = TextHelper::truncate($this->product->description, self::MAX_INPUT_CONTENT_LENGTH);

        if (!isset($params['lang']))
            $params['lang'] = $this->lang;

        if (!isset($params['features']))
        {
            $features = array();
            foreach ($this->product->features as $f)
            {
                $features[] = $f['name'] . ': ' . $f['value'];
            }
            $params['features'] = join("\n", $features);
            $params['features'] = TextHelper::truncate($params['features'], self::MAX_INPUT_CONTENT_LENGTH);
        }

        if (!isset($params['reviews']))
        {
            $reviews = array();
            foreach ($this->product->reviews as $i => $r)
            {
                $reviews[] = $i + 1 . ') ' . TextHelper::truncate(ContentHelper::htmlToText($r['review']), self::MAX_REVIEW_LENGTH);
            }
            $params['reviews'] = join("\n\n", $reviews);
            $params['reviews'] = TextHelper::truncate($params['reviews'], self::MAX_INPUT_CONTENT_LENGTH);
        }

        if (!isset($params['title_new']))
            $params['title_new'] = $this->product_new->title;

        if (!isset($params['description_new']))
            $params['description_new'] = ContentHelper::htmlToText(TextHelper::truncate($this->product_new->description, self::MAX_INPUT_CONTENT_LENGTH));

        if (!isset($params['description_html_new']))
            $params['description_html_new'] = TextHelper::truncate($this->product_new->description, self::MAX_INPUT_CONTENT_LENGTH);

        return $params;
    }

    public function rephraseReview($review)
    {
        $prompt = "Rephrase the following product review:";
        $prompt .= "\n\n";
        $prompt .= ContentHelper::htmlToText($review);
        $prompt .= "\n\n";
        $prompt .= "Review text:";

        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function rewriteReview($review)
    {
        $prompt = "Rewrite the following product user review:";
        $prompt .= "\n\n";
        $prompt .= ContentHelper::htmlToText($review);
        $prompt .= "\n\n";
        $prompt .= "Rewrited review:";

        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function translateReview($review)
    {
        $prompt = "Translate into %lang% the product user review below:";
        $prompt .= "\n\n";
        $prompt .= ContentHelper::htmlToText($review);

        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function customPromptTitle($n)
    {
        $prompt = AiConfig::getInstance()->option('prompt' . $n);
        if (!$prompt)
            return '';

        return ContentHelper::prepareProductTitle($this->query($prompt));
    }

    public function customPromptTitle1()
    {
        return $this->customPromptTitle(1);
    }

    public function customPromptTitle2()
    {
        return $this->customPromptTitle(2);
    }

    public function customPromptTitle3()
    {
        return $this->customPromptTitle(3);
    }

    public function customPromptTitle4()
    {
        return $this->customPromptTitle(4);
    }

    public function customPromptReview($n, $review)
    {
        if (!$prompt = AiConfig::getInstance()->option('prompt' . $n))
            return '';

        $review = ContentHelper::htmlToText($review);
        $params = array('review' => $review);

        return ContentHelper::prepareParagraphs($this->query($prompt, $params));
    }

    public function customPromptReview1($review)
    {
        return $this->customPromptReview(1, $review);
    }

    public function customPromptReview2($review)
    {
        return $this->customPromptReview(2, $review);
    }

    public function customPromptReview3($review)
    {
        return $this->customPromptReview(3, $review);
    }

    public function customPromptReview4($review)
    {
        return $this->customPromptReview(4, $review);
    }

    public function customPromptDescription($n)
    {
        if (!$prompt = AiConfig::getInstance()->option('prompt' . $n))
            return '';

        return ContentHelper::prepareParagraphs($this->query($prompt));
    }

    public function customPromptDescription1()
    {
        return $this->customPromptDescription(1);
    }

    public function customPromptDescription2()
    {
        return $this->customPromptDescription(2);
    }

    public function customPromptDescription3()
    {
        return $this->customPromptDescription(3);
    }

    public function customPromptDescription4()
    {
        return $this->customPromptDescription(4);
    }
}
