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

function upgrade_module_4_4_57()
{
    $manufacturers = array_map(function ($manufacturer) {
        return $manufacturer['name'];
    }, \Manufacturer::getManufacturers());


    $replacements = array(
        'google' => 'google_v2',
        'bing'   => 'microsoft_v3',
        'deepl'  => 'deepl_v2',
        'yandex' => 'yandex_v15'
    );

    $defaultProvider = \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_provider_name');

    foreach ($replacements as $k => $v) {
        $key = 'dingedi_provider_' . $k;
        if (\Configuration::hasKey($key)) {
            \Configuration::updateValue('dingedi_provider_' . $v, \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_provider_' . $k, ''));
            \Configuration::deleteByName($key);

            if ($v === $defaultProvider) {
                \Configuration::updateValue('dingedi_provider_name', $k);
            }
        }
    }

    \Configuration::updateValue('dingedi_exclude', 'true');
    \Configuration::updateValue('dingedi_excluded', implode(',', $manufacturers));

    if (\Configuration::hasKey('dingedi_provider_bing_server')) {
        \Configuration::updateValue('dingedi_provider_microsoft_server', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_provider_bing_server'));
    }

    if (\Configuration::hasKey('dingedi_provider_bing_location')) {
        \Configuration::updateValue('dingedi_provider_microsoft_location', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_provider_bing_location'));
    }
}
