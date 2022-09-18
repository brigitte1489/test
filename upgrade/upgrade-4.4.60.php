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

function upgrade_module_4_4_60()
{
    \Dingedi\PsTranslationsApi\DgSameTranslations::install();

    if (\Configuration::hasKey('dingedi_same_tr')) {
        $datas = json_decode(\Configuration::get('dingedi_same_tr'), true);
        $tables = \Dingedi\PsTools\DgTools::arrayGroup($datas, 't');
        foreach ($tables as $k => $v) {
            $v = array_map(function ($t) {
                unset($t['t']);
                return $t;
            }, $v);

            $to_insert = array(
                'name' => \pSQL($k, false),
                'value' => \pSQL(json_encode($v, JSON_NUMERIC_CHECK), false)
            );

            \Db::getInstance()->insert(\Dingedi\PsTranslationsApi\DgSameTranslations::$table_name, $to_insert);
        }

        \Configuration::deleteByName('dingedi_same_tr');
    }

    return true;
}
