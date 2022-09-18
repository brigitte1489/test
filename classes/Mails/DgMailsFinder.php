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

class DgMailsFinder
{
    /** @var array $langFrom */
    private $langFrom;

    /**
     * @param array $langFrom
     * @throws \Exception
     */
    public function __construct($langFrom)
    {
        $this->langFrom = $langFrom;
    }

    /**
     * @param string $path
     * @param bool $searchModule
     * @return array
     */
    public function find($path, $searchModule = false)
    {
        if ($searchModule) {
            return $this->findMailsModulesFiles($path);
        } else {
            return $this->findMailsFiles($path);
        }
    }

    private function findMailsFiles($path)
    {
        $path = rtrim($path, '/') . '/';

        return $this->findFiles($path . $this->langFrom['iso_code']);
    }

    private function findMailsModulesFiles($path)
    {
        $mails = array();
        foreach (\Module::getModulesInstalled() as $module) {
            if (file_exists($path . $module['name'] . '/mails')) {
                array_push($mails, array(
                    'name'  => $module['name'],
                    'mails' => $this->findMailsFiles($path . $module['name'] . '/mails/')
                ));
            }
        }

        return array_values($mails);
    }

    private function findFiles($path)
    {
        if (!file_exists($path)) {
            return array();
        }

        $mails = array();
        $recursiveDirectoryIterator = new \RecursiveDirectoryIterator($path);
        $extensions = array('html', 'txt');

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($recursiveDirectoryIterator) as $file) {
            if (in_array($file->getExtension(), $extensions)) {
                $name = basename($file->getFilename(), '.' . $file->getExtension());

                $mail = array(
                    'path'         => $file->getPath() . '/' . $file->getFilename(),
                    'filename'     => $file->getFilename(),
                    'extension'    => $file->getExtension(),
                    'translations' => array()
                );

                foreach (\Dingedi\PsTools\DgShopInfos::getLanguages() as $lang) {
                    if ((int)$lang['id_lang'] === (int)$this->langFrom['id_lang']) {
                        continue;
                    }

                    $source = \Tools::file_get_contents($mail['path']);
                    $langFile = \Tools::file_get_contents(dirname(dirname($mail['path'])) . '/' . $lang['iso_code'] . '/' . $mail['filename']);

                    if ($source && $langFile === false) {
                        $translated = false;
                    } else if ($source && $langFile) {
                        $translated = $source !== $langFile;
                    } else {
                        continue;
                    }

                    $mail['translations'][] = array(
                        'id_lang'    => (int)$lang['id_lang'],
                        'name'       => $lang['name'],
                        'locale'     => \Tools::strtoupper($lang['iso_code']),
                        'translated' => $translated
                    );
                }

                $search = array_search($name, array_column($mails, 'name'));

                if ($search === false) {
                    $mails[] = array(
                        'name'  => $name,
                        'mails' => array($mail)
                    );
                } else {
                    $mails[$search]['mails'][] = $mail;
                }
            }
        }

        return array_values($mails);
    }
}
