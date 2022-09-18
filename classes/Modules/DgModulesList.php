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

class DgModulesList
{
    /**
     * PrestaShop certified modules
     *
     * @var string[] $certified
     */
    static $certified = array(
        'ps_facetedsearch',
        'blockreassurance',
        'ps_checkout',
        'ps_buybuttonlite',
        'ps_mbo',
        'psgdpr',
        'psaddonsconnect',
        'emarketing',
        'gamification',
        'welcome',
        'statsvisits',
        'statsstock',
        'statssearch',
        'statssales',
        'statsregistrations',
        'statsproduct',
        'statspersonalinfos',
        'statsorigin',
        'statsnewsletter',
        'statslive',
        'statsforecast',
        'statsequipment',
        'statsdata',
        'statscheckup',
        'statscatalog',
        'statscarrier',
        'statsbestvouchers',
        'statsbestsuppliers',
        'statsbestproducts',
        'statsbestcustomers',
        'statsbestcategories',
        'sekeywords',
        'pagesnotfound',
        'ps_wirepayment',
        'ps_themecusto',
        'ps_socialfollow',
        'ps_shoppingcart',
        'ps_sharebuttons',
        'ps_searchbar',
        'ps_mainmenu',
        'ps_linklist',
        'ps_languageselector',
        'ps_imageslider',
        'ps_featuredproducts',
        'ps_faviconnotificationbo',
        'ps_emailsubscription',
        'ps_customtext',
        'ps_customersignin',
        'ps_customeraccountlinks',
        'ps_currencyselector',
        'ps_contactinfo',
        'ps_checkpayment',
        'ps_categorytree',
        'ps_banner',
        'gsitemap',
        'gridhtml',
        'graphnvd3',
        'dashproducts',
        'dashgoals',
        'dashtrends',
        'dashactivity',
        'contactform'
    );

    /**
     * @return DgModuleTranslatable[]
     * @throws \Exception
     */
    public static function getList()
    {
        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return self::getListFor16();
        } else if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            return self::getListFor17();
        } else {
            throw new \Exception(\Dingedi\PsTools\DgShopInfos::$unsupportedVersion);
        }
    }

    /**
     * @param string $moduleName module name
     * @param int $idLangTo
     * @return DgModuleTranslatable16
     * @throws \Exception
     */
    public static function getObject($moduleName, $idLangTo)
    {
        $langTo = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangTo);

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return new DgModuleTranslatable16($moduleName, $langTo);
        } else {
            return new DgModuleTranslatable17($moduleName, $langTo);
        }
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    private static function getListFor16()
    {
        $modules = array();
        $datas = \Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "module  ORDER BY id_module DESC");

        foreach ($datas as $module) {
            $modules[] = array(
                'name'      => $module['name'],
                'type'      => 'module',
                'certified' => in_array($module['name'], self::$certified)
            );
        }

        return $modules;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private static function getListFor17()
    {
        return self::getListFor16();
    }
}
