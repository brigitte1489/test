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
 * @copyright Copyright 2020 Â© Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dgtranslationall/classes/polyfill.php';
require_once _PS_MODULE_DIR_ . 'dgtranslationall/vendor/autoload.php';

class Dgtranslationall extends Module
{
    /** @var array $dgModuleConfig */
    private $dgModuleConfig;

    /** @var string $page */
    private $page;

    public function __construct()
    {
        $this->name = 'dgtranslationall';
        $this->tab = 'i18n_localization';
        $this->version = '4.7.69';
        $this->author = 'Dingedi';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->module_key = 'ef7f1e2fa626e241965461a073a1a77e';
        $this->displayName = $this->l('Translate all - Free and unlimited translation');
        $this->description = $this->l('Translate your entire shop automatically! With more than 3000 shops translated in more than 110 languages since its creation, Translation of all is the best module to translate your shop.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->page = empty(Tools::getValue('dgtranslationallpage')) ? 'index' : Tools::getValue('dgtranslationallpage');

        if (Tools::getValue('ajax') === '1' && Tools::getValue('configure') === $this->name) {
            $action = Tools::getValue('action');

            if (str_starts_with($action, 'Content')) {
                $this->initContent();
            } else if (str_starts_with($action, 'Modules')) {
                $this->initModules();
            } else if (str_starts_with($action, 'Themes') || str_starts_with($action, 'Mails')) {
                $this->initThemesAndMails();
            }
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && \Dingedi\PsTranslationsApi\DgTranslationTools::install()
            && $this->registerHook('actionObjectUpdateBefore')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && \Dingedi\PsTranslationsApi\DgTranslationTools::uninstall($this->name)
            && $this->unregisterHook('actionObjectUpdateBefore')
            && $this->unregisterHook('displayBackOfficeHeader');
    }

    public function getContent()
    {
        $this->context->smarty->assign([
            'dgtranslationall_page'         => $this->page,
            'dgtranslationall_default_link' => Tools::getHttpHost(true) . __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_) . '/index.php?controller=AdminModules&configure=dgtranslationall&token=' . Tools::getValue('token'),
            'dgtranslationall_config'       => $this->dgModuleConfig,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }



//STARTdgcontenttranslation

    public function initContent($loadTableOnly = false)
    {
        if ($loadTableOnly === false) {
            $this->dgModuleConfig = array(
                'is_ps_16'                       => \Dingedi\PsTools\DgShopInfos::isPrestaShop16(),
                'add_language_link'              => \Dingedi\PsTools\DgTools::getAdminLink('AdminLocalization'),
                'link_admin_db_backup'           => \Dingedi\PsTools\DgTools::getAdminLink('AdminBackup'),
                'link_admin_update_url_settings' => \Dingedi\PsTools\DgTools::getAdminLink('AdminMeta'),
                'cron_cli_command'               => $this->getCronCliCommand(),
                'default_lang'                   => \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId(),
                'module_name'                    => $this->name,
            );
        }

        require_once _PS_MODULE_DIR_ . $this->name . '/classes/Tables/TablesAdapterList.php';

        TablesAdapterList::register();
    }

    public function ajaxProcessContentSearchWords()
    {
        $data = $this->checkAndGetParams(['search_query']);

        $searchQuery = (string)$data['search_query'];
        $searchTable = (string)$data['search_table'];
        $searchIdLang = (string)$data['search_id_lang'];

        if (trim($searchQuery) === "") {
            $this->jsonResponse(array(
                'success' => true,
                'results' => array()
            ));
        }

        $tables = $this->getContentTables();
        $tables = array_map(function ($elem) {
            return $elem['tables'];
        }, $tables);

        $tablesArray = array();

        foreach ($tables as $table) {
            $tablesArray = array_merge($tablesArray, $table);
        }

        $results = array();

        $searchRegex = '/\b' . preg_quote($searchQuery) . '\ ?\b/um';

        if (Tools::version_compare(\Db::getInstance()->getVersion(), '8.0', '>=')) {
            $searchRegexMysql = '\\\\b' . str_replace(
                    array("'", '"'),
                    array("\'", '\"'),
                    preg_quote($searchQuery)
                ) . '\\\\b';
        } else {
            $searchRegexMysql = '[[:<:]]' . str_replace(
                    array("'", '"'),
                    array("\'", '\"'),
                    preg_quote($searchQuery)
                );

            if (!str_contains($searchQuery, '"') && !str_contains($searchQuery, "'")) {
                $searchRegexMysql .= "[[:>:]]";
            }
        }

        $whereRegexp = " RLIKE '" . $searchRegexMysql . "' ";

        /** @var \Dingedi\TablesTranslation\DgTableTranslatable16 $table */
        foreach ($tablesArray as $table) {
            if ($searchTable !== '' && $searchTable !== $table->getTableName(false)) {
                continue;
            }

            $fields = array_merge($table->getPrimaryKeys(), $table->getFields());
            $where = implode(" " . $whereRegexp . "  OR ", $table->getFields()) . " " . $whereRegexp . " ";

            if ($searchIdLang) {
                $where = " ( id_lang = " . $searchIdLang . " ) AND ( " . $where . " ) ";
            }

            $query = "SELECT " . \pSQL(implode(',', $fields)) . " FROM " . \pSQL($table->getTableName(true)) . " WHERE " . $where;

            if (in_array('id_shop', $fields)) {
                $query .= " " . Shop::addSqlRestriction();
            }
            $tableResults = \Db::getInstance()->executeS($query);

            foreach ($tableResults as $item) {
                if (\Language::getLanguage((int)$item['id_lang']) === false) {
                    continue;
                }

                foreach ($item as $field => $value) {
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_COMPAT, 'UTF-8');

                    $marked = $value;

                    if ($marked === strip_tags($marked)) {
                        if (preg_match($searchRegex, $marked, $matches)) {
                            foreach (array_unique($matches) as $match) {
                                $marked = preg_replace($searchRegex, '<mark>' . $searchQuery . '</mark>', $marked);
                            }
                        }
                    } else {
                        $dgHtmlParser = new \Dingedi\PsTranslationsApi\DgHTMLParser($marked);

                        foreach ($dgHtmlParser->getTextNodes() as $node) {
                            $node->nodeValue = str_replace(array('<mark>', '</mark>'), array('', ''), $node->nodeValue);
                            $node->nodeValue = preg_replace($searchRegex, '<mark>' . $searchQuery . '</mark>', $node->nodeValue);
                        }

                        $marked = $dgHtmlParser->getHTMLOutput();
                    }

                    if (trim($marked) !== trim($value) && (str_replace("\n", '', strip_tags($marked, '<mark>')) !== str_replace("\n", '', strip_tags($value, '<mark>')))) {
                        $result = array(
                            'id_lang' => $item['id_lang'],
                            'table'   => $table->getTableName(false),
                            'field'   => $field,
                            'id'      => $item[$table->getPrimaryKey()],
                            'text'    => strip_tags($marked, '<mark>')
                        );

                        $results[] = $result;
                    }
                }
            }
        }

        $this->jsonResponse(array(
            'success' => true,
            'results' => array_values(array_unique($results, SORT_REGULAR))
        ));
    }

    public function ajaxProcessContentReplaceWords()
    {
        $data = $this->checkAndGetParams(array('rows', 'search_query', 'replace_query'));

        $rows = (array)$data['rows'];
        $searchQuery = (string)$data['search_query'];
        $replaceQuery = (string)$data['replace_query'];

        $regex = '/\b' . preg_quote($searchQuery) . '\b/um';

        foreach ($rows as $row) {
            /** @var \Dingedi\TablesTranslation\DgTableTranslatable16 $table */
            $table = $this->getContentTable($row['table']);

            $selectQuery = new \DbQuery();
            $selectQuery->select($row['field'])
                ->from($table->getTableName(false))
                ->where($table->getPrimaryKey() . ' = ' . $row['id'])
                ->where('id_lang = ' . $row['id_lang']);

            $query = $selectQuery->build();

            if ($table->isMultiShop()) {
                $query .= ' ' . \Shop::addSqlRestriction();
            }

            $result = \Db::getInstance()->executeS($query)[0];

            if (!empty($result)) {
                $value = $result[$row['field']];

                if ($value === strip_tags($value)) {
                    $value = \pSQL(preg_replace($regex, $replaceQuery, $value));

                    if (isset($table->getFieldsRewrite()[$row['field']])) {
                        $value = \Tools::link_rewrite($value);
                    }
                } else {
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_COMPAT, 'UTF-8');

                    $dgHtmlParser = new \Dingedi\PsTranslationsApi\DgHTMLParser($value);

                    foreach ($dgHtmlParser->getTextNodes() as $node) {
                        $node->nodeValue = preg_replace($regex, $replaceQuery, $node->nodeValue);
                    }

                    $value = \pSQL($dgHtmlParser->getHTMLOutput(), true);
                }

                \Db::getInstance()->update($table->getTableName(false), array($row['field'] => $value), $table->getPrimaryKey() . ' = ' . $row['id'] . ' AND id_lang = ' . $row['id_lang'] . ' ' . ($table->isMultiShop() ? Shop::addSqlRestriction() : ''));
            }
        }

        $this->jsonResponse(array(
            'success' => true
        ));
    }

    public function ajaxProcessGlobalSetDefaultLang()
    {
        $data = $this->checkAndGetParams(array(
            'id_lang'
        ));

        Configuration::updateValue('dingedi_default_lang', (int)$data['id_lang']);

        $this->jsonResponse(array(
            'success' => 1
        ));
    }

    public function ajaxProcessGlobalSaveSettings()
    {
        \Dingedi\PsTranslationsApi\DgTranslationTools::saveSettings();

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('Settings successfully saved')
        ));
    }

    public function ajaxProcessGlobalSaveApiKeys()
    {
        \Dingedi\PsTranslationsApi\DgTranslationTools::saveApiKeys();
        \Dingedi\PsTranslationsApi\DgTranslationTools::saveSettings();

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('API keys successfully saved')
        ));
    }

    /**
     * @throws Exception
     */
    public function ajaxProcessContentGetPercentageTranslation()
    {
        $data = $this->checkAndGetParams(array(
            'name', 'id_lang_to'
        ));

        $translation_data = Tools::getValue('translation_data');
        $current = isset($translation_data['current']) ? (int)$translation_data['current'] : false;

        $tableName = (string)$data['name'];
        $id_lang_to = (int)$data['id_lang_to'];

        $dgTableTranslatable = $this->getContentTable($tableName, false);
        $dgTableCalculateMissingTranslations = new \Dingedi\TablesTranslation\DgTableCalculateMissingTranslations($dgTableTranslatable);

        $this->jsonResponse($dgTableCalculateMissingTranslations->getTranslationsPercent(Language::getLanguage($id_lang_to), $current));
    }

    private function getContentModuleBaseData()
    {
        return array(
            'moduleConfig' => array_merge(
                $this->dgModuleConfig,
                array('languages' => $this->getLanguages()),
                array('translations' => $this->getContentModuleTranslations()),
                array('translationsProviders' => \Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationsConfiguration()),
                array('shopConfig' => \Dingedi\PsTranslationsApi\DgTranslationTools::getShopConfig())
            )
        );
    }

    public function ajaxProcessContentGetData()
    {
        $this->jsonResponse(array_merge(
            $this->getContentModuleBaseData(),
            array('translatable' => $this->getContentTables())
        ));
    }

    /**
     * @throws Exception
     */
    public function ajaxProcessContentGetModalData()
    {
        $data = $this->checkAndGetParams(array('tableName'));

        $this->jsonResponse(array_merge(
            $this->getContentModuleBaseData(),
            array('table' => $this->getContentTable($data['tableName']))
        ));
    }

    public function ajaxProcessContentTranslate()
    {
        $data = $this->checkAndGetParams(array(
            'name', 'id_lang_from', 'id_lang_to', 'latin', 'overwrite', 'requests', 'current'
        ));

        try {
            $this->jsonResponse($this->translateContentTable(
                (string)$data['name'],
                (int)$data['id_lang_from'],
                (int)$data['id_lang_to'],
                (int)$data['latin'],
                ($data['overwrite'] === 'true'),
                ((int)$data['requests'] > 1) ? (int)$data['current'] : 1
            ));
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function getContentModuleTranslations()
    {
        return array(
            'api_keys'                                                                                                                                                                          => array(
                'microsoftProvider' => array(
                    array('label' => $this->l('Global'), 'value' => 'api'),
                    array('label' => $this->l('North America'), 'value' => 'api-nam'),
                    array('label' => $this->l('Europe'), 'value' => 'api-eur'),
                    array('label' => $this->l('Asia Pacific'), 'value' => 'api-apc'),
                ),
            ),
            'Setup wizard'                                                                                                                                                                      => $this->l('Setup wizard'),
            'Next'                                                                                                                                                                              => $this->l('Next'),
            'Previous'                                                                                                                                                                          => $this->l('Previous'),
            'Message from %s'                                                                                                                                                                   => $this->l('Message from %s'),
            'Free monthly quota of %s characters'                                                                                                                                               => $this->l('Free monthly quota of %s characters'),
            'Pricing options'                                                                                                                                                                   => $this->l('Pricing options'),
            'Obtain an API key'                                                                                                                                                                 => $this->l('Obtain an API key'),
            'offers a trial offer: %s credit for %s months'                                                                                                                                     => $this->l('offers a trial offer: %s credit for %s months'),
            'Finish'                                                                                                                                                                            => $this->l('Finish'),
            'Message from'                                                                                                                                                                      => $this->l('Message from'),
            'You must have a Google Cloud account before starting.'                                                                                                                             => $this->l('You must have a Google Cloud account before starting.'),
            'Click here'                                                                                                                                                                        => $this->l('Click here'),
            'to create one.'                                                                                                                                                                    => $this->l('to create one.'),
            'Go to the Google Cloud dashboard'                                                                                                                                                  => $this->l('Go to the Google Cloud dashboard'),
            'Click on "Select a project"'                                                                                                                                                       => $this->l('Click on "Select a project"'),
            'Click on "New project"'                                                                                                                                                            => $this->l('Click on "New project"'),
            'Create a project'                                                                                                                                                                  => $this->l('Create a project'),
            'Select the project'                                                                                                                                                                => $this->l('Select the project'),
            'Click on "APIs & Services"'                                                                                                                                                        => $this->l('Click on "APIs & Services"'),
            'Click on "Enable APIs and Services"'                                                                                                                                               => $this->l('Click on "Enable APIs and Services"'),
            'Search and select "Cloud Translation API"'                                                                                                                                         => $this->l('Search and select "Cloud Translation API"'),
            'Enable "Cloud Translation API'                                                                                                                                                     => $this->l('Enable "Cloud Translation API'),
            'You can skip these two steps if you already have a billing account in your Google Cloud account.'                                                                                  => $this->l('You can skip these two steps if you already have a billing account in your Google Cloud account.'),
            'Create an API key'                                                                                                                                                                 => $this->l('Create an API key'),
            'Copy and enter it in the module'                                                                                                                                                   => $this->l('Copy and enter it in the module'),
            'Click to view in full screen'                                                                                                                                                      => $this->l('Click to view in full screen'),
            'You must have a Microsoft Azure account before starting.'                                                                                                                          => $this->l('You must have a Microsoft Azure account before starting.'),
            'Go to the Microsoft Azure dashboard'                                                                                                                                               => $this->l('Go to the Microsoft Azure dashboard'),
            'Click on "Create a ressource"'                                                                                                                                                     => $this->l('Click on "Create a ressource"'),
            'Search "Translator text"'                                                                                                                                                          => $this->l('Search "Translator text"'),
            'Click on "Create"'                                                                                                                                                                 => $this->l('Click on "Create"'),
            'Fill in the required fields and click on "Review + create"'                                                                                                                        => $this->l('Fill in the required fields and click on "Review + create"'),
            'Go to ressource'                                                                                                                                                                   => $this->l('Go to ressource'),
            'Click on "Keys and Endpoint"'                                                                                                                                                      => $this->l('Click on "Keys and Endpoint"'),
            'Copy KEY 1 enter it in the module'                                                                                                                                                 => $this->l('Copy KEY 1 enter it in the module'),
            'Configure API keys'                                                                                                                                                                => $this->l('Configure API keys'),
            'Apply for all shops'                                                                                                                                                               => $this->l('Apply for all shops'),
            'API Key'                                                                                                                                                                           => $this->l('API Key'),
            'Save'                                                                                                                                                                              => $this->l('Save'),
            'Server'                                                                                                                                                                            => $this->l('Server'),
            'Location'                                                                                                                                                                          => $this->l('Location'),
            'Show'                                                                                                                                                                              => $this->l('Show'),
            'Hide'                                                                                                                                                                              => $this->l('Hide'),
            'Plan'                                                                                                                                                                              => $this->l('Plan'),
            'offers a free offer with a quota of %s characters per month'                                                                                                                       => $this->l('offers a free offer with a quota of %s characters per month'),
            'Add a language'                                                                                                                                                                    => $this->l('Add a language'),
            'API Keys'                                                                                                                                                                          => $this->l('API Keys'),
            'Excluded words'                                                                                                                                                                    => $this->l('Excluded words'),
            'Exclude words from translation'                                                                                                                                                    => $this->l('Exclude words from translation'),
            'The words you add will not be translated. For example, you can add brand names.'                                                                                                   => $this->l('The words you add will not be translated. For example, you can add brand names.'),
            'Performance'                                                                                                                                                                       => $this->l('Performance'),
            'Elements per query'                                                                                                                                                                => $this->l('Elements per query'),
            'Tools'                                                                                                                                                                             => $this->l('Tools'),
            'Settings'                                                                                                                                                                          => $this->l('Settings'),
            'Content translation'                                                                                                                                                               => $this->l('Content translation'),
            'Translation'                                                                                                                                                                       => $this->l('Translation'),
            'Translate active or inactive elements'                                                                                                                                             => $this->l('Translate active or inactive elements'),
            'If you choose active, only the active elements (your products for example) will be translated.'                                                                                    => $this->l('If you choose active, only the active elements (your products for example) will be translated.'),
            'All'                                                                                                                                                                               => $this->l('All'),
            'Inactive'                                                                                                                                                                          => $this->l('Inactive'),
            'Active'                                                                                                                                                                            => $this->l('Active'),
            'You must refresh your browser page after saving this setting.'                                                                                                                     => $this->l('You must refresh your browser page after saving this setting.'),
            'Exclude all brands'                                                                                                                                                                => $this->l('Exclude all brands'),
            'Add a word to exclude from the translation'                                                                                                                                        => $this->l('Add a word to exclude from the translation'),
            'Do not forget to make a backup of your database before starting the translation!'                                                                                                  => $this->l('Do not forget to make a backup of your database before starting the translation!'),
            'Select all'                                                                                                                                                                        => $this->l('Select all'),
            'items'                                                                                                                                                                             => $this->l('items'),
            'Fields to translate'                                                                                                                                                               => $this->l('Fields to translate'),
            'selected fields'                                                                                                                                                                   => $this->l('selected fields'),
            'new'                                                                                                                                                                               => $this->l('new'),
            'Automatic translation'                                                                                                                                                             => $this->l('Automatic translation'),
            'Enabled'                                                                                                                                                                           => $this->l('Enabled'),
            'Disabled'                                                                                                                                                                          => $this->l('Disabled'),
            'No translation service is configured. Please configure one in order to be able to launch translations.'                                                                            => $this->l('No translation service is configured. Please configure one in order to be able to launch translations.'),
            'Smart dictionary'                                                                                                                                                                  => $this->l('Smart dictionary'),
            'Add'                                                                                                                                                                               => $this->l('Add'),
            'Add a word'                                                                                                                                                                        => $this->l('Add a word'),
            'If the translation of certain words do not fit, you can define your own translations here.'                                                                                        => $this->l('If the translation of certain words do not fit, you can define your own translations here.'),
            'Add the word whose translation you want to change first, then add the translations you want.'                                                                                      => $this->l('Add the word whose translation you want to change first, then add the translations you want.'),
            'Find and replace'                                                                                                                                                                  => $this->l('Find and replace'),
            'Find the text to replace'                                                                                                                                                          => $this->l('Find the text to replace'),
            'Search'                                                                                                                                                                            => $this->l('Search'),
            'ID'                                                                                                                                                                                => $this->l('ID'),
            'Type'                                                                                                                                                                              => $this->l('Type'),
            'Language'                                                                                                                                                                          => $this->l('Language'),
            'Replace'                                                                                                                                                                           => $this->l('Replace'),
            'Replace by'                                                                                                                                                                        => $this->l('Replace by'),
            'No result'                                                                                                                                                                         => $this->l('No result'),
            'Enable automatic translation of your content. Example: when you modify the description of a product, the descriptions of the selected languages will be translated automatically.' => $this->l('Enable automatic translation of your content. Example: when you modify the description of a product, the descriptions of the selected languages will be translated automatically.'),
            'Find and replace words found in the different contents of your shop'                                                                                                               => $this->l('Find and replace words found in the different contents of your shop'),
            'Modules translation'                                                                                                                                                               => $this->l('Modules translation'),
            'Themes and emails translation'                                                                                                                                                     => $this->l('Themes and emails translation'),
            'This feature is available in the PRO version'                                                                                                                                      => $this->l('This feature is available in the PRO version'),
            'See the module'                                                                                                                                                                    => $this->l('See the module'),
            'Any question ?'                                                                                                                                                                    => $this->l('Any question ?'),
            'contact us'                                                                                                                                                                        => $this->l('contact us'),
            'Service to use'                                                                                                                                                                    => $this->l('Service to use'),
            'Video tutorial'                                                                                                                                                                    => $this->l('Video tutorial'),
            'Configuration'                                                                                                                                                                     => $this->l('Configuration'),
            'Supported languages'                                                                                                                                                               => $this->l('Supported languages'),
            'List of ISO codes accepted for translation'                                                                                                                                        => $this->l('List of ISO codes accepted for translation'),
            'PrestaShop Addons order ID'                                                                                                                                                        => $this->l('PrestaShop Addons order ID'),
            'We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.'                                              => $this->l('We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.'),
            'Leave a review on our module and get better translation speed for free.'                                                                                                           => $this->l('Leave a review on our module and get better translation speed for free.'),
            'Leave a review'                                                                                                                                                                    => $this->l('Leave a review'),
            'Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'                                                                                 => $this->l('Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'),
            'Some installed languages use non-Latin characters:'                                                                                                                                => $this->l('Some installed languages use non-Latin characters:'),
            'Change settings'                                                                                                                                                                   => $this->l('Change settings'),
            'Please configure the selected translation service'                                                                                                                                 => $this->l('Please configure the selected translation service'),
            'Not available with'                                                                                                                                                                => $this->l('Not available with'),
            'Translation speed'                                                                                                                                                                 => $this->l('Translation speed'),
            'The value corresponds to the number of items (e.g. products) translated in each query.'                                                                                            => $this->l('The value corresponds to the number of items (e.g. products) translated in each query.'),
            'Very low'                                                                                                                                                                          => $this->l('Very low'),
            'Low'                                                                                                                                                                               => $this->l('Low'),
            'Normal'                                                                                                                                                                            => $this->l('Normal'),
            'High'                                                                                                                                                                              => $this->l('High'),
            'Custom'                                                                                                                                                                            => $this->l('Custom'),
            'Help'                                                                                                                                                                              => $this->l('Help'),
            'Close'                                                                                                                                                                             => $this->l('Close'),
            'Here are several solutions to solve this error:'                                                                                                                                   => $this->l('Here are several solutions to solve this error:'),
            'Reduce the translation speed in the module settings.'                                                                                                                              => $this->l('Reduce the translation speed in the module settings.'),
            'Increase the "max_execution_time" parameter in the php.ini configuration file of your server.'                                                                                     => $this->l('Increase the "max_execution_time" parameter in the php.ini configuration file of your server.'),
            'Contact your server manager to increase the "Timeout" setting of your Apache web server.'                                                                                          => $this->l('Contact your server manager to increase the "Timeout" setting of your Apache web server.'),
            'If the error is still present, please contact us.'                                                                                                                                 => $this->l('If the error is still present, please contact us.'),
            'show details'                                                                                                                                                                      => $this->l('show details'),
            'This word already exists for this language'                                                                                                                                        => $this->l('This word already exists for this language'),
            'Update'                                                                                                                                                                            => $this->l('Update'),
            'Estimated time remaining before the end of the translation.'                                                                                                                       => $this->l('Estimated time remaining before the end of the translation.'),
            'Friendly URLs are disabled, internal links in your content will not be translated.'                                                                                                => $this->l('Friendly URLs are disabled, internal links in your content will not be translated.'),
            'Translate'                                                                                                                                                                         => $this->l('Translate'),
            'Advanced parameters'                                                                                                                                                               => $this->l('Advanced parameters'),
            'Overwrite all translations'                                                                                                                                                        => $this->l('Overwrite all translations'),
            'Latin option (for supported languages)'                                                                                                                                            => $this->l('Latin option (for supported languages)'),
            'My source text is in Latin characters'                                                                                                                                             => $this->l('My source text is in Latin characters'),
            'I want to translate into Latin characters'                                                                                                                                         => $this->l('I want to translate into Latin characters'),
            'The source language is the language you want to translate from'                                                                                                                    => $this->l('The source language is the language you want to translate from'),
            'Languages to translate'                                                                                                                                                            => $this->l('Languages to translate'),
            'The languages to translate are the languages you want to translate, from the source language selected previously'                                                                  => $this->l('The languages to translate are the languages you want to translate, from the source language selected previously'),
            'Source language'                                                                                                                                                                   => $this->l('Source language'),
            'Stop'                                                                                                                                                                              => $this->l('Stop'),
            'Server error'                                                                                                                                                                      => $this->l('Server error'),
            'Access your invoices on PrestaShop Addons'                                                                                                                                         => $this->l('Access your invoices on PrestaShop Addons'),
            'Copy the order ID of the module'                                                                                                                                                   => $this->l('Copy the order ID of the module'),
            'Remember to save changes before translating'                                                                                                                                       => $this->l('Remember to save changes before translating'),
            'Formality'                                                                                                                                                                         => $this->l('Formality'),
            'Sets whether the translated text should lean towards formal or informal language for supported languages'                                                                          => $this->l('Sets whether the translated text should lean towards formal or informal language for supported languages'),
            'Default'                                                                                                                                                                           => $this->l('Default'),
            'Formal'                                                                                                                                                                            => $this->l('Formal'),
            'Informal'                                                                                                                                                                          => $this->l('Informal'),
            'Unselect all'                                                                                                                                                                      => $this->l('Unselect all'),
            'Translate all'                                                                                                                                                                     => $this->l('Translate all'),
            'State'                                                                                                                                                                             => $this->l('State'),
            'Warning, spaces are present which could alter the formatting after the translation'                                                                                                => $this->l('Warning, spaces are present which could alter the formatting after the translation'),
            'Recommended parameters'                                                                                                                                                            => $this->l('Recommended parameters'),
            'No'                                                                                                                                                                                => $this->l('No'),
            'Apply'                                                                                                                                                                             => $this->l('Apply'),
            'Custom value'                                                                                                                                                                      => $this->l('Custom value'),
            'Languages'                                                                                                                                                                         => $this->l('Languages'),
            'Translate button'                                                                                                                                                                  => $this->l('Translate button'),
            'Display a translate button in your back office to translate directly from a content page (product, category, attributes, etc.)'                                                    => $this->l('Display a translate button in your back office to translate directly from a content page (product, category, attributes, etc.)'),
            'Recommended settings'                                                                                                                                                              => $this->l('Recommended settings'),
            'Apply recommended settings'                                                                                                                                                        => $this->l('Apply recommended settings'),
            'Keep current settings'                                                                                                                                                             => $this->l('Keep current settings'),
            'Calculate characters to translate'                                                                                                                                                 => $this->l('Calculate characters to translate'),
            'Translate only internal links'                                                                                                                                                     => $this->l('Translate only internal links'),
            'There are'                                                                                                                                                                         => $this->l('There are'),
            'Characters to translate'                                                                                                                                                           => $this->l('Characters to translate'),
            'The actual number of characters sent to the API may vary.'                                                                                                                         => $this->l('The actual number of characters sent to the API may vary.'),
            'Cron job'                                                                                                                                                                          => $this->l('Cron job'),
            'Run your translations in the background with a cron job '                                                                                                                          => $this->l('Run your translations in the background with a cron job '),
            'Command:'                                                                                                                                                                          => $this->l('Command:'),
            'This table contains a lot of data. To load the display of the translation percentages, please click manually on the button.'                                                       => $this->l('This table contains a lot of data. To load the display of the translation percentages, please click manually on the button.'),
            'Load by clicking'                                                                                                                                                                  => $this->l('Load by clicking'),
            'Unable to calculate the number of characters to translate. Please check that all selected content are loaded.'                                                                     => $this->l('Unable to calculate the number of characters to translate. Please check that all selected content are loaded.'),
        );
    }

    public function getContentTables()
    {
        return array(
            array(
                'group_name' => $this->l('Catalog'),
                'icon'       => 'store',
                'tables'     => $this->filterExistingTables(array(
                    new Product_lang(),
                    new Category_lang(),

                    new Feature_lang(),
                    new Feature_value_lang(),

                    new Attribute_lang(),
                    new Attribute_group_lang(),
                ))
            ),
            array(
                'group_name' => $this->l('Pages'),
                'icon'       => 'desktop_mac',
                'tables'     => $this->filterExistingTables(array(
                    new Cms_lang(),
                    new Cms_category_lang(),
                    new Meta_lang(),
                ))
            ),
            array(
                'group_name' => $this->l('Suppliers'),
                'icon'       => 'account_circle',
                'tables'     => $this->filterExistingTables(array(
                    new Supplier_lang(),
                ))
            ),
            array(
                'group_name' => $this->l('Manufacturers'),
                'icon'       => 'business',
                'tables'     => $this->filterExistingTables(array(
                    new Manufacturer_lang(),
                ))
            ),
            array(
                'group_name' => $this->l('Orders'),
                'icon'       => 'shopping_basket',
                'tables'     => $this->filterExistingTables(array(
                    new Order_message_lang(),
                    new Order_return_state_lang(),
                    new Order_state_lang(),
                    new Supply_order_state_lang(),
                ))
            ),
            array(
                'group_name' => $this->l('Others'),
                'icon'       => 'info',
                'tables'     => $this->filterExistingTables(array(
                    new Attachment_lang(),
                    new Carrier_lang(),
                    new Cart_rule_lang(),
                    new Contact_lang(),
                    new Customization_field_lang(),
                    new Gender_lang(),
                    new Homeslider_slides_lang(),
                    new Image_lang(),
                    new Linksmenutop_lang(),
                    new Profile_lang(),
                    new Quick_access_lang(),
                    new Reassurance_lang(),
                    new Risk_lang(),
                    new Stock_mvt_reason_lang(),
                    new Psreassurance_lang(),
                ))
            ),
        );
    }

    /**
     * @param string $table_name
     * @param int $idLangFrom
     * @param int $idLangTo
     * @param int $latin
     * @param bool $overwrite
     * @param int $paginate
     * @return array
     * @throws Exception
     */
    public function translateContentTable($table_name, $idLangFrom, $idLangTo, $latin, $overwrite, $paginate)
    {
        $dgTableTranslatable = $this->getContentTable($table_name, false);
        $dgTableTranslation = new \Dingedi\TablesTranslation\DgTableTranslation($dgTableTranslatable, $idLangFrom, $idLangTo, $overwrite, $latin);
        $dgTableTranslation->translate($paginate);

        $message = $this->l('Data has been translated');

        if (\Tools::getValue('translate-modal') === 'true') {
            $message .= '. ' . $this->l('Refresh the page to see the translations');
        }

        return ['success' => 1, 'message' => $message];
    }

    /**
     * @param string $table_name
     * @param bool $with_prefix
     * @return \Dingedi\TablesTranslation\AbstractTableAdapter
     * @throws Exception
     */
    public function getContentTable($table_name, $with_prefix = false)
    {
        foreach ($this->getContentTables() as $tablesGroup) {
            /** @var \Dingedi\TablesTranslation\AbstractTableAdapter $table */
            foreach ($tablesGroup['tables'] as $table) {
                if ($table->getTableName($with_prefix) === $table_name) {
                    return $table;
                }
            }
        }

        if (method_exists($this, 'initModules')) {
            $this->initModules(true);

            foreach (\Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->getAdapters() as $table) {
                if ($table->getTableName($with_prefix) === $table_name) {
                    return $table;
                }
            }
        }

        throw new \Exception('This table does not exist');
    }

    public function hookActionObjectUpdateBefore($params)
    {
        $translation_data = Tools::getValue('translation_data');

        $automaticTranslationConfiguration = \Dingedi\PsTranslationsApi\DgTranslationTools::getAutomaticTranslation();

        if ($automaticTranslationConfiguration['enabled'] === false || ($translation_data && array_key_exists('automatic_progress', $translation_data) && $translation_data['automatic_progress'] === true)) {
            return true;
        }

        Configuration::set('dingedi_translation_filter', 2);
        $this->initContent(true);

        $object = $params['object'];

        /** @var \Dingedi\TablesTranslation\AbstractTableAdapter|false $supportModel */
        $supportModel = \Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->supportObjectModel($object);

        if ($supportModel === false || !property_exists($object, 'id')) {
            return true;
        }

        $oldItem = $supportModel->findOneByPrimaryKey($object->id, array('id_lang' => $automaticTranslationConfiguration['id_lang_from']))[0];
        $new = array();
        $old = array();

        foreach ($supportModel->getFields() as $field) {
            if (property_exists($object, $field)) {
                $_field = $object->$field;

                if (is_array($_field)) {
                    $new[$field] = $_field[$automaticTranslationConfiguration['id_lang_from']];
                    $old[$field] = $oldItem[$field];
                }
            }
        }

        list($old, $new) = array_map(function ($array) {
            return array_map(function ($k) {
                return html_entity_decode(str_replace(array("\n", "\r", "\n\r"), array('', '', ''), strip_tags($k)));
            }, $array);
        }, [$old, $new]);

        // get fields with diff
        $updatedFields = array_keys(array_diff_assoc($old, $new));

        $supportItemRewrite = $supportModel->supportedItemRewrite(array_flip($updatedFields));

        $updatedFields = array_diff($updatedFields, array_keys($supportModel->getFieldsRewrite()));

        // if a field that need link regeneration is modified, add the field that need to be regenerated
        if ($supportItemRewrite !== false) {
            $updatedFields[] = array_keys($supportItemRewrite)[0];
        }

        if (!\Dingedi\PsTranslationsApi\DgTranslationTools::automaticTranslationTranslateAll()) {
            $fields = \Dingedi\PsTranslationsApi\DgTranslationTools::automaticTranslationGetFields($supportModel->getTableName(false));

            if ($fields === false) {
                return true;
            }

            if (is_array($fields)) {
                $updatedFields = array_intersect($updatedFields, $fields);
            }
        }

        if (count($updatedFields) === 0) {
            return true;
        }

        foreach ($automaticTranslationConfiguration['ids_langs_to'] as $idLang) {
            try {
                $_POST['translation_data'] = array(
                    'automatic_progress' => true,
                    'selected_fields'    => array_unique($updatedFields),
                    'plage_enabled'      => 'true',
                    'start_id'           => $object->id,
                    'end_id'             => $object->id
                );

                $object->update();

                Configuration::set('dingedi_translation_filter', 2);
                $this->translateContentTable($supportModel->getTableName(false), $automaticTranslationConfiguration['id_lang_from'], (int)$idLang, 0, true, 1);

                $translated = $supportModel->findOneByPrimaryKey($object->id, array('id_lang' => $idLang))[0];

                foreach ($updatedFields as $field) {
                    if (property_exists($object, $field) && isset($translated[$field])) {
                        $object->{$field}[$idLang] = $translated[$field];
                    }
                }

                $object->update();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param array<\Dingedi\TablesTranslation\DgTableTranslatable16> $tables
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function filterExistingTables($tables)
    {
        return array_values(array_filter($tables, function ($i) {
            return $i->isExist() === true;
        }));
    }

    /**
     * @param array $required
     */
    private function checkAndGetParams($required)
    {
        $data = Tools::getValue('translation_data');

        try {
            if (\Dingedi\PsTools\DgTools::hasParameters($data, $required)) {
                return $data;
            }
        } catch (\Dingedi\PsTools\Exception\MissingParametersException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function getCronCliCommand()
    {
        $php = '';
        if (defined('PHP_BINDIR') && is_string(PHP_BINDIR)) {
            $php = PHP_BINDIR . '/';
        }
        $php .= "php";

        $consolePath = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'bin/console';
        $command = $php . ' ' . $consolePath . ' dgtranslationall:translate --from_lang=FROM_LANG --dest_lang=DEST_LANG --tables="TABLES" --overwrite=OVERWRITE';

        return $command;
    }

    /**
     * @param array $data
     * @return void
     */
    private function jsonResponse($data)
    {
        \Dingedi\PsTools\DgTools::jsonResponse($data);
    }

    /**
     * @param array|string $data
     * @return void
     */
    private function jsonError($data)
    {
        if (is_string($data)) {
            $data = array(
                'error'   => true,
                'message' => $data
            );
        }

        \Dingedi\PsTools\DgTools::jsonError($data);
    }

    private function loadAssets()
    {
        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            $this->context->controller->css_files['https://fonts.googleapis.com/icon?family=Material+Icons'] = 'all';
        }

        $this->context->controller->css_files[$this->_path . 'views/css/prestashop-ui-kit.css?v=' . $this->version] = 'all';
        $this->context->controller->js_files[] = $this->_path . 'views/js/dg.runtime.js?v=' . $this->version;
        $this->context->controller->js_files[] = $this->_path . 'views/js/dg.vendors.js?v=' . $this->version;
    }

//ENDdgcontenttranslation



    //STARTmodules

    private function initModules($loadTableOnly = false)
    {
        if ($loadTableOnly === false) {
            $this->dgModuleConfig = array(
                'add_language_link'              => $this->context->link->getAdminLink('AdminLocalization'),
                'link_admin_db_backup'           => \Dingedi\PsTools\DgTools::getAdminLink('AdminBackup'),
                'link_admin_update_url_settings' => \Dingedi\PsTools\DgTools::getAdminLink('AdminMeta'),
                'default_lang'                   => \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId(),
                'module_name'                    => $this->name,
            );
        }

        require_once _PS_MODULE_DIR_ . 'dgtranslationall/classes/Modules/autoload.php';

        ModulesTablesAdapterList::register();
    }

    public function ajaxProcessModulesGetWidgetData()
    {
        $this->jsonResponse(array(
            'moduleConfig' => array_merge(
                $this->dgModuleConfig,
                array('languages' => $this->getLanguages()),
                array('translations' => $this->getModulesModuleTranslations()),
                array('translationsProviders' => \Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationsConfiguration()),
                array('shopConfig' => \Dingedi\PsTranslationsApi\DgTranslationTools::getShopConfig())
            ),
        ));
    }

    public function ajaxProcessModulesTranslateWidget()
    {
        $data = $this->checkAndGetParams(array('id_lang_from', 'id_lang_to', 'text', 'latin'));

        $from = (int)$data['id_lang_from'];
        $to = (int)$data['id_lang_to'];
        $text = (string)$data['text'];
        $latin = (int)$data['latin'];

        try {
            if (trim($text) !== "") {
                if (strpos($text, "|DGTAGSTOKENS|") === 0) {
                    $splitted = explode('|DGTAGSTOKENS|', $text)[1];
                    $splitted = explode(',', $splitted);
                    $translated = array();

                    foreach ($splitted as $str) {
                        $translated[] = \Dingedi\PsTranslationsApi\DgTranslateApi::translate(
                            $str,
                            \Dingedi\PsTools\DgTools::getLocale((int)$from),
                            \Dingedi\PsTools\DgTools::getLocale((int)$to),
                            $latin
                        );
                    }

                    $text = implode(', ', $translated);
                } else {
                    $text = \Dingedi\PsTranslationsApi\DgTranslateApi::translate(
                        $text,
                        \Dingedi\PsTools\DgTools::getLocale((int)$from),
                        \Dingedi\PsTools\DgTools::getLocale((int)$to),
                        $latin
                    );
                }
            }
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('The data has been translated. Remember to save the changes.'),
            'text'    => $text
        ));
    }

    public function ajaxProcessModulesGetMissingTranslations()
    {
        $data = $this->checkAndGetParams(array('name', 'id_lang_to'));

        $this->jsonResponse(
            DgModulesList::getObject(
                (string)$data['name'],
                (int)$data['id_lang_to']
            )->jsonSerialize()
        );
    }

    public function ajaxProcessModulesTablesGetPercentageTranslation()
    {
        $data = $this->checkAndGetParams(array('name', 'id_lang_to'));

        $dgTableTranslatable = \Dingedi\TablesTranslation\DgTablesList::getObject((string)$data['name']);
        $dgTableCalculateMissingTranslations = new \Dingedi\TablesTranslation\DgTableCalculateMissingTranslations($dgTableTranslatable);

        $this->jsonResponse(
            $dgTableCalculateMissingTranslations->getTranslationsPercent(Language::getLanguage((int)$data['id_lang_to']))
        );
    }

    public function ajaxProcessModulesTranslateTable()
    {
        $data = $this->checkAndGetParams(array('name', 'id_lang_from', 'id_lang_to', 'latin', 'overwrite', 'requests', 'current'));

        $paginate = ((int)$data['requests'] > 1) ? (int)$data['current'] : 1;

        try {
            $dgTableTranslatable = \Dingedi\TablesTranslation\DgTablesList::getObject(
                (string)$data['name']
            );

            $dgTableTranslation = new \Dingedi\TablesTranslation\DgTableTranslation(
                $dgTableTranslatable,
                (int)$data['id_lang_from'],
                (int)$data['id_lang_to'],
                ($data['overwrite'] === 'true'),
                (int)$data['latin']
            );

            $dgTableTranslation->translate($paginate);

            return $this->jsonResponse(array(
                'success' => 1, 'message' => $this->l('Data has been translated')
            ));
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('The selected modules have been translated.')
        ));
    }

    public function ajaxProcessModulesTranslate()
    {
        $data = $this->checkAndGetParams(array('name', 'id_lang_from', 'id_lang_to', 'latin', 'translations'));

        try {
            $dgModuleTranslatable = DgModulesList::getObject(
                (string)$data['name'],
                (int)$data['id_lang_to']
            );

            $translations = array();

            foreach ($data['translations'] as $translation) {
                foreach ($translation as $k => $v) {
                    $translations[$k] = $v;
                }
            }

            $dgModuleTranslatable->translateMissingTranslations(
                $translations,
                (int)$data['id_lang_from'],
                (int)$data['latin']
            );
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('The selected modules have been translated.')
        ));
    }

    public function ajaxProcessModulesGetData()
    {
        $this->jsonResponse(array(
            'moduleConfig'  => array_merge(
                $this->dgModuleConfig,
                array('languages' => $this->getLanguages()),
                array('translations' => $this->getModulesModuleTranslations()),
                array('translationsProviders' => \Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationsConfiguration()),
                array('shopConfig' => \Dingedi\PsTranslationsApi\DgTranslationTools::getShopConfig())
            ),
            'modulesFiles'  => $this->getModulesTranslatableFilesList(),
            'modulesTables' => $this->getModulesTranslatableTablesList()
        ));
    }

    private function getModulesModuleTranslations()
    {
        return array_merge(
            $this->getContentModuleTranslations(),
            array(
                'form'                                                                                                                                 => array(
                    'modules_filter' => array(
                        'search' => $this->l('Search'),
                        'others' => $this->l('Others'),
                    ),
                    'button'         => array(
                        'translate' => $this->l('Translate'),
                        'stop'      => $this->l('Stop'),
                    ),
                    'languages'      => array(
                        'locked_from'       => array(
                            'default' => $this->l('English by default'),
                            'change'  => $this->l('Change default language'),
                            'reset'   => $this->l('Reset'),
                            'warning' => $this->l('warning'),
                            'help'    => $this->l('The source language of PrestaShop modules by default is English. Only change this setting if you are sure what you are doing')
                        ),
                        'advanced_settings' => $this->l('Advanced parameters'),
                        'source'            => $this->l('Language source'),
                        'from_help'         => $this->l('The source language is the language you want to translate from'),
                        'to'                => $this->l('Languages to translate'),
                        'to_help'           => $this->l('The languages to translate are the languages you want to translate, from the source language selected previously'),
                        'latin_title'       => $this->l('Latin option (for supported languages)'),
                        'latin_input'       => $this->l('My source text is in Latin characters'),
                        'latin_output'      => $this->l('I want to translate into Latin characters'),
                        'overwrite'         => $this->l('Overwrite all translations'),
                    ),
                ),
                'modules'                                                                                                                              => array(
                    'alerts' => array(
                        'already_translated' => $this->l('The selected modules are already fully translated into this language.'),
                    ),
                ),
                'table'                                                                                                                                => array(
                    'head'    => array(
                        'module'     => $this->l('Module'),
                        'modules'    => $this->l('Modules'),
                        'prestashop' => 'PrestaShop',
                        'addons'     => 'Addons',
                        'action'     => $this->l('Action'),
                        'search'     => $this->l('Search'),
                    ),
                    'options' => array(
                        'modules_files'     => $this->l('Modules Databases (Front Office)'),
                        'modules_databases' => $this->l('Interface translation (Back Office)'),
                    )
                ),
                'tables'                                                                                                                               => array(
                    'load_by_click_message' => $this->l('This table contains a lot of data. To load the display of the translation percentages, please click manually on the button.'),
                    'load_by_click'         => $this->l('Load by clicking'),
                    'error_loading'         => $this->l('Error when retrieving translation percentages.'),
                ),
                'groups'                                                                                                                               => array(
                    'certified'    => $this->l('Belongs to PrestaShop, does not come from an external module.'),
                    'server_error' => $this->l('Server error'),
                    'error'        => $this->l('Error'),
                ),
                'api_keys'                                                                                                                             => array(
                    'microsoftProvider' => array(
                        array('label' => $this->l('Global'), 'value' => 'api'),
                        array('label' => $this->l('North America'), 'value' => 'api-nam'),
                        array('label' => $this->l('Europe'), 'value' => 'api-eur'),
                        array('label' => $this->l('Asia Pacific'), 'value' => 'api-apc'),
                    ),
                ),
                'Setup wizard'                                                                                                                         => $this->l('Setup wizard'),
                'Next'                                                                                                                                 => $this->l('Next'),
                'Previous'                                                                                                                             => $this->l('Previous'),
                'Message from %s'                                                                                                                      => $this->l('Message from %s'),
                'Free monthly quota of %s characters'                                                                                                  => $this->l('Free monthly quota of %s characters'),
                'Pricing options'                                                                                                                      => $this->l('Pricing options'),
                'Obtain an API key'                                                                                                                    => $this->l('Obtain an API key'),
                'offers a trial offer: %s credit for %s months'                                                                                        => $this->l('offers a trial offer: %s credit for %s months'),
                'Finish'                                                                                                                               => $this->l('Finish'),
                'Message from'                                                                                                                         => $this->l('Message from'),
                'You must have a Google Cloud account before starting.'                                                                                => $this->l('You must have a Google Cloud account before starting.'),
                'Click here'                                                                                                                           => $this->l('Click here'),
                'to create one.'                                                                                                                       => $this->l('to create one.'),
                'Go to the Google Cloud dashboard'                                                                                                     => $this->l('Go to the Google Cloud dashboard'),
                'Click on "Select a project"'                                                                                                          => $this->l('Click on "Select a project"'),
                'Click on "New project"'                                                                                                               => $this->l('Click on "New project"'),
                'Create a project'                                                                                                                     => $this->l('Create a project'),
                'Select the project'                                                                                                                   => $this->l('Select the project'),
                'Click on "APIs & Services"'                                                                                                           => $this->l('Click on "APIs & Services"'),
                'Click on "Enable APIs and Services"'                                                                                                  => $this->l('Click on "Enable APIs and Services"'),
                'Search and select "Cloud Translation API"'                                                                                            => $this->l('Search and select "Cloud Translation API"'),
                'Enable "Cloud Translation API'                                                                                                        => $this->l('Enable "Cloud Translation API'),
                'You can skip these two steps if you already have a billing account in your Google Cloud account.'                                     => $this->l('You can skip these two steps if you already have a billing account in your Google Cloud account.'),
                'Create an API key'                                                                                                                    => $this->l('Create an API key'),
                'Copy and enter it in the module'                                                                                                      => $this->l('Copy and enter it in the module'),
                'Click to view in full screen'                                                                                                         => $this->l('Click to view in full screen'),
                'You must have a Microsoft Azure account before starting.'                                                                             => $this->l('You must have a Microsoft Azure account before starting.'),
                'Go to the Microsoft Azure dashboard'                                                                                                  => $this->l('Go to the Microsoft Azure dashboard'),
                'Click on "Create a ressource"'                                                                                                        => $this->l('Click on "Create a ressource"'),
                'Search "Translator text"'                                                                                                             => $this->l('Search "Translator text"'),
                'Click on "Create"'                                                                                                                    => $this->l('Click on "Create"'),
                'Fill in the required fields and click on "Review + create"'                                                                           => $this->l('Fill in the required fields and click on "Review + create"'),
                'Go to ressource'                                                                                                                      => $this->l('Go to ressource'),
                'Click on "Keys and Endpoint"'                                                                                                         => $this->l('Click on "Keys and Endpoint"'),
                'Copy KEY 1 enter it in the module'                                                                                                    => $this->l('Copy KEY 1 enter it in the module'),
                'Configure API keys'                                                                                                                   => $this->l('Configure API keys'),
                'Apply for all shops'                                                                                                                  => $this->l('Apply for all shops'),
                'API Key'                                                                                                                              => $this->l('API Key'),
                'Save'                                                                                                                                 => $this->l('Save'),
                'Server'                                                                                                                               => $this->l('Server'),
                'Location'                                                                                                                             => $this->l('Location'),
                'Show'                                                                                                                                 => $this->l('Show'),
                'Hide'                                                                                                                                 => $this->l('Hide'),
                'Plan'                                                                                                                                 => $this->l('Plan'),
                'offers a free offer with a quota of %s characters per month'                                                                          => $this->l('offers a free offer with a quota of %s characters per month'),
                'Add a language'                                                                                                                       => $this->l('Add a language'),
                'Translation of modules'                                                                                                               => $this->l('Translation of modules'),
                'Module database translation'                                                                                                          => $this->l('Module database translation'),
                'Tools'                                                                                                                                => $this->l('Tools'),
                'Settings'                                                                                                                             => $this->l('Settings'),
                'Excluded words'                                                                                                                       => $this->l('Excluded words'),
                'Exclude words from translation'                                                                                                       => $this->l('Exclude words from translation'),
                'The words you add will not be translated. For example, you can add brand names.'                                                      => $this->l('The words you add will not be translated. For example, you can add brand names.'),
                'API Keys'                                                                                                                             => $this->l('API Keys'),
                'Performance'                                                                                                                          => $this->l('Performance'),
                'Exclude all brands'                                                                                                                   => $this->l('Exclude all brands'),
                'Add a word to exclude from the translation'                                                                                           => $this->l('Add a word to exclude from the translation'),
                'Elements per query'                                                                                                                   => $this->l('Elements per query'),
                'Do not forget to make a backup of your database before starting the translation!'                                                     => $this->l('Do not forget to make a backup of your database before starting the translation!'),
                'If the translation of certain words do not fit, you can define your own translations here.'                                           => $this->l('If the translation of certain words do not fit, you can define your own translations here.'),
                'Add a word'                                                                                                                           => $this->l('Add a word'),
                'Smart dictionary'                                                                                                                     => $this->l('Smart dictionary'),
                'new'                                                                                                                                  => $this->l('new'),
                'Add'                                                                                                                                  => $this->l('Add'),
                'Disabled'                                                                                                                             => $this->l('Disabled'),
                'Enabled'                                                                                                                              => $this->l('Enabled'),
                'Add the word whose translation you want to change first, then add the translations you want.'                                         => $this->l('Add the word whose translation you want to change first, then add the translations you want.'),
                'Service to use'                                                                                                                       => $this->l('Service to use'),
                'Video tutorial'                                                                                                                       => $this->l('Video tutorial'),
                'Configuration'                                                                                                                        => $this->l('Configuration'),
                'Supported languages'                                                                                                                  => $this->l('Supported languages'),
                'List of ISO codes accepted for translation'                                                                                           => $this->l('List of ISO codes accepted for translation'),
                'PrestaShop Addons order ID'                                                                                                           => $this->l('PrestaShop Addons order ID'),
                'We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.' => $this->l('We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.'),
                'Leave a review on our module and get better translation speed for free.'                                                              => $this->l('Leave a review on our module and get better translation speed for free.'),
                'Leave a review'                                                                                                                       => $this->l('Leave a review'),
                'Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'                                    => $this->l('Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'),
                'Some installed languages use non-Latin characters:'                                                                                   => $this->l('Some installed languages use non-Latin characters:'),
                'Change settings'                                                                                                                      => $this->l('Change settings'),
                'Select all'                                                                                                                           => $this->l('Select all'),
                'items'                                                                                                                                => $this->l('items'),
                'Fields to translate'                                                                                                                  => $this->l('Fields to translate'),
                'Translation speed'                                                                                                                    => $this->l('Translation speed'),
                'The value corresponds to the number of items (e.g. products) translated in each query.'                                               => $this->l('The value corresponds to the number of items (e.g. products) translated in each query.'),
                'Very low'                                                                                                                             => $this->l('Very low'),
                'Low'                                                                                                                                  => $this->l('Low'),
                'Normal'                                                                                                                               => $this->l('Normal'),
                'High'                                                                                                                                 => $this->l('High'),
                'Custom'                                                                                                                               => $this->l('Custom'),
                'Not available with'                                                                                                                   => $this->l('Not available with'),
                'This word already exists for this language'                                                                                           => $this->l('This word already exists for this language'),
                'Update'                                                                                                                               => $this->l('Update'),
                'Estimated time remaining before the end of the translation.'                                                                          => $this->l('Estimated time remaining before the end of the translation.'),
                'Order ID without the #'                                                                                                               => $this->l('Order ID without the #'),
                'Advanced parameters'                                                                                                                  => $this->l('Advanced parameters'),
                'Overwrite all translations'                                                                                                           => $this->l('Overwrite all translations'),
                'Latin option (for supported languages)'                                                                                               => $this->l('Latin option (for supported languages)'),
                'My source text is in Latin characters'                                                                                                => $this->l('My source text is in Latin characters'),
                'I want to translate into Latin characters'                                                                                            => $this->l('I want to translate into Latin characters'),
                'Languages to translate'                                                                                                               => $this->l('Languages to translate'),
                'The languages to translate are the languages you want to translate, from the source language selected previously'                     => $this->l('The languages to translate are the languages you want to translate, from the source language selected previously'),
                'Friendly URLs are disabled, internal links in your content will not be translated.'                                                   => $this->l('Friendly URLs are disabled, internal links in your content will not be translated.'),
                'Source language'                                                                                                                      => $this->l('Source language'),
                'English by default'                                                                                                                   => $this->l('English by default'),
                'Change default language'                                                                                                              => $this->l('Change default language'),
                'The source language is the language you want to translate from'                                                                       => $this->l('The source language is the language you want to translate from'),
                'Server error'                                                                                                                         => $this->l('Server error'),
                'show details'                                                                                                                         => $this->l('show details'),
                'Help'                                                                                                                                 => $this->l('Help'),
                'Here are several solutions to solve this error:'                                                                                      => $this->l('Here are several solutions to solve this error:'),
                'Reduce the translation speed in the module settings.'                                                                                 => $this->l('Reduce the translation speed in the module settings.'),
                'Increase the "max_execution_time" parameter in the php.ini configuration file of your server.'                                        => $this->l('Increase the "max_execution_time" parameter in the php.ini configuration file of your server.'),
                'Contact your server manager to increase the "Timeout" setting of your Apache web server.'                                             => $this->l('Contact your server manager to increase the "Timeout" setting of your Apache web server.'),
                'If the error is still present, please contact us.'                                                                                    => $this->l('If the error is still present, please contact us.'),
                'Close'                                                                                                                                => $this->l('Close'),
                'Translate fields'                                                                                                                     => $this->l('Translate fields'),
                'A translation button will appear next to the multilingual fields of the modules to allow you to translate them'                       => $this->l('A translation button will appear next to the multilingual fields of the modules to allow you to translate them'),
                'Always enable'                                                                                                                        => $this->l('Always enable'),
                'Also display on content pages (categories, attributes, pages, etc.)'                                                                  => $this->l('Also display on content pages (categories, attributes, pages, etc.)'),
            )
        );
    }

    private function getModulesTranslatableTablesList()
    {
        $list = $this->filterExistingTables(\Dingedi\TablesTranslation\DgTablesList::getList());

        usort($list, function ($a, $b) {
            return $a->getTableName() > $b->getTableName();
        });

        return array(
            array(
                'group_name' => $this->l('Catalog'),
                'icon'       => 'store',
                'tables'     => $list
            )
        );
    }

    private function getModulesTranslatableFilesList()
    {
        $list = DgModulesList::getList();

        usort($list, function ($a, $b) {
            return $a['name'] > $b['name'];
        });

        return $list;
    }

    //ENDmodules

    //STARTthemes-and-emails
    private function initThemesAndMails()
    {
        $this->dgModuleConfig = array(
            'add_language_link'              => $this->context->link->getAdminLink('AdminLocalization'),
            'link_admin_db_backup'           => \Dingedi\PsTools\DgTools::getAdminLink('AdminBackup'),
            'link_admin_update_url_settings' => \Dingedi\PsTools\DgTools::getAdminLink('AdminMeta'),
            'is_16'                          => \Dingedi\PsTools\DgShopInfos::isPrestaShop16(),
            'module_name'                    => $this->name,
            'default_lang'                   => \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId(),
        );

        require_once _PS_MODULE_DIR_ . 'dgtranslationall/classes/Mails/autoload.php';
        require_once _PS_MODULE_DIR_ . 'dgtranslationall/classes/Themes/autoload.php';
    }

    public function ajaxProcessThemesGetList()
    {
        $this->jsonResponse(DgThemesList::getList());
    }

    public function ajaxProcessMailsGetList()
    {
        $data = $this->checkAndGetParams(array('id_lang_from'));

        $this->jsonResponse(DgMailsList::getList((int)$data['id_lang_from']));
    }

    public function ajaxProcessThemesGetMissingTranslations()
    {
        set_time_limit(300);
        $data = $this->checkAndGetParams(array('theme', 'id_lang_to'));

        $this->jsonResponse(
            DgThemesList::getObject(
                (string)$data['theme'],
                (int)$data['id_lang_to']
            )->jsonSerialize());
    }

    public function ajaxProcessThemesTranslate()
    {
        $data = $this->checkAndGetParams(array('theme', 'id_lang_from', 'id_lang_to', 'latin', 'translations'));

        try {
            $themeTranslatation = DgThemesList::getObject(
                (string)$data['theme'],
                (int)$data['id_lang_to']
            );

            $translations = array();

            foreach ($data['translations'] as $translation) {
                foreach ($translation as $k => $v) {
                    $translations[$k] = $v;
                }
            }

            $themeTranslatation->translateMissingTranslations($translations, (int)$data['id_lang_from'], (int)$data['latin']);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('The selected themes have been translated.')
        ));
    }

    public function ajaxProcessMailsTranslate()
    {
        $data = $this->checkAndGetParams(array('id_lang_from', 'id_lang_to', 'path', 'overwrite', 'latin'));

        try {
            $dgMailTranslatable = DgMailsList::getObject((string)$data['path'], (int)$data['id_lang_from']);
            $dgMailTranslatable->translate((int)$data['id_lang_to'], ($data['overwrite'] === 'true'), (int)$data['latin']);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }

        $this->jsonResponse(array(
            'success' => 1,
            'message' => $this->l('The selected mails have been translated.')
        ));
    }

    public function ajaxProcessThemesAndMailsGetData()
    {
        $this->jsonResponse(array(
            'moduleConfig' => array_merge(
                $this->dgModuleConfig,
                array('languages' => $this->getLanguages()),
                array('translations' => $this->getThemesModuleTranslations()),
                array('translationsProviders' => \Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationsConfiguration()),
                array('shopConfig' => \Dingedi\PsTranslationsApi\DgTranslationTools::getShopConfig())
            )
        ));
    }

    private function getThemesModuleTranslations()
    {
        return array_merge(
            $this->getContentModuleTranslations(),
            array(
                'form'                                                                                                                                 => array(
                    'button'    => array(
                        'translate' => $this->l('Translate'),
                        'stop'      => $this->l('Stop'),
                    ),
                    'languages' => array(
                        'locked_from'       => array(
                            'default' => $this->l('English by default'),
                            'change'  => $this->l('Change default language'),
                            'reset'   => $this->l('Reset'),
                            'warning' => $this->l('warning'),
                            'help'    => $this->l('The source language of PrestaShop themes by default is English. Only change this setting if you are sure what you are doing')
                        ),
                        'advanced_settings' => $this->l('Advanced parameters'),
                        'source'            => $this->l('Language from'),
                        'from_help'         => $this->l('The source language is the language you want to translate from'),
                        'to'                => $this->l('Languages to translate'),
                        'to_help'           => $this->l('The languages to translate are the languages you want to translate, from the source language selected previously'),
                        'latin_title'       => $this->l('Latin option (for supported languages)'),
                        'latin_input'       => $this->l('My source text is in Latin characters'),
                        'latin_output'      => $this->l('I want to translate into Latin characters'),
                        'overwrite'         => $this->l('Overwrite all translations'),
                    ),
                ),
                'mails'                                                                                                                                => array(
                    'errors'       => array(
                        'server' => $this->l('Server error'),
                    ),
                    'untranslated' => $this->l('Untranslated'),
                    'theme'        => $this->l('Theme'),
                    'core'         => $this->l('core'),
                    'modules'      => $this->l('modules'),
                    'theme_emails' => $this->l('Theme emails'),
                    'active'       => $this->l('active'),
                    'core_emails'  => $this->l('Core emails (PrestaShop)'),
                    'available'    => $this->l('This email is available in'),
                    'availability' => $this->l('The emails you want to translate must be available in the source language.')
                ),
                'themes'                                                                                                                               => array(
                    'errors' => array(
                        'unstranslatable_error_ps' => $this->l('If after several translations, some translations remain untranslated, it may be an error related to PrestaShop and not to our module.'),
                        'get_error'                => $this->l('Your server returned an error when retrieving translations.'),
                        'get_error_ps'             => $this->l('An error has occurred. PrestaShop could not recover the translation files for the selected language. This error is related to PrestaShop.'),
                    ),
                    'alerts' => array(
                        'already_translated' => $this->l('The selected themes are already fully translated into this language.'),
                    ),
                ),
                'api_keys'                                                                                                                             => array(
                    'microsoftProvider' => array(
                        array('label' => $this->l('Global'), 'value' => 'api'),
                        array('label' => $this->l('North America'), 'value' => 'api-nam'),
                        array('label' => $this->l('Europe'), 'value' => 'api-eur'),
                        array('label' => $this->l('Asia Pacific'), 'value' => 'api-apc'),
                    ),
                ),
                'Previous'                                                                                                                             => $this->l('Previous'),
                'Message from %s'                                                                                                                      => $this->l('Message from %s'),
                'Free monthly quota of %s characters'                                                                                                  => $this->l('Free monthly quota of %s characters'),
                'Pricing options'                                                                                                                      => $this->l('Pricing options'),
                'Obtain an API key'                                                                                                                    => $this->l('Obtain an API key'),
                'offers a trial offer: %s credit for %s months'                                                                                        => $this->l('offers a trial offer: %s credit for %s months'),
                'Finish'                                                                                                                               => $this->l('Finish'),
                'Message from'                                                                                                                         => $this->l('Message from'),
                'You must have a Google Cloud account before starting.'                                                                                => $this->l('You must have a Google Cloud account before starting.'),
                'Click here'                                                                                                                           => $this->l('Click here'),
                'to create one.'                                                                                                                       => $this->l('to create one.'),
                'Go to the Google Cloud dashboard'                                                                                                     => $this->l('Go to the Google Cloud dashboard'),
                'Click on "Select a project"'                                                                                                          => $this->l('Click on "Select a project"'),
                'Click on "New project"'                                                                                                               => $this->l('Click on "New project"'),
                'Create a project'                                                                                                                     => $this->l('Create a project'),
                'Select the project'                                                                                                                   => $this->l('Select the project'),
                'Click on "APIs & Services"'                                                                                                           => $this->l('Click on "APIs & Services"'),
                'Click on "Enable APIs and Services"'                                                                                                  => $this->l('Click on "Enable APIs and Services"'),
                'Search and select "Cloud Translation API"'                                                                                            => $this->l('Search and select "Cloud Translation API"'),
                'Enable "Cloud Translation API'                                                                                                        => $this->l('Enable "Cloud Translation API'),
                'You can skip these two steps if you already have a billing account in your Google Cloud account.'                                     => $this->l('You can skip these two steps if you already have a billing account in your Google Cloud account.'),
                'Create an API key'                                                                                                                    => $this->l('Create an API key'),
                'Copy and enter it in the module'                                                                                                      => $this->l('Copy and enter it in the module'),
                'Click to view in full screen'                                                                                                         => $this->l('Click to view in full screen'),
                'You must have a Microsoft Azure account before starting.'                                                                             => $this->l('You must have a Microsoft Azure account before starting.'),
                'Go to the Microsoft Azure dashboard'                                                                                                  => $this->l('Go to the Microsoft Azure dashboard'),
                'Click on "Create a ressource"'                                                                                                        => $this->l('Click on "Create a ressource"'),
                'Search "Translator text"'                                                                                                             => $this->l('Search "Translator text"'),
                'Click on "Create"'                                                                                                                    => $this->l('Click on "Create"'),
                'Fill in the required fields and click on "Review + create"'                                                                           => $this->l('Fill in the required fields and click on "Review + create"'),
                'Go to ressource'                                                                                                                      => $this->l('Go to ressource'),
                'Click on "Keys and Endpoint"'                                                                                                         => $this->l('Click on "Keys and Endpoint"'),
                'Copy KEY 1 enter it in the module'                                                                                                    => $this->l('Copy KEY 1 enter it in the module'),
                'Configure API keys'                                                                                                                   => $this->l('Configure API keys'),
                'Apply for all shops'                                                                                                                  => $this->l('Apply for all shops'),
                'API Key'                                                                                                                              => $this->l('API Key'),
                'Save'                                                                                                                                 => $this->l('Save'),
                'Server'                                                                                                                               => $this->l('Server'),
                'Location'                                                                                                                             => $this->l('Location'),
                'Show'                                                                                                                                 => $this->l('Show'),
                'Hide'                                                                                                                                 => $this->l('Hide'),
                'Plan'                                                                                                                                 => $this->l('Plan'),
                'offers a free offer with a quota of %s characters per month'                                                                          => $this->l('offers a free offer with a quota of %s characters per month'),
                'Add a language'                                                                                                                       => $this->l('Add a language'),
                'Tools'                                                                                                                                => $this->l('Tools'),
                'Settings'                                                                                                                             => $this->l('Settings'),
                'Excluded words'                                                                                                                       => $this->l('Excluded words'),
                'Exclude words from translation'                                                                                                       => $this->l('Exclude words from translation'),
                'The words you add will not be translated. For example, you can add brand names.'                                                      => $this->l('The words you add will not be translated. For example, you can add brand names.'),
                'Exclude all brands'                                                                                                                   => $this->l('Exclude all brands'),
                'Add a word to exclude from the translation'                                                                                           => $this->l('Add a word to exclude from the translation'),
                'API Keys'                                                                                                                             => $this->l('API Keys'),
                'Performance'                                                                                                                          => $this->l('Performance'),
                'Elements per query'                                                                                                                   => $this->l('Elements per query'),
                'Theme translation'                                                                                                                    => $this->l('Theme translation'),
                'Email translation'                                                                                                                    => $this->l('Email translation'),
                'Setup wizard'                                                                                                                         => $this->l('Setup wizard'),
                'Do not forget to make a backup of your database before starting the translation!'                                                     => $this->l('Do not forget to make a backup of your database before starting the translation!'),
                'new'                                                                                                                                  => $this->l('new'),
                'Add'                                                                                                                                  => $this->l('Add'),
                'Disabled'                                                                                                                             => $this->l('Disabled'),
                'Enabled'                                                                                                                              => $this->l('Enabled'),
                'Smart dictionary'                                                                                                                     => $this->l('Smart dictionary'),
                'If the translation of certain words do not fit, you can define your own translations here.'                                           => $this->l('If the translation of certain words do not fit, you can define your own translations here.'),
                'Add a word'                                                                                                                           => $this->l('Add a word'),
                'Service to use'                                                                                                                       => $this->l('Service to use'),
                'Video tutorial'                                                                                                                       => $this->l('Video tutorial'),
                'Configuration'                                                                                                                        => $this->l('Configuration'),
                'Supported languages'                                                                                                                  => $this->l('Supported languages'),
                'List of ISO codes accepted for translation'                                                                                           => $this->l('List of ISO codes accepted for translation'),
                'PrestaShop Addons order ID'                                                                                                           => $this->l('PrestaShop Addons order ID'),
                'We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.' => $this->l('We offer a free and unlimited translation service. To be able to use this service, please configure your PrestaShop Addons order ID.'),
                'Leave a review on our module and get better translation speed for free.'                                                              => $this->l('Leave a review on our module and get better translation speed for free.'),
                'Leave a review'                                                                                                                       => $this->l('Leave a review'),
                'Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'                                    => $this->l('Accented URLs are disabled, urls will not be translated for languages using non-Latin characters.'),
                'Some installed languages use non-Latin characters:'                                                                                   => $this->l('Some installed languages use non-Latin characters:'),
                'Change settings'                                                                                                                      => $this->l('Change settings'),
                'Translation speed'                                                                                                                    => $this->l('Translation speed'),
                'The value corresponds to the number of items (e.g. products) translated in each query.'                                               => $this->l('The value corresponds to the number of items (e.g. products) translated in each query.'),
                'Very low'                                                                                                                             => $this->l('Very low'),
                'Low'                                                                                                                                  => $this->l('Low'),
                'Normal'                                                                                                                               => $this->l('Normal'),
                'High'                                                                                                                                 => $this->l('High'),
                'Custom'                                                                                                                               => $this->l('Custom'),
                'Not available with'                                                                                                                   => $this->l('Not available with'),
                'This word already exists for this language'                                                                                           => $this->l('This word already exists for this language'),
                'Update'                                                                                                                               => $this->l('Update'),
                'Estimated time remaining before the end of the translation.'                                                                          => $this->l('Estimated time remaining before the end of the translation.'),
                'Order ID without the #'                                                                                                               => $this->l('Order ID without the #'),
                'Friendly URLs are disabled, internal links in your content will not be translated.'                                                   => $this->l('Friendly URLs are disabled, internal links in your content will not be translated.'),
                'Source language'                                                                                                                      => $this->l('Source language'),
                'English by default'                                                                                                                   => $this->l('English by default'),
                'Change default language'                                                                                                              => $this->l('Change default language'),
                'The source language is the language you want to translate from'                                                                       => $this->l('The source language is the language you want to translate from'),
                'Languages to translate'                                                                                                               => $this->l('Languages to translate'),
                'The languages to translate are the languages you want to translate, from the source language selected previously'                     => $this->l('The languages to translate are the languages you want to translate, from the source language selected previously'),
            )
        );
    }

    //ENDthemes-and-emails

    private function getLanguages()
    {
        return array_map(function ($language) {
            return [
                'value'    => $language['id_lang'],
                'label'    => $language['name'],
                'iso_code' => $language['iso_code'],
                'locale'   => $language['locale']
            ];
        }, \Language::getLanguages(false));
    }

    public function hookDisplayBackOfficeHeader()
    {
        $configure = Tools::getValue('configure');
        $controller = Tools::getValue('controller');

        if (in_array($configure, array('dgcontenttranslation', 'dgcreativeelementstranslation'))) {
            return;
        }

        if ($configure === $this->name || $controller !== "") {
            $js_vars = array();
            $tableAdapterModal = false;

            if ($configure === $this->name) {
                $this->loadAssets();
                $type = Tools::getValue('dgtranslationallpage');

                if (in_array($type, array('content', 'modules', 'themes'))) {
                    $this->context->controller->js_files[] = $this->_path . 'views/js/dg.' . $type . '-admin.js?v=' . $this->version;
                }
            } else {
                $this->initContent(true);
                $this->initModules(true);

                if (\Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationModalEnabled()) {
                    $tableAdapterModal = \Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->supportController($controller);

                    if ($tableAdapterModal instanceof \Dingedi\TablesTranslation\AbstractTableAdapter) {
                        $id = (int)$tableAdapterModal->getObjectIdInRequest();

                        if ($id) {
                            $this->loadAssets();
                            $this->context->controller->js_files[] = $this->_path . 'views/js/dg.translate-modal.js?v=' . $this->version;

                            $js_vars = array(
                                'dgTranslateModal' => array(
                                    'tableName' => $tableAdapterModal->table,
                                    'id'        => $id
                                )
                            );
                        } else {
                            $tableAdapterModal = false;
                        }
                    }
                }
            }

            if ($controller !== "") {
                if ((
                        \Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationFieldsAlwaysEnabled() === 1
                        || (\Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationFieldsEnabled() && $tableAdapterModal === false))
                    && $configure !== $this->name) {
                    $this->loadAssets();
                    $this->context->controller->js_files[] = $this->_path . 'views/js/dg.modules-widget.js?v=' . $this->version;
                }

                $dg_base_url = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name;
                $ps_base_uri = rtrim(__PS_BASE_URI__, '/');

                $js_vars = array_merge(array(
                    'dg_base_url' => $dg_base_url,
                    'ps_base_uri' => $ps_base_uri
                ), $js_vars);

                if ($tableAdapterModal === false) {
                    $js_vars['ps_faviconnotificationbo'] = 'undefined';
                }

                if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
                    $this->context->smarty->assign($js_vars);

                    return $this->display(__FILE__, 'views/templates/admin/hook/js_vars.tpl');
                } else {
                    Media::addJsDef($js_vars);
                }
            }
        }
    }
}
