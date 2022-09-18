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

class ModulesTablesAdapterList
{
    private static function getList()
    {
        $classList = array();

        foreach (glob(__DIR__ . '/ModulesTablesAdapter/*/*lang*.php') as $file) {
            try {
                require_once $file;

                $className = basename($file, '.php');

                if (class_exists($className)) {
                    $classList[] = new $className();
                }
            } catch (\Exception $e) {
            }
        }

        return $classList;
    }

    public static function register()
    {
        \Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->add(self::getList());
    }
}
