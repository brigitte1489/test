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


if (!defined('_PS_VERSION_')) {
    exit;
}

class DgModuleTranslatable17 extends DgModuleTranslatable16
{

    /** @see PrestaShop\TranslationToolsBundle\Translation\Helper\DomainHelper */
    private static function buildModuleBaseDomain($moduleName, $withDots = false)
    {
        $domain = 'Modules';

        if ($withDots) {
            $domain .= '.';
        }

        $domain .= self::buildModuleDomainNameComponent($moduleName);

        return $domain;
    }

    /** @see PrestaShop\TranslationToolsBundle\Translation\Helper\DomainHelper */
    private static function buildModuleDomainNameComponent($moduleName)
    {
        if ('ps_' === \Tools::substr($moduleName, 0, 3)) {
            $moduleName = \Tools::substr($moduleName, 3);
        }

        return self::transformDomainComponent($moduleName);
    }

    /** @see PrestaShop\TranslationToolsBundle\Translation\Helper\DomainHelper */
    private static function transformDomainComponent($component)
    {
        return \Tools::ucfirst(
            strtr(
                \Tools::strtolower($component),
                ['_' => '']
            )
        );
    }

    /**
     * @return bool
     */
    private function supportNewTranslationSystem()
    {
        $module = \Module::getInstanceByName($this->moduleName);

        try {
            if (is_callable(array($module, 'isUsingNewTranslationSystem'))) {
                return $module->isUsingNewTranslationSystem();
            } else {
                $domains = array_keys(\Context::getContext()->getTranslator()->getCatalogue()->all());

                $moduleBaseDomain = self::buildModuleBaseDomain($this->moduleName);
                $length = \Tools::strlen($moduleBaseDomain);

                foreach ($domains as $domain) {
                    if (\Tools::substr($domain, 0, $length) === $moduleBaseDomain) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    private function extractDomainTranslationTranslations($domainTranslations)
    {
        if (!isset($domainTranslations['data'])) {
            return null;
        }

        $defaults = array();
        $translations = array();

        foreach ($domainTranslations['data'] as $domainTranslation) {
            $domain = implode('', $domainTranslation['tree_domain']);
            $original = $domainTranslation['default'];

            $translation = null;
            if (isset($domainTranslation['database'])) {
                $translation = $domainTranslation['database'];
            } elseif (isset($domainTranslation['project'])) {
                $translation = $domainTranslation['project'];
            } elseif (isset($domainTranslation['user'])) {
                $translation = $domainTranslation['user'];
            }

            $key = "<$domain>" . md5($original);

            $translations[$key] = $translation;
            $defaults[$key] = $original;
        }

        return array(
            'defaults'     => $defaults,
            'translations' => $translations
        );
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
     */
    private function getContainer()
    {
        if (class_exists('\PrestaShop\PrestaShop\Adapter\SymfonyContainer')) {
            return \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        }

        global $kernel;

        return $kernel->getContainer();
    }

    public function getTranslations()
    {
        if (!$this->supportNewTranslationSystem()) {
            return parent::getTranslations();
        }

        $container = $this->getContainer();

        $translationService = $container->get('prestashop.service.translation');

        $moduleProvider = $container->get('prestashop.translation.external_module_provider', \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if (!$moduleProvider) {
            $moduleProvider = $container->get('prestashop.translation.module_provider');
        }
        $moduleProvider->setModuleName($this->moduleName);

        $locale = $this->langTo['locale'];
        $domains = $moduleProvider->setLocale($locale)->getDefaultCatalogue()->getDomains();

        $defaults = array();
        $translations = array();

        foreach ($domains as $domain) {
            $domain = explode('.', $domain)[0];

            try {
                if (\Tools::version_compare('1.7.8.0', _PS_VERSION_, '<=')) {
                    $moduleProviderDefinition = new \PrestaShop\PrestaShop\Core\Translation\Storage\Provider\Definition\ModuleProviderDefinition($this->moduleName);
                    $domainTranslations = $translationService->listDomainTranslation($moduleProviderDefinition, $locale, $domain, array());
                } else {
                    $domainTranslations = $translationService->listDomainTranslation($locale, $domain);
                }
            } catch (\Exception $ex) {
                continue;
            }

            $translationsData = $this::extractDomainTranslationTranslations($domainTranslations);

            if (!$translationsData) {
                continue;
            }

            $defaults = array_merge($defaults, $translationsData['defaults']);
            $translations = array_merge($translations, $translationsData['translations']);
        }

        $missing = array();
        $isoLangTo = \Dingedi\PsTools\DgTools::getLocale($this->langTo);

        if (!in_array($isoLangTo, array('en', 'gb'))) {
            foreach ($defaults as $key => $value) {
                if (($this->sameTranslations->needTranslation($this->moduleName, $key, [-1, (int)$this->langTo['id_lang']]) && $value === $translations[$key]) || in_array($translations[$key], array(null, ''))) {
                    $missing[] = array(
                        'key'   => $key,
                        'value' => \Tools::stripslashes(html_entity_decode($value, ENT_COMPAT, 'UTF-8')),
                        'trad'  => \Tools::stripslashes(html_entity_decode($translations[$key], ENT_COMPAT, 'UTF-8'))
                    );
                }
            }
        }

        return array(
            'all'     => $defaults,
            'missing' => $missing
        );
    }

    public function saveMissingTranslations($translations)
    {
        if (!$this->supportNewTranslationSystem()) {
            return parent::saveMissingTranslations($translations);
        }

        foreach ($translations as $translation) {
            preg_match('/<(.+)>(.+)/i', $translation['key'], $matches);

            $item = array(
                'id_lang'     => (int)$this->langTo['id_lang'],
                'key'         => \pSQL($translation['value'], true),
                'translation' => \pSQL($translation['trad'], true),
                'domain'      => \pSQL($matches[1]),
            );

            $existing = \Db::getInstance()->executeS("SELECT `id_translation`, `translation` FROM " . _DB_PREFIX_ . "translation WHERE `id_lang`=" . $item['id_lang'] . " AND `key`='" . $item['key'] . "' AND `domain`='" . $item['domain'] . "' AND `theme` IS NULL ORDER BY `id_translation` DESC");

            if (empty($existing)) {
                \Db::getInstance()->insert("translation", $item);
            } else {
                if ($existing[0]['translation'] !== $item['translation']) {
                    \Db::getInstance()->update("translation",
                        array('translation' => $item['translation']),
                        " `id_translation` = " . (int)$existing[0]['id_translation'] . " AND `id_lang`= " . (int)$item['id_lang'] . " AND `key` = '" . $item['key'] . "' AND `domain` = '" . $item['domain'] . "' AND `theme` IS NULL ");
                }
            }

            $this->sameTranslations->addTranslations(array(
                'i'     => $this->moduleName,
                'f'     => $translation['key'],
                'langs' => array(-1, (int)$this->langTo['id_lang'])
            ));
        }

        $container = $this->getContainer();

        /** @var \PrestaShopBundle\Service\Cache\Refresh $cacheRefresh */
        $cacheRefresh = $container->get('prestashop.cache.refresh', \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($cacheRefresh) {
            try {
                $cacheRefresh->addCacheClear();
                $cacheRefresh->execute();
            } catch (\Exception $e) {
            }
        }

        return true;
    }
}
