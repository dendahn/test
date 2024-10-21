<?php

namespace ExternalImporter\application\components\ai;

use ExternalImporter\application\libs\pextractor\parser\Product;
use ExternalImporter\application\admin\AiConfig;
use ExternalImporter\application\libs\pextractor\parser\ProductProcessor;

use function ExternalImporter\prn;
use function ExternalImporter\prnx;

defined('\ABSPATH') || exit;

/**
 * AiProductProcessor class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */

class AiProductProcessor
{

    public static function maybeApplayAi(Product $product)
    {
        if (!$api_key = AiConfig::getInstance()->option('openai_key'))
            return $product;

        $title_generator = AiConfig::getInstance()->option('title_generator');
        $description_generator = AiConfig::getInstance()->option('description_generator');
        $short_description_generator = AiConfig::getInstance()->option('short_description_generator');
        $reviews_generator = AiConfig::getInstance()->option('reviews_generator');

        if (!$title_generator && !$description_generator && !$short_description_generator && !$reviews_generator)
            return $product;

        $lang = AiConfig::getInstance()->option('language');
        $temperature = AiConfig::getInstance()->option('temperature');
        $model = AiConfig::getInstance()->option('model');

        $api_key = explode(',', $api_key);
        $api_key = trim($api_key[array_rand($api_key)]);

        $prompt = new ProductPrompt($api_key, $model);

        $prompt->setProduct($product);
        $prompt->setProductNew($product);
        $prompt->setLang($lang);
        $prompt->setTemperature($temperature);

        if (\ExternalImporter\application\Plugin::isDevEnvironment())
            mt_srand(12345678);

        $title_methods = array(
            'rephrase' => 'rephraseProductTitle',
            'translate' => 'translateProductTitle',
            'shorten' => 'shortenProductTitle',
            'generate_question_title' => 'generateQuestionProductTitle',
            'generate_buyers_guide_title' => 'generateGuideProductTitle',
            'generate_review_title' => 'generateReviewProductTitle',
            'generate_how_to_use_title' => 'generateHowToUseTitle',
            'prompt1' => 'customPromptTitle1',
            'prompt2' => 'customPromptTitle2',
            'prompt3' => 'customPromptTitle3',
            'prompt4' => 'customPromptTitle4',
        );

        if ($title_generator && isset($title_methods[$title_generator]))
        {
            $method = $title_methods[$title_generator];
            if (method_exists($prompt, $method))
            {
                try
                {
                    $product->title = $prompt->$method();
                }
                catch (\Exception $e)
                {
                    throw new \Exception('OpenAI: Title generation error: ' . $e->getMessage());
                }
            }
        }

        $description_methods = array(
            'rewrite' => 'rewriteProductDescription',
            'paraphrase' => 'paraphraseProductDescription',
            'translate' => 'translateProductDescription',
            'summarize' => 'summarizeProductDescription',
            'bullet_points' => 'bulletPointsProductDescription',
            'turn_into_advertising' => 'turnIntoAdvertisingProductDescription',
            'cta_text' => 'ctaTextProductDescription',
            'write_paragraphs' => 'writeParagraphsProductDescription',
            'craft_description' => 'craftProductDescription',
            'write_article' => 'writeArticleProductDescription',
            'write_buyers_guide' => 'writeBuyersGuideProductDescription',
            'write_review' => 'writeReviewProductDescription',
            'write_how_to_use' => 'writeHowToUseProductDescription',
            'prompt1' => 'customPromptDescription1',
            'prompt2' => 'customPromptDescription2',
            'prompt3' => 'customPromptDescription3',
            'prompt4' => 'customPromptDescription4',
        );

        if ($description_generator && isset($description_methods[$description_generator]))
        {
            $method = $description_methods[$description_generator];
            if (method_exists($prompt, $method))
            {
                try
                {
                    $product->description = $prompt->$method();
                }
                catch (\Exception $e)
                {
                    throw new \Exception('OpenAI: Description generation error: ' . $e->getMessage());
                }
            }
        }

        $short_description_methods = array(
            'summarize' => 'summarizeProductDescription',
            'bullet_points' => 'bulletPointsProductDescription',
            'cta_text' => 'ctaTextProductDescription',
            'prompt1' => 'customPromptDescription1',
            'prompt2' => 'customPromptDescription2',
            'prompt3' => 'customPromptDescription3',
            'prompt4' => 'customPromptDescription4',
        );

        if ($short_description_generator && isset($short_description_methods[$short_description_generator]))
        {
            $method = $short_description_methods[$short_description_generator];
            if (method_exists($prompt, $method))
            {
                try
                {
                    $product->shortDescription = $prompt->$method();
                }
                catch (\Exception $e)
                {
                    throw new \Exception('OpenAI: Short description generation error: ' . $e->getMessage());
                }
            }
        }

        $reviews_methods = array(
            'rewrite' => 'rewriteReview',
            'rephrase' => 'rephraseReview',
            'translate' => 'translateReview',
            'prompt1' => 'customPromptReview1',
            'prompt2' => 'customPromptReview2',
            'prompt3' => 'customPromptReview3',
            'prompt4' => 'customPromptReview4',
        );

        if ($reviews_generator && isset($reviews_methods[$reviews_generator]))
        {
            $method = $reviews_methods[$reviews_generator];
            if (method_exists($prompt, $method) && $product->reviews)
            {
                foreach ($product->reviews as $i => $review)
                {
                    try
                    {
                        $product->reviews[$i]['review'] = $prompt->$method($review['review']);
                    }
                    catch (\Exception $e)
                    {
                        throw new \Exception('OpenAI: Reviews generation error: ' . $e->getMessage());
                    }
                }
            }
        }

        $product = ProductProcessor::prepare($product);

        return $product;
    }
}
