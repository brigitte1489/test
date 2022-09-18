<?php
/**
 * License limited to a single site, for use on another site please purchase a license for this module.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Dingedi.com
 * @copyright Copyright 2020 © Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */

namespace Dingedi\PsTranslationsApi;

class DgTranslationTools
{
    static $modulesName = array('dgcontenttranslation', 'dgcreativeelementstranslation', 'dgtranslationall');

    /**
     *  Return api key of default provider if none provider is pass in parameter
     *
     * @param string $provider api provider
     * @return string
     */
    public static function getApiKey($provider = null)
    {
        if ($provider === null) {
            $provider = self::getProvider();
        }

        return self::getValue('dingedi_provider_' . $provider);
    }

    /**
     *  Get current translation provider
     *
     * @param bool|\Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider $asString
     * @return string
     */
    public static function getProvider($asString = true)
    {
        $provider = self::getValue('dingedi_provider_name');

        if ($asString) {
            return $provider;
        } else {
            foreach (self::getProvidersList() as $p) {
                if ($p->key === $provider) {
                    return $p;
                }
            }
        }
    }

    public static function getValue($key, $default_value = '')
    {
        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            return \Configuration::get($key, null, null, null, $default_value);
        } else {
            $value = \Configuration::get($key, null, null, null);

            if ($value === false) {
                return $default_value;
            }

            return $value;
        }
    }

    /**
     *  Get list of translation providers with API Key if defines
     *
     * @return array<\Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider>
     */
    public static function getProvidersList()
    {
        return array(
            new \Dingedi\PsTranslationsApi\TranslationsProviders\DeepLTranslateV2(),
            new \Dingedi\PsTranslationsApi\TranslationsProviders\GoogleTranslateV2(),
            new \Dingedi\PsTranslationsApi\TranslationsProviders\MicrosoftTranslateV3(),
            new \Dingedi\PsTranslationsApi\TranslationsProviders\YandexTranslateV2(),
            new \Dingedi\PsTranslationsApi\TranslationsProviders\YandexTranslateV15(),
            new \Dingedi\PsTranslationsApi\TranslationsProviders\DingediTranslateV1()
        );
    }

    /**
     * @return int
     */
    public static function getDefaultLangId()
    {
        $defaultLangId = (int)self::getValue('dingedi_default_lang', self::getValue('PS_LANG_DEFAULT'));

        if (is_array(\Language::getLanguage($defaultLangId))) {
            return $defaultLangId;
        }

        return (int)self::getValue('PS_LANG_DEFAULT');
    }

    /**
     * @throws \Exception
     */
    public static function saveSettings()
    {
        $data = \Tools::getValue('translation_data');

        $config = array();

        if (isset($data['exclude'])) {
            $config['dingedi_exclude'] = (string)$data['exclude'];
        }

        if (isset($data['excluded'])) {
            $excluded = ($data['excluded'] === 'false') ? [] : $data['excluded'];
            $excluded = implode(',', array_unique(array_map(function ($i) {
                return trim($i);
            }, $excluded)));
            $config['dingedi_excluded'] = $excluded;
        }

        if (isset($data['per_request'])) {
            $config['dingedi_per_request'] = (string)$data['per_request'];
        }

        if (isset($data['translation_filter'])) {
            $config['dingedi_translation_filter'] = (string)$data['translation_filter'];
        }

        if (isset($data['automatic_translation'])) {
            $config['dingedi_automatic_translation'] = $data['automatic_translation'] === "true";
        }

        if (isset($data['translation_modal_enabled'])) {
            $config['dingedi_translation_modal_enabled'] = $data['translation_modal_enabled'] === "true";
        }

        if (isset($data['translation_fields_enabled'])) {
            $config['dingedi_translation_fields_enabled'] = $data['translation_fields_enabled'] === "true";
        }

        if (isset($data['translation_fields_always_enabled'])) {
            $config['dingedi_translation_fields_always_enabled'] = $data['translation_fields_always_enabled'] === "true";
        }

        if (isset($data['automatic_translation_translate_all'])) {
            $config['dingedi_automatic_translation_translate_all'] = $data['automatic_translation_translate_all'] === "true" ? "1" : "0";
        }

        if (isset($data['automatic_translation_translate_tables'])) {
            $config['dingedi_automatic_translation_translate_tables'] = json_encode(json_decode($data['automatic_translation_translate_tables']));
        }

        if (isset($data['automatic_translation_id_lang_from'])) {
            $config['dingedi_automatic_translation_id_lang_from'] = (int)$data['automatic_translation_id_lang_from'];
        }

        if (isset($data['automatic_translation_ids_langs_to'])) {
            $config['dingedi_automatic_translation_ids_langs_to'] = implode(',', $data['automatic_translation_ids_langs_to']);
        }

        if (isset($data['smart_dictionary'])) {
            $config['dingedi_smart_dictionary'] = json_encode($data['smart_dictionary']);
        }

        if (!empty($config)) {
            self::saveConfigurationArray($config);
        }
    }

    public static function saveConfigurationArray($array)
    {
        if (\Shop::isFeatureActive() && \Shop::getContextShopGroup()->id === null) {
            foreach ($array as $k => $v) {
                \Configuration::updateGlobalValue($k, $v);
            }
        } else {
            foreach ($array as $k => $v) {
                \Configuration::updateValue($k, $v);
            }
        }
    }

    /**
     * @return bool
     */
    public static function automaticTranslationTranslateAll()
    {
        return self::getValue('dingedi_automatic_translation_translate_all', '1') === "1";
    }

    /**
     * @return array|bool
     */
    public static function automaticTranslationGetFields($tableName)
    {
        $tables = json_decode(self::getValue('dingedi_automatic_translation_translate_tables', '[]'), JSON_OBJECT_AS_ARRAY);

        if (!isset($tables[$tableName])) {
            return false;
        }

        $fields = $tables[$tableName];

        if (empty($fields)) {
            return true;
        }

        return $fields;
    }

    /**
     * Save apikeys
     */
    public static function saveApiKeys()
    {
        $data = \Tools::getValue('translation_data');

        if (\Dingedi\PsTools\DgTools::hasParameters($data, array('apiKeys', 'defaultProvider', 'microsoftServer', 'microsoftLocation', 'deeplPlan', 'deeplFormality'))) {
            $apikeys = $data['apiKeys'];
            $provider = (string)$data['defaultProvider'];
            $microsoftServer = (string)$data['microsoftServer'];
            $microsoftLocation = (string)$data['microsoftLocation'];
            $deeplPlan = (string)$data['deeplPlan'];
            $deeplFormality = (string)$data['deeplFormality'];

            $config = array(
                'dingedi_provider_microsoft_location' => $microsoftLocation,
                'dingedi_provider_microsoft_server'   => $microsoftServer,
                'dingedi_provider_deepl_plan'         => $deeplPlan,
                'dingedi_provider_deepl_formality'    => $deeplFormality,
                'dingedi_provider_name'               => $provider
            );

            foreach ($apikeys as $apikey) {
                $config['dingedi_provider_' . (string)$apikey['key']] = (string)$apikey['api_key'];
            }

            self::saveConfigurationArray($config);
        }
    }

    /**
     * @return bool
     */
    public static function install()
    {
        \Configuration::updateValue('dingedi_smart_dictionary', self::getValue('dingedi_smart_dictionary', '[]'));
        \Configuration::updateValue('dingedi_translation_filter', self::getValue('dingedi_translation_filter', '2'));
        \Configuration::updateValue('dingedi_secret_key', sha1(uniqid(rand(), true)) . rand());
        \Configuration::updateValue('dingedi_per_request', self::getValue('dingedi_per_request', '10'));
        \Configuration::updateValue('dingedi_resume_tr', self::getValue('dingedi_resume_tr', 'false'));
        \Configuration::updateValue('dingedi_default_lang', self::getValue('dingedi_default_lang', (int)self::getValue('PS_LANG_DEFAULT')));
        \Configuration::updateValue('dingedi_provider_deepl_plan', self::getValue('dingedi_provider_deepl_plan', 'api-free'));
        \Configuration::updateValue('dingedi_provider_deepl_formality', self::getValue('dingedi_provider_deepl_formality', 'default'));
        \Configuration::updateValue('dingedi_provider_microsoft_server', self::getValue('dingedi_provider_microsoft_server', 'api'));
        \Configuration::updateValue('dingedi_provider_microsoft_location', self::getValue('dingedi_provider_microsoft_location', '-'));

        \Configuration::updateValue('dingedi_automatic_translation', self::getValue('dingedi_automatic_translation', 0));
        \Configuration::updateValue('dingedi_translation_modal_enabled', self::getValue('dingedi_translation_modal_enabled', 1));
        \Configuration::updateValue('dingedi_translation_fields_enabled', self::getValue('dingedi_translation_fields_enabled', 1));
        \Configuration::updateValue('dingedi_translation_fields_always_enabled', self::getValue('dingedi_translation_fields_always_enabled', 0));
        \Configuration::updateValue('dingedi_automatic_translation_translate_all', self::getValue('dingedi_automatic_translation_translate_all', "1"));
        \Configuration::updateValue('dingedi_automatic_translation_translate_tables', self::getValue('dingedi_automatic_translation_translate_tables', '[]'));
        \Configuration::updateValue('dingedi_automatic_translation_id_lang_from', self::getValue('dingedi_automatic_translation_id_lang_from', self::getDefaultLangId()));
        \Configuration::updateValue('dingedi_automatic_translation_ids_langs_to', self::getValue('dingedi_automatic_translation_ids_langs_to', ''));

        $providers = self::getProvidersList();
        foreach ($providers as $provider) {
            \Configuration::updateValue('dingedi_provider_' . $provider->key, self::getValue('dingedi_provider_' . $provider->key, ''));
        }

        \Configuration::updateValue('dingedi_provider_name', self::getValue('dingedi_provider_name', $providers[0]->key));

        \Configuration::updateValue('dingedi_exclude', self::getValue('dingedi_exclude', 'true'));
        \Configuration::updateValue('dingedi_excluded', self::getValue('dingedi_excluded', implode(',', self::getShopManufacturers())));

        \Dingedi\PsTranslationsApi\DgSameTranslations::install();

        return true;
    }

    /**
     * @return array
     */
    public static function getShopManufacturers()
    {
        $manufacturers = array_map(function ($manufacturer) {
            return $manufacturer['name'];
        }, \Manufacturer::getManufacturers());

        return array_values(array_unique($manufacturers));
    }

    /**
     * @param string $module_name
     * @return bool
     */
    public static function uninstall($module_name)
    {
        if (self::isOtherTranslationsModuleInstalled($module_name)) {
            return true;
        }

        \Configuration::deleteByName('dingedi_smart_dictionary');
        \Configuration::deleteByName('dingedi_translation_filter');
        \Configuration::deleteByName('dingedi_secret_key');
        \Configuration::deleteByName('dingedi_per_request');
        \Configuration::deleteByName('dingedi_resume_tr');
        \Configuration::deleteByName('dingedi_default_lang');
        \Configuration::deleteByName('dingedi_provider_deepl_plan');
        \Configuration::deleteByName('dingedi_provider_deepl_formality');
        \Configuration::deleteByName('dingedi_provider_microsoft_server');
        \Configuration::deleteByName('dingedi_provider_microsoft_location');

        $providers = self::getProvidersList();
        foreach ($providers as $provider) {
            \Configuration::deleteByName('dingedi_provider_' . $provider->key);
        }

        \Configuration::deleteByName('dingedi_provider_name');

        \Configuration::deleteByName('dingedi_exclude');
        \Configuration::deleteByName('dingedi_excluded');

        \Configuration::deleteByName('dingedi_automatic_translation');
        \Configuration::deleteByName('dingedi_translation_modal_enabled');
        \Configuration::deleteByName('dingedi_translation_fields_enabled');
        \Configuration::deleteByName('dingedi_translation_fields_always_enabled');
        \Configuration::deleteByName('dingedi_automatic_translation_translate_all');
        \Configuration::deleteByName('dingedi_automatic_translation_translate_tables');
        \Configuration::deleteByName('dingedi_automatic_translation_id_lang_from');
        \Configuration::deleteByName('dingedi_automatic_translation_ids_langs_to');

        \Dingedi\PsTranslationsApi\DgSameTranslations::uninstall();

        return true;
    }

    /**
     * @param string $module_name
     * @return bool
     */
    public static function isOtherTranslationsModuleInstalled($module_name)
    {
        try {
            $modulesList = array_map(function ($module) {
                return $module['name'];
            }, \Module::getModulesInstalled());

            if (!array_intersect(array_diff($modulesList, array($module_name)), self::$modulesName)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public static function getTranslationsConfiguration()
    {
        return array(
            'default'                           => self::getProvider(),
            'microsoft'                         => array(
                'microsoftServer'   => self::getValue('dingedi_provider_microsoft_server'),
                'microsoftLocation' => self::getValue('dingedi_provider_microsoft_location'),
            ),
            'deepl'                             => array(
                'deeplPlan'      => self::getValue('dingedi_provider_deepl_plan'),
                'deeplFormality' => self::getValue('dingedi_provider_deepl_formality'),
            ),
            'providers'                         => self::getProvidersList(),
            'manufacturers'                     => self::getShopManufacturers(),
            'exclude'                           => self::getExcludeStatut(),
            'excluded'                          => self::getExcludedWords(),
            'per_request'                       => self::getPerRequest(),
            'resume_tr'                         => self::getResumeTr(),
            'translation_fields_enabled'        => self::getTranslationFieldsEnabled(),
            'translation_fields_always_enabled' => self::getTranslationFieldsAlwaysEnabled(),
            'translation_modal_enabled'         => self::getTranslationModalEnabled(),
            'translation_filter'                => self::getTranslationFilter(),
            'automatic_translation'             => self::getAutomaticTranslation(),
            'smart_dictionary'                  => self::getSmartDictionary()
        );
    }

    /**
     * @return bool
     */
    public static function getExcludeStatut()
    {
        return self::getValue('dingedi_exclude') === 'true';
    }

    public static function getSmartDictionary()
    {
        $smartDictionary = json_decode(self::getValue('dingedi_smart_dictionary', '[]'), true);

        if (!is_array($smartDictionary)) {
            $smartDictionary = [];
        }

        return $smartDictionary;
    }

    public static function getAutomaticTranslation()
    {
        return array(
            'enabled'       => (bool)self::getValue('dingedi_automatic_translation', 0),
            'translate_all' => self::getValue('dingedi_automatic_translation_translate_all', "1") === "1",
            'tables'        => json_decode(self::getValue('dingedi_automatic_translation_translate_tables', '[]')),
            'id_lang_from'  => (int)self::getValue('dingedi_automatic_translation_id_lang_from', self::getDefaultLangId()),
            'ids_langs_to'  => explode(',', self::getValue('dingedi_automatic_translation_ids_langs_to', ''))
        );
    }

    /**
     * @return string[]|string
     */
    public static function getExcludedWords()
    {
        $excluded = self::getValue('dingedi_excluded', '');

        $excluded = array_values(array_unique(explode(',', $excluded)));

        return (!empty($excluded)) ? $excluded : '';
    }

    /**
     * @return int
     */
    public static function getPerRequest()
    {
        $per_request = (int)self::getValue('dingedi_per_request');

        if ($per_request < 1) {
            $per_request = 1;
        }

        return $per_request;
    }

    public static function getResumeTr()
    {
        $resumeTr = self::getValue('dingedi_resume_tr');

        if ($resumeTr === false) {
            return false;
        }

        return json_decode($resumeTr);
    }

    public static function getTranslationModalEnabled()
    {
        return (int)self::getValue('dingedi_translation_modal_enabled');
    }

    public static function getTranslationFieldsEnabled()
    {
        return (int)self::getValue('dingedi_translation_fields_enabled');
    }

    public static function getTranslationFieldsAlwaysEnabled()
    {
        return (int)self::getValue('dingedi_translation_fields_always_enabled');
    }

    public static function getTranslationFilter()
    {
        return (int)self::getValue('dingedi_translation_filter');
    }

    /**
     * @return array
     * @deprecated since 4.4.69
     */
    public static function getRecommandations()
    {
        if (!defined('PHP_MAJOR_VERSION')) {
            $phpVersion = explode('.', PHP_VERSION);
            define('PHP_MAJOR_VERSION', $phpVersion[0]);
        }

        $phpVersionSatisfying = true;
        $missingRequirements = 0;
        $maxExecutionTime = @ini_get('max_execution_time');
        $maxExecutionTimeSatisfying = true;
        $memoryLimit = @ini_get('memory_limit');
        $memoryLimitSatisfying = true;

        if ((int)$maxExecutionTime < 180 && (int)$maxExecutionTime !== 0) {
            $maxExecutionTimeSatisfying = false;
            $missingRequirements++;
        }

        if ((int)$memoryLimit < 256 && (int)$memoryLimit !== -1) {
            $memoryLimitSatisfying = false;
            $missingRequirements++;
        }

        if (PHP_MAJOR_VERSION < 7) {
            $phpVersionSatisfying = false;
            $missingRequirements++;
        }

        return array(
            'total'           => $missingRequirements,
            'recommandations' => array(
                array('name' => 'max_execution_time', 'satisfying' => $maxExecutionTimeSatisfying, 'value' => $maxExecutionTime, 'recommanded' => '180'),
                array('name' => 'memory_limit', 'satisfying' => $memoryLimitSatisfying, 'value' => $memoryLimit, 'recommanded' => '256'),
                array('name' => 'php', 'satisfying' => $phpVersionSatisfying, 'value' => PHP_VERSION, 'recommanded' => 'PHP7+')
            )
        );
    }

    public static function getShopConfig()
    {
        return array(
            'PS_REWRITING_SETTINGS'                => self::getValue('PS_REWRITING_SETTINGS') == "1",
            'PS_ALLOW_ACCENTED_CHARS_URL'          => self::getValue('PS_ALLOW_ACCENTED_CHARS_URL') == "1",
            'HAS_LANGUAGES_REQUIRE_ACCENTED_CHARS' => !empty(self::getLanguagesRequireAccentedCharsUrl()),
            'LANGUAGES_REQUIRE_ACCENTED_CHARS'     => self::getLanguagesRequireAccentedCharsUrl()
        );
    }

    public static function getLanguagesRequireAccentedCharsUrl()
    {
        $default_list = array('el', 'zh', 'tw');

        $languages = array();

        foreach (\Language::getLanguages(false) as $language) {
            $name = trim(explode('(', $language['name'])[0]);

            $isNonLatin = \Tools::strlen(\Tools::link_rewrite($name)) !== \Tools::strlen($name);

            if ($isNonLatin || in_array($language['iso_code'], $default_list)) {
                $languages[] = $language;
            }
        }

        return $languages;
    }

    /**
     * @param int $id_lang Language Id
     *
     * @return array
     * @throws \Exception
     */
    public static function getLanguage($id_lang)
    {
        $language = \Language::getLanguage($id_lang);

        if ($language === false) {
            throw new \Exception('Invalid language ID: ' . $id_lang);
        }

        return $language;
    }
}
