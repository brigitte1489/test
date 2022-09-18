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

class DgMailsList
{
    /**
     * @param int $idLangFrom
     * @return DgMailTranslatable16[]
     * @throws \Exception
     */
    public static function getList($idLangFrom)
    {
        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return self::getListFor16($idLangFrom);
        } else if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            return self::getListFor17($idLangFrom);
        } else {
            throw new \Exception(\Dingedi\PsTools\DgShopInfos::$unsupportedVersion);
        }
    }

    /**
     * @param string $path
     * @param int $idLangFrom
     * @return DgMailTranslatable16
     * @throws \Exception
     */
    public static function getObject($path, $idLangFrom)
    {
        $langFrom = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangFrom);

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return new DgMailTranslatable16($path, $langFrom);
        } else {
            return new DgMailTranslatable17($path, $langFrom);
        }
    }

    /**
     * @param int $idLangFrom
     * @return array
     * @throws \Exception
     */
    private static function getListFor16($idLangFrom)
    {
        return self::getMailsList($idLangFrom);
    }

    /**
     * @param int $idLangFrom
     * @return array
     * @throws \Exception
     */
    private static function getListFor17($idLangFrom)
    {
        return self::getListFor16($idLangFrom);
    }

    /**
     * @param int $idLangFrom
     * @return array
     * @throws \Exception
     */
    private static function getMailsList($idLangFrom)
    {
        $dgMailsFinder = new DgMailsFinder(\Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangFrom));

        $mails = array(
            'core'    => $dgMailsFinder->find(_PS_MAIL_DIR_),
            'modules' => $dgMailsFinder->find(_PS_MODULE_DIR_, true),
            'themes'  => array()
        );

        foreach (DgThemesList::getList() as $theme) {
            $theme_dir = _PS_ALL_THEMES_DIR_ . $theme;

            array_push($mails['themes'], array(
                'theme_name' => $theme,
                'mails'      => array(
                    'core'    => $dgMailsFinder->find($theme_dir . '/mails/'),
                    'modules' => $dgMailsFinder->find($theme_dir . '/modules/', true)
                )
            ));
        }

        return $mails;
    }
}
