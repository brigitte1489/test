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

class DgMailTranslatable16
{
    /** @var string */
    public $path;

    /** @var array $langFrom */
    public $langFrom;

    /** @var \Dingedi\PsTranslationsApi\DgSameTranslations $sameTranslations */
    public $sameTranslations;

    /**
     * @param string $path mail path
     * @param array $langFrom
     * @throws \Exception
     */
    public function __construct($path, $langFrom)
    {
        if (!file_exists($path)) {
            throw new \Exception("Unable to find this file: {$path}");
        }

        $this->path = $path;
        $this->langFrom = $langFrom;
        $this->sameTranslations = new \Dingedi\PsTranslationsApi\DgSameTranslations('mails');
    }

    /**
     * @param int $idLangTo dest language
     * @param bool $overwrite
     * @param int $latin
     * @throws \Exception
     */
    public function translate($idLangTo, $overwrite = false, $latin = 0)
    {
        $langTo = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangTo);

        $file = new \SplFileInfo(basename($this->path));
        $destPath = dirname(dirname(trim($this->path))) . "/" . $langTo['iso_code'] . "/" . $file->getFilename();

        $sourceFile = \Tools::file_get_contents($this->path);
        $destFile = \Tools::file_get_contents($destPath);

        if ($sourceFile === false) {
            throw new \Exception("Cannot read source mail: {$this->path}");
        }
        if (($sourceFile === $destFile || $overwrite === true) || $destFile === false || trim($destFile) === "") {
            $_POST['translation_data']['mail'] = true;

            $content = \Dingedi\PsTranslationsApi\DgTranslateApi::translate(
                $sourceFile,
                \Dingedi\PsTools\DgTools::getLocale($this->langFrom),
                \Dingedi\PsTools\DgTools::getLocale($langTo),
                $latin
            );

            if (!file_exists(dirname($destPath))) {
                mkdir(dirname($destPath));
            }

            file_put_contents($destPath, $content);
        }
    }
}
