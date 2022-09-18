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

class DgThemeTranslatable17 extends DgThemeTranslatable16
{
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


    /**
     * @return array
     * @throws \Exception
     */
    public function getTranslations()
    {
        $container = $this->getContainer();

        /** @var \PrestaShopBundle\Service\TranslationService $translationService */
        $translationService = $container->get('prestashop.service.translation');

        $translated = array();
        $defaults = array();

        $iso = $this->langTo['iso_code'];

        try {
            $translationsCatalogue = $translationService->getTranslationsCatalogue($iso, 'themes', $this->themeName);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }


        foreach ($translationsCatalogue as $domain => $tr) {
            unset($tr['__metadata']);
            $domain = explode('.', $domain)[0];

//            try {
//                if (\Tools::version_compare('1.7.8.0', _PS_VERSION_, '<=')) {
//                    $themeProviderDefinition = new \PrestaShop\PrestaShop\Core\Translation\Storage\Provider\Definition\ThemeProviderDefinition($this->themeName);
//                    $domainTranslations = $translationService->listDomainTranslation($themeProviderDefinition, $this->langTo['locale'], $domain, array());
//                } else {
//                    $domainTranslations = $translationService->listDomainTranslation($this->langTo['locale'], $domain, $this->themeName);
//                }
//            } catch (\Exception $e) {
//                continue;
//            }

            $domainTranslations = [
                'data' => []
            ];

            foreach ($tr as $k => $v) {
                $domainTranslations['data'][] = [
                    'tree_domain' => array($domain),
                    'default'     => $k,
                    'database'    => $v['db'],
                    'xliff'       => $v['xlf']
                ];
            }

            $translationArray = $this->extractDomainTranslationTranslations($domainTranslations);

            if (!$translationArray) {
                continue;
            }

            $translated = array_merge($translated, $translationArray['translations']);
            $defaults = array_merge($defaults, $translationArray['defaults']);
        }

        $emailsBody = $this->loadEmailsBody();

        if (is_array($emailsBody)) {
            $translated = array_merge($translated, $emailsBody['translations']);
            $defaults = array_merge($defaults, $emailsBody['defaults']);
        }

        $translations = array();
        $missing = array();

        foreach ($defaults as $key => $value) {
            $translations[] = array(
                'key'   => $key,
                'value' => \Tools::stripslashes(html_entity_decode($value, ENT_COMPAT, 'UTF-8')),
                'trad'  => \Tools::stripslashes(html_entity_decode($translated[$key], ENT_COMPAT, 'UTF-8'))
            );
        }

        $isoLangTo = \Dingedi\PsTools\DgTools::getLocale($this->langTo);

        if (!in_array($isoLangTo, array('en', 'gb'))) {
            foreach ($translations as $translation) {
                if (($this->sameTranslations->needTranslation($this->themeName, $translation['key'], [-1, (int)$this->langTo['id_lang']]) && $translation['value'] == $translated[$translation['key']]) || in_array($translated[$translation['key']], array(null, ''))) {
                    $missing[] = $translation;
                }
            }
        }

        return array(
            'all'     => $translations,
            'missing' => $missing
        );
    }

    private function loadEmailsBody()
    {
        if (!class_exists('\PrestaShop\PrestaShop\Core\Translation\Storage\Provider\Definition\CoreDomainProviderDefinition')) {
            return false;
        }
        
        $locale = $this->langTo['locale'];
        $domain = "EmailsBody";
        $container = $this->getContainer();

        /** @var \PrestaShopBundle\Service\TranslationService $translationService */
        $translationService = $container->get('prestashop.service.translation');

        try {
            $translationService->findLanguageByLocale($locale);

            $providerDefinition = new \PrestaShop\PrestaShop\Core\Translation\Storage\Provider\Definition\CoreDomainProviderDefinition($domain);

            $catalog = $translationService->listDomainTranslation($providerDefinition, $locale, $domain, []);
        } catch (Exception $e) {
            return false;
        }

        return $this->extractDomainTranslationTranslations($catalog);
    }

    /**
     * @param $translations
     * @return bool
     */
    public function saveMissingTranslations($translations)
    {
        foreach ($translations as $translation) {
            preg_match('/<(.+)>(.+)/i', $translation['key'], $matches);

            $theme = $this->themeName;

            if ($matches[1] === "EmailsBody") {
                $theme = null;
            }

            $item = array(
                'id_lang'     => (int)$this->langTo['id_lang'],
                'key'         => \pSQL($translation['value'], true),
                'translation' => \pSQL($translation['trad'], true),
                'domain'      => \pSQL($matches[1]),
                'theme'       => is_null($theme) ? null : \pSQL($theme),
            );

            $existing = \Db::getInstance()->executeS("SELECT `id_translation`, `translation` FROM " . _DB_PREFIX_ . "translation WHERE `id_lang`=" . $item['id_lang'] . " AND `key`='" . $item['key'] . "' AND `domain`='" . $item['domain'] . "' AND `theme`='" . $item['theme'] . "' ORDER BY `id_translation` DESC");

            if (empty($existing)) {
                \Db::getInstance()->insert("translation", $item, true);
            } else {
                if ($existing[0]['translation'] !== $item['translation']) {
                    \Db::getInstance()->update("translation",
                        array('translation' => $item['translation']),
                        "`id_translation`=" . (int)$existing[0]['id_translation'] . " AND `id_lang`=" . $item['id_lang'] . " AND `key`='" . $item['key'] . "' AND `domain`='" . $item['domain'] . "' AND `theme`='" . $item['theme'] . "'"
                    );
                }
            }

            /** ADD SAME TRANSLATION */
            $this->sameTranslations->addTranslations(array(
                'i'     => $this->themeName,
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
