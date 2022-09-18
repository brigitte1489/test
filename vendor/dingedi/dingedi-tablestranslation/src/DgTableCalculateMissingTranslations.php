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

namespace Dingedi\TablesTranslation;

use Dingedi\PsTranslationsApi\DgSameTranslations;

const BIG_TABLE_LIMIT = 6000;

class DgTableCalculateMissingTranslations
{

    /** @var DgTableTranslatable16 dgTableTranslatable */
    private $dgTableTranslatable;

    /** @var DgSameTranslations $dgSameTranslations */
    private $dgSameTranslations;

    /** @var int $default_language_id */
    private $default_language_id;

    /** @var int $id_shop */
    private $id_shop;

    /**
     * DgTableCalculateMissingTranslations constructor.
     * @param DgTableTranslatable16 $dgTableTranslatable
     */
    public function __construct($dgTableTranslatable)
    {
        $this->dgTableTranslatable = $dgTableTranslatable;
        $this->dgSameTranslations = new \Dingedi\PsTranslationsApi\DgSameTranslations('tables-' . $dgTableTranslatable->getTableName(false));
        $this->default_language_id = \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId();
        $this->id_shop = (int)\Context::getContext()->shop->id;
    }

    /**
     * @param array $language
     * @param false|integer $current
     * @return array
     * @throws \Exception
     */
    public function getTranslationsPercent($language, $current = false)
    {
        $defaultLanguage = \Language::getLanguage((int)$this->default_language_id);

        // set default source language
        if (empty($defaultLanguage['id_lang'])) {
            $defaultLanguage = \Language::getLanguage((int)\Language::getLanguages(false)[0]['id_lang']);
        }

        if ((int)$language['id_lang'] === (int)$defaultLanguage['id_lang']) {
            return array('skip' => true);
        }

        $limit = null;
        $offset = null;

        if(is_int($current)) {
            $limit = BIG_TABLE_LIMIT;
            $offset = ($current -1) * BIG_TABLE_LIMIT;
        }

        // source items
        $defaultLanguageItems = $this->dgTableTranslatable->findAll(array(
            'id_lang' => (int)$defaultLanguage['id_lang']
        ), $limit, $offset);

        $fields = $this->dgTableTranslatable->getFields();

        // total fields
        $total = count($fields) * count($defaultLanguageItems);

        // language items
        $languageItems = $this->dgTableTranslatable->findAll(array(
            'id_lang' => (int)$language['id_lang']
        ), $limit, $offset);

        $translated = 0;

        $tableKey = $this->dgTableTranslatable->getPrimaryKey();
        $columns = array_column($defaultLanguageItems, $tableKey);

        $missingCharactersToTranslate = 0;

        foreach ($languageItems as $k => $v) {
            $defaultItem = $defaultLanguageItems[array_search($v[$tableKey], $columns)];

            foreach ($fields as $field) {
                // column doesnt exist
                if (!array_key_exists($field, $defaultItem) || !array_key_exists($field, $v)) {
                    $translated++;
                    continue;
                }

                $vfield = $v[$field];
                $dfield = $defaultItem[$field];

                // empty source/dest doesnt count it
                if (($vfield === "" && $dfield === "") || (trim($vfield) === "" && trim($dfield) === "")) {
                    $total--;
                    continue;
                }

                // different value, possible translated
                if ($vfield !== $dfield && $vfield !== "") {
                    $translated++;
                    continue;
                }

                if (!$this->dgSameTranslations->needTranslation($v[$tableKey], $field, array($defaultLanguage['id_lang'], $language['id_lang']))) {
                    $translated++;
                    continue;
                }

                if (is_numeric($vfield) && is_numeric($dfield) && (int)$vfield === (int)$dfield) {
                    $translated++;
                    continue;
                }

                $missingCharactersToTranslate += \Tools::strlen($dfield);
            }
        }

        $translationsPercent = @(($total - ($total - $translated)) / $total) * 100;

        $percent = round($translationsPercent, 2);

        if ($percent > 100 || $total === 0) {
            $percent = 100;
        }

        return array(
            'id_lang'    => (int)$language['id_lang'],
            'locale'     => $language['iso_code'],
            'percent'    => $percent,
            'class_name' => $this->getPercentClassName($percent),
            'missing_characters' => $missingCharactersToTranslate
        );
    }

    /**
     * Get classname for percent
     *
     * @param int $percent
     * @return string
     */
    private function getPercentClassName($percent)
    {
        $className = 'success';

        if ($percent < 20) {
            $className = 'danger';
        } else if ($percent < 40) {
            $className = 'danger-hover';
        } else if ($percent < 70) {
            $className = 'warning-hover';
        } else if ($percent < 100) {
            $className = 'success-hover';
        }

        return $className;
    }
}
