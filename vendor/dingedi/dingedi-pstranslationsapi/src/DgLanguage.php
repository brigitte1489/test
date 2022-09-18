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

namespace Dingedi\PsTranslationsApi;

class DgLanguage
{

    /** @var $language array */
    private $language;

    /**
     * @param int $id_lang
     * @throws \Exception
     */
    public function __construct($id_lang)
    {
        $language = \Language::getLanguage($id_lang);

        if (!\Validate::isLoadedObject($language)) {
            throw new \Dingedi\PsTranslationsApi\Exception\NotSupportedLanguageException();
        }

        $this->language = $language;
    }

    static function getLanguages()
    {
        $languages = \Language::getLanguages(false);

        return array_map(function ($language) {
            new self((int)$language['id_lang']);
        }, $languages);
    }

    static function getLanguage($id_lang)
    {
        new self((int)$id_lang);
    }

    public function getLocale()
    {
        return \Dingedi\PsTools\DgTools::getLocale($this->language);
    }
}
