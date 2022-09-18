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

function upgrade_module_4_4_55($module_obj)
{
    return \Configuration::updateValue('dingedi_same_tr', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_same_tr', '[]'))
        && \Configuration::updateValue('dingedi_default_lang', Configuration::get($module_obj->module_nkey . '_default_lang', (int)\Dingedi\PsTranslationsApi\DgTranslationTools::getValue('PS_LANG_DEFAULT')))
        && \Configuration::updateValue('dingedi_provider_google', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_google', ''))
        && \Configuration::updateValue('dingedi_provider_bing', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_bing', ''))
        && \Configuration::updateValue('dingedi_provider_bing_server', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_bing_server', 'api'))
        && \Configuration::updateValue('dingedi_provider_bing_location', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_bing_location', '-'))
        && \Configuration::updateValue('dingedi_provider_deepl', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_deepl', ''))
        && \Configuration::updateValue('dingedi_provider_yandex', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_yandex', ''))
        && \Configuration::updateValue('dingedi_provider_name', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue($module_obj->module_nkey . '_provider_name', 'google'))

        && \Configuration::deleteByName($module_obj->module_nkey . '_same_tr')
        && \Configuration::deleteByName($module_obj->module_nkey . '_default_lang')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_google')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_bing')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_bing_server')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_bing_location')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_deepl')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_yandex')
        && \Configuration::deleteByName($module_obj->module_nkey . '_provider_name');
}
