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

class DgThemesList
{
    /**
     * @return string[]
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
     * @param string $themeName
     * @param int $idLangTo
     * @return DgThemeTranslatable16
     * @throws \Exception
     */
    public static function getObject($themeName, $idLangTo)
    {
        if (!in_array($themeName, self::getList())) {
            throw new \Exception("The theme {$themeName} does not exist or cannot be loaded");
        }

        $langTo = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangTo);

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return new DgThemeTranslatable16($themeName, $langTo);
        } else {
            return new DgThemeTranslatable17($themeName, $langTo);
        }
    }

    /**
     * @return array
     */
    private static function getListFor16()
    {
        $themes = array();

        if (method_exists('Theme', 'getInstalledThemeDirectories')) {
            $themes = \Theme::getInstalledThemeDirectories();
        } elseif (method_exists('Theme', 'getAvailable')) {
            $themes = \Theme::getAvailable(false);
        } else {
            foreach (\Theme::getThemes() as $theme) {
                $themes[] = $theme->directory;
            }
        }

        return $themes;
    }

    /**
     * @return array
     */
    private static function getListFor17()
    {
        $themes = array();

        $suffix = 'config/theme.yml';
        $theme_directories = glob(_PS_ALL_THEMES_DIR_ . '*/' . $suffix);

        foreach ($theme_directories as $path) {
            $themes[] = basename(\Tools::substr($path, 0, -\Tools::strlen($suffix)));
        }

        return $themes;
    }
}
