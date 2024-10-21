<?php

namespace ExternalImporter\application\admin;

defined('\ABSPATH') || exit;

use ExternalImporter\application\components\ai\AiClient;
use ExternalImporter\application\Plugin;
use ExternalImporter\application\components\Config;

use function ExternalImporter\prnx;

/**
 * AiConfig class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2024 keywordrush.com
 */
class AiConfig extends Config
{
    public function page_slug()
    {
        return Plugin::getSlug() . '-settings-ai';
    }

    public function option_name()
    {
        return Plugin::getSlug() . '-settings-ai';
    }

    public function header_name()
    {
        return 'AI';
    }

    public function add_admin_menu()
    {
        \add_submenu_page('options.php', __('AI Settings', 'external-importer') . ' &lsaquo; ' . Plugin::getName(), __('AI Settings', 'external-importer'), 'manage_options', $this->page_slug(), array($this, 'settings_page'));
    }

    protected function options()
    {
        return array(

            'model' => array(
                'title' => __('AI Model', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getModelList(),
                'default' => 'gpt-3.5-turbo',
                'description' => __('Please be cautious with your model settings, as some AI models may be significantly more expensive than others.', 'external-importer'),
            ),
            'openai_key' => array(
                'title' => 'AI API key' . ' <span style="color:red;">*</span>',
                'description' => sprintf(__('Add your <a target="_blank" href="%s" href="">OpenAI</a> or <a target="_blank" href="%s" href="">Claude</a> API key according to the AI model you have selected.', 'external-importer'), 'https://platform.openai.com/api-keys', 'https://console.anthropic.com/settings/keys'),
                'callback' => array($this, 'render_password'),
                'default' => '',
                'validator' => array(
                    'trim',
                    array(
                        'call'    => array('\ExternalImporter\application\helpers\FormValidator', 'required'),
                        'message' => sprintf(__('The field "%s" can not be empty.', 'external-importer'), 'OpenAI API key'),
                    ),
                ),
            ),
            'language' => array(
                'title' => __('Language', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getLanguagesList(),
                'default' => self::getDefaultLang(),
            ),
            'temperature' => array(
                'title' => __('Creativity level', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => self::getCreativitiesList(),
                'default' => '0.75',
            ),
            'title_generator' => array(
                'title' => __('Title generator', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'external-importer'),
                    'rephrase' => __('Rephrase', 'external-importer'),
                    'translate' => __('Translate', 'external-importer'),
                    'shorten' => __('Shorten', 'external-importer'),
                    'generate_question_title' => __('Generate a question article title', 'external-importer'),
                    'generate_how_to_use_title' => __('Generate a how to use title', 'external-importer'),
                    'generate_review_title' => __('Generate a review title', 'external-importer'),
                    'generate_buyers_guide_title' => __('Generate a buyer\'s guide title', 'external-importer'),
                    'prompt1' => sprintf(__('Custom prompt #%d', 'external-importer'), 1),
                    'prompt2' => sprintf(__('Custom prompt #%d', 'external-importer'), 2),
                    'prompt3' => sprintf(__('Custom prompt #%d', 'external-importer'), 3),
                    'prompt4' => sprintf(__('Custom prompt #%d', 'external-importer'), 4),
                ),
                'default' => '',
            ),
            'description_generator' => array(
                'title' => __('Description/post generator', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'external-importer'),
                    'rewrite' => __('Rewrite', 'external-importer'),
                    'paraphrase' => __('Paraphrase', 'external-importer'),
                    'translate' => __('Translate', 'external-importer'),
                    'summarize' => __('Summarize', 'external-importer'),
                    'bullet_points' => __('Bullet points', 'external-importer'),
                    'turn_into_advertising' => __('Turn into advertising', 'external-importer'),
                    'cta_text' => __('Generate CTA text', 'external-importer'),
                    'craft_description' => __('Craft a product description', 'external-importer'),
                    'write_paragraphs' => __('Write a few paragraphs', 'external-importer'),
                    'write_article' => __('Write an article', 'external-importer'),
                    'write_how_to_use' => __('Write a how to use instruction', 'external-importer'),
                    'write_review' => __('Write a review', 'external-importer'),
                    'write_buyers_guide' => __('Write a buyer\'s guide', 'external-importer'),
                    'prompt1' => sprintf(__('Custom prompt #%d', 'external-importer'), 1),
                    'prompt2' => sprintf(__('Custom prompt #%d', 'external-importer'), 2),
                    'prompt3' => sprintf(__('Custom prompt #%d', 'external-importer'), 3),
                    'prompt4' => sprintf(__('Custom prompt #%d', 'external-importer'), 4),
                ),
                'default' => '',
            ),
            'short_description_generator' => array(
                'title' => __('Short description generator', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'external-importer'),
                    'summarize' => __('Summarize', 'external-importer'),
                    'bullet_points' => __('Bullet points', 'external-importer'),
                    'cta_text' => __('Generate CTA text', 'external-importer'),
                    'prompt1' => sprintf(__('Custom prompt #%d', 'external-importer'), 1),
                    'prompt2' => sprintf(__('Custom prompt #%d', 'external-importer'), 2),
                    'prompt3' => sprintf(__('Custom prompt #%d', 'external-importer'), 3),
                    'prompt4' => sprintf(__('Custom prompt #%d', 'external-importer'), 4),
                ),
                'default' => '',
            ),
            'reviews_generator' => array(
                'title' => __('Reviews (comments) generator', 'external-importer'),
                'callback' => array($this, 'render_dropdown'),
                'dropdown_options' => array(
                    '' => __('Disabled', 'external-importer'),
                    'rewrite' => __('Rewrite', 'external-importer'),
                    'rephrase' => __('Rephrase', 'external-importer'),
                    'translate' => __('Translate', 'external-importer'),
                    'prompt1' => sprintf(__('Custom prompt #%d', 'external-importer'), 1),
                    'prompt2' => sprintf(__('Custom prompt #%d', 'external-importer'), 2),
                    'prompt3' => sprintf(__('Custom prompt #%d', 'external-importer'), 3),
                    'prompt4' => sprintf(__('Custom prompt #%d', 'external-importer'), 4),
                ),
                'default' => '',
            ),
            'prompt1' => array(
                'title' => sprintf(__('Custom prompt #%d', 'external-importer'), 1),
                'description' => __('For custom prompts, you can use placeholders such as %title%, %description%, %description_html%, %lang%, %features%, %reviews%, %review%, %title_new%, and %description_new%.', 'external-importer')
                    . ' ' . sprintf(__('<a target="_blank" href="%s">More info...</a>', 'external-importer'), 'https://ei-docs.keywordrush.com/ai/custom-prompts'),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
            ),
            'prompt2' => array(
                'title' => sprintf(__('Custom prompt #%d', 'external-importer'), 2),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
            ),
            'prompt3' => array(
                'title' => sprintf(__('Custom prompt #%d', 'external-importer'), 3),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
            ),
            'prompt4' => array(
                'title' => sprintf(__('Custom prompt #%d', 'external-importer'), 4),
                'callback' => array($this, 'render_textarea'),
                'validator' => array(
                    'trim',
                ),
            ),
        );
    }

    public function settings_page()
    {
        PluginAdmin::getInstance()->render('settings', array('page_slug' => $this->page_slug()));
    }

    public static function getLanguagesList()
    {
        return array_combine(array_values(self::getLanguages()), array_values(self::getLanguages()));
    }

    public static function getLanguages()
    {
        $list = array(
            'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'en' => 'English',
            'tl' => 'Filipino',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'el' => 'Greek',
            'iw' => 'Hebrew',
            'hi' => 'Hindi',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'ms' => 'Malay',
            'no' => 'Norwegian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'pt_BR' => 'Portuguese (Brazil)',
            'pt_PT' => 'Portuguese (Portugal)',
            'ro' => 'Romanian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
        );

        ksort($list);
        return $list;
    }

    public static function getCreativitiesList()
    {
        return array(
            '0.0' => __('Min (more factual, but repetiteve)', 'external-importer'),
            '0.5' => __('Low', 'external-importer'),
            '0.75' => __('Optimal', 'external-importer') . ' ' . __('(recommended)', 'external-importer'),
            '1.0' => __('Optimal+', 'external-importer'),
            '1.2' => __('Hight', 'external-importer'),
            '1.5' => __('Max (less factual, but creative)', 'external-importer'),
        );
    }

    public static function getDefaultLang()
    {
        $parts = explode('_', \get_locale());
        $lang = strtolower(reset($parts));
        $languages = self::getLanguages();

        if (isset($languages[$lang]))
            return $languages[$lang];
        else
            return 'English';
    }

    public static function getModelList()
    {
        $models = AiClient::models();
        $res = array();
        foreach ($models as $key => $model)
        {
            $models[$key] = $model['name'];
        }

        return $models;
    }
}
