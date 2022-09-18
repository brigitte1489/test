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

function upgrade_module_4_5_66($module_obj)
{
    return \Configuration::updateValue('dingedi_automatic_translation', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_automatic_translation', 0))
        && \Configuration::updateValue('dingedi_automatic_translation_id_lang_from', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_automatic_translation_id_lang_from', \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId()))
        && \Configuration::updateValue('dingedi_automatic_translation_ids_langs_to', \Dingedi\PsTranslationsApi\DgTranslationTools::getValue('dingedi_automatic_translation_ids_langs_to', ''));
}
