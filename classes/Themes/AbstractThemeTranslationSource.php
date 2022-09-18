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
 * @copyright Copyright 2021 © Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */



abstract class AbstractThemeTranslationSource implements \JsonSerializable
{
    /** @var string $themeName */
    protected $themeName;

    /** @var array $langTo */
    protected $langTo;

    /** @var \Dingedi\PsTranslationsApi\DgSameTranslations $sameTranslations */
    protected $sameTranslations;

    /**
     * AbstractThemeTranslationSource constructor.
     * @param string $themeName
     * @param array $langTo
     * @throws \Exception
     */
    public function __construct($themeName, $langTo)
    {
        $this->themeName = $themeName;
        $this->langTo = $langTo;
        $this->sameTranslations = new \Dingedi\PsTranslationsApi\DgSameTranslations('themes-' . $themeName);
    }

    /**
     * @return array
     */
    abstract public function getTranslations();

    /**
     * @param array $translations
     * @param int $idLangFrom
     * @param int $latin
     * @return bool
     */
    abstract public function translateMissingTranslations($translations, $idLangFrom, $latin = 0);

    /**
     * @param array $translations
     * @return bool
     */
    abstract public function saveMissingTranslations($translations);

    /**
     * @return array
     */
    abstract public function jsonSerialize();
}
