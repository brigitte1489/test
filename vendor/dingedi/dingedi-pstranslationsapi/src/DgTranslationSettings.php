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

class DgTranslationSettings extends \Dingedi\PsTools\AbstractDingediSettings
{

    /** @var int $dingedi_translate_filter */
    public $dingedi_secret_key;
    /** @var int $dingedi_translate_filter */
    public $dingedi_per_request;
    /** @var int $dingedi_translate_filter */
    public $dingedi_resume_tr;
    /** @var int $dingedi_translate_filter */
    public $dingedi_default_lang;
    /** @var int $dingedi_translate_filter */
    public $dingedi_provider_microsoft_server;
    /** @var int $dingedi_translate_filter */
    public $dingedi_provider_microsoft_location;
    /** @var int $dingedi_translate_filter */
    public $dingedi_exclude;
    /** @var int $dingedi_translate_filter */
    public $dingedi_excluded;
    /** @var int $dingedi_translate_filter */
    public $dingedi_translate_filter;


    public function __construct()
    {
        $this->dingedi_secret_key = sha1(uniqid(rand(), true)) . rand();
        $this->dingedi_per_request = self::get('dingedi_per_request', '10');
        $this->dingedi_resume_tr = self::get('dingedi_resume_tr', 'false');
        $this->dingedi_default_lang = self::get('dingedi_default_lang', (int)self::get('PS_LANG_DEFAULT'));
        $this->dingedi_exclude = self::get('dingedi_exclude', 'true');
        $this->dingedi_excluded = self::get('dingedi_excluded', implode(',', self::getShopManufacturers()));

        // 0: all    1: active   2: inactive
        $this->dingedi_translate_filter = self::get('dingedi_translate_filter', '0');
    }

    public function install()
    {
        parent::install();

        \Dingedi\PsTranslationsApi\DgSameTranslations::install();
    }

    public function uninstall()
    {
        parent::uninstall();

        \Dingedi\PsTranslationsApi\DgSameTranslations::uninstall();
    }

    public static function getShopManufacturers()
    {
        $manufacturers = array_map(function ($manufacturer) {
            return $manufacturer['name'];
        }, \Manufacturer::getManufacturers());

        return array_values(array_unique($manufacturers));
    }
}
