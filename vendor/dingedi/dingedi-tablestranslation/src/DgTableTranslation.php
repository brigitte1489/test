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

class DgTableTranslation
{
    /** @var int $per_request */
    private $per_request = 10;

    /** @var DgTableTranslatable16|AbstractTableAdapter */
    public $dgTableTranslatable;

    /** @var \Dingedi\PsTranslationsApi\DgSameTranslations $dgSameTranslations */
    public $dgSameTranslations;

    /** @var array $from */
    public $from;

    /** @var array $to */
    public $to;

    /** @var bool $overwrite */
    public $overwrite;

    /** @var int $latin */
    private $latin;

    /**
     * @param DgTableTranslatable16 $dgTableTranslatable
     * @param int $idLangFrom
     * @param int $idLangTo
     * @param bool $overwrite
     * @param int $latin
     * @throws \Exception
     */
    public function __construct($dgTableTranslatable, $idLangFrom, $idLangTo, $overwrite, $latin)
    {
        if ($idLangFrom === $idLangTo) {
            throw new \Exception("You can't translate to the same language: {$idLangFrom} -> {$idLangTo}");
        }

        $this->per_request = \Dingedi\PsTranslationsApi\DgTranslationTools::getPerRequest();
        $this->dgTableTranslatable = $dgTableTranslatable;
        $this->dgSameTranslations = new \Dingedi\PsTranslationsApi\DgSameTranslations('tables-' . $dgTableTranslatable->getTableName(false));
        $this->from = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangFrom);
        $this->to = \Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangTo);
        $this->latin = $latin;

        if (\Dingedi\PsTranslationsApi\TranslationRequest::isRegenerateLinksOnly()) {
            $overwrite = true;
        }

        $this->overwrite = $overwrite;
    }

    /**
     * @param int $paginate
     * @throws \Exception
     */
    public function translate($paginate)
    {
        try {
            $translation_data = \Tools::getValue('translation_data');

            if (array_key_exists('selected_fields', $translation_data)) {
                $selected = array();

                foreach ($translation_data['selected_fields'] as $field) {
                    if (in_array($field, $this->dgTableTranslatable->getFields())) {
                        $selected[] = $field;
                    }
                }

                $this->dgTableTranslatable->setFields($selected);
            }

            $plage_enabled = (isset($translation_data['plage_enabled']) && $translation_data['plage_enabled'] === 'true');

            $primaryKey = $this->dgTableTranslatable->getPrimaryKey();

            $additionalWhere = array();

            if ($plage_enabled === true) {
                $additionalWhere = array(
                    $primaryKey => array(
                        ' >= ' . (int)$translation_data['start_id'],
                        ' <= ' . (int)$translation_data['end_id']
                    )
                );
            }

            $datas = $this->dgTableTranslatable->findAll(
                array_merge(
                    array('id_lang' => $this->from['id_lang']),
                    $additionalWhere
                ),
                $this->per_request,
                (($paginate - 1) * $this->per_request)
            );


            $ids = array_column($datas, $primaryKey);

            if (empty($ids)) {
                return;
            }

            $itemsToTranslate = $this->dgTableTranslatable->findAll(array(
                'id_lang'   => $this->to['id_lang'],
                $primaryKey => $ids
            ));


            $ids = array_column($itemsToTranslate, $primaryKey);

            foreach ($datas as $item) {
                $index = array_search($item[$primaryKey], $ids);

                if ($index === false) {
                    $itemToTranslate = array();
                } else {
                    $itemToTranslate = $itemsToTranslate[$index];
                }

                $this->translateAndSaveItem($item, $itemToTranslate);
            }
        } catch (\Exception $e) {
            throw new \Exception('Error: ' . $e->getMessage());
        }
    }

    /**
     * @param array|int $itemSource
     * @param array|null $itemDest
     * @return bool
     * @throws \Exception
     */
    public function translateAndSaveItem($itemSource, $itemDest = null)
    {
        list($itemSource, $itemDest, $itemDestCopy, $update, $where) = $this->translateItem($itemSource, $itemDest);

        if (method_exists($this->dgTableTranslatable, 'beforeSaveAction')) {
            list($itemDest, $where) = $this->dgTableTranslatable->beforeSaveAction($itemSource, $itemDest, $where, $this);
        }

        try {
            if ($update === true) {
                $diff = array_diff_assoc($itemDest, $itemDestCopy);
                if (!empty($diff)) {
                    \Db::getInstance()->update($this->dgTableTranslatable->getTableName(false), $diff, implode(" AND ", $where));
                }
            } else {
                \Db::getInstance()->insert($this->dgTableTranslatable->getTableName(false), $itemDest);
            }
        } catch (\Exception $e) {
        }

        if (method_exists($this->dgTableTranslatable, 'afterAction')) {
            $this->dgTableTranslatable->afterAction($itemSource, $itemDest, $this);
        }
    }

    public function translateItem($itemSource, $itemDest = null)
    {
        $primaryKey = $this->dgTableTranslatable->getPrimaryKey();

        if (!is_array($itemSource)) {
            return true;
        }

        if ($itemDest === null) {
            $itemDest = $this->dgTableTranslatable->findOne(array(
                'id_lang'   => $this->to['id_lang'],
                $primaryKey => $itemSource[$primaryKey],
            ));
        }

        $itemDestCopy = $itemDest;

        $update = true;

        if (empty($itemDest)) {
            $itemDest = $itemSource;
            $itemDest['id_lang'] = (int)$this->to['id_lang'];
            $update = false;
        }

        if ((int)$itemSource[$primaryKey] !== (int)$itemDest[$primaryKey]) {
            return true;
        }

        if (method_exists($this->dgTableTranslatable, 'needTranslation') && $this->dgTableTranslatable->needTranslation($itemSource, $itemDest, $this) === false) {
            return true;
        }

        $where = array();
        $sameTranslations = array();

        $supportedItemRewrite = $this->dgTableTranslatable->supportedItemRewrite($itemSource);

        foreach ($this->dgTableTranslatable->getPrimaryKeys() as $key) {
            if ($key === 'id_shop') {
                unset($itemDest['id_shop']);
                $where[] = \pSQL($key) . " IN (" . implode(',', array_values(\Shop::getContextListShopID())) . ")";
            } else {
                $where[] = \pSQL($key) . " = " . $itemDest[$key];
            }
        }

        if (method_exists($this->dgTableTranslatable, 'beforeTranslateAction')) {
            list($itemSource, $itemDest) = $this->dgTableTranslatable->beforeTranslateAction($itemSource, $itemDest, $this);
        }

        foreach ($this->dgTableTranslatable->getFields() as $field) {
            /**
             * Skip if:
             *  - field doesnt exist
             *  - field is a rewrite field
             *  - field is empty
             *  - field is numeric
             */
            if (
                !array_key_exists($field, $itemSource)
                || trim($itemSource[$field]) === ""
                || (is_array($supportedItemRewrite) && $field === array_keys($supportedItemRewrite)[0])
            ) {
                if (trim($itemSource[$field]) === "") {
                    $itemDest[$field] = "";
                }

                continue;
            }

            if ($this->overwrite === false) {
                if (is_numeric($itemSource[$field])) {
                    $itemDest[$field] = $itemSource[$field];
                    continue;
                }

                if (!$this->dgSameTranslations->needTranslation(
                    $itemDest[$primaryKey],
                    $field,
                    array((int)$this->from['id_lang'], (int)$this->to['id_lang'])
                )) {
                    continue;
                }

                if (trim($itemDest[$field]) !== "") {
                    if ($itemSource[$field] !== $itemDest[$field]) {
                        continue;
                    }
                }
            }

            if (in_array($field, $this->dgTableTranslatable->getFieldsTags())) {
                $translated = $this->_translateKeywords($itemSource[$field]);
            } else {
                $translated = $this->_translate($itemSource[$field], $itemDest[$field]);
            }

            $itemDest[$field] = $translated;

            if ($itemSource[$field] === $itemDest[$field]) {
                $sameTranslations[] = $field;
            }

            $itemDest[$field] = \pSQL($translated, true);
        }

        if ($update === false) {
            foreach ($itemDest as $k => $v) {
                $itemDest[$k] = \pSQL($v, true);
            }
        }

        if (method_exists($this->dgTableTranslatable, 'afterTranslateAction')) {
            list($itemSource, $itemDest) = $this->dgTableTranslatable->afterTranslateAction($itemSource, $itemDest, $this);
        }
        if (is_array($supportedItemRewrite)) {
            $itemRewriteValue = array_values($supportedItemRewrite)[0];
            $itemRewriteKey = array_keys($supportedItemRewrite)[0];

            if (
                !in_array($itemSource[$itemRewriteKey], array(''))
                && \Tools::substr($itemSource[$itemRewriteKey], 0, 1) !== '#'
            ) {
                $itemDest[$itemRewriteKey] = preg_replace("/\s+/u", "", \pSQL(\Tools::link_rewrite($itemDest[$itemRewriteValue])));

                if (trim($itemDest[$itemRewriteKey]) === "") {
                    if (trim($itemDestCopy[$itemRewriteKey]) === "") {
                        $itemDest[$itemRewriteKey] = $itemSource[$itemRewriteKey];
                    } else {
                        $itemDest[$itemRewriteKey] = $itemDestCopy[$itemRewriteKey];
                    }
                }

                if ($itemSource[$itemRewriteKey] === $itemDest[$itemRewriteKey]) {
                    $sameTranslations[] = $itemRewriteKey;
                }
            }
        }

        if (method_exists($this->dgTableTranslatable, 'beforeAction')) {
            $itemDest = $this->dgTableTranslatable->beforeAction($itemSource, $itemDest, $this);
        }

        if (\Dingedi\PsTranslationsApi\TranslationRequest::isRegenerateLinksOnly() === false) {
            foreach ($sameTranslations as $field) {
                $this->dgSameTranslations->addTranslations(array(
                    'i'     => $itemDest[$primaryKey],
                    'f'     => $field,
                    'langs' => array((int)$this->from['id_lang'], (int)$this->to['id_lang'])
                ));
            }
        }

        return array($itemSource, $itemDest, $itemDestCopy, $update, $where);
    }

    private function _translateKeywords($tags)
    {
        if (\Dingedi\PsTranslationsApi\TranslationRequest::isRegenerateLinksOnly()) {
            return $tags;
        }

        $translated = array();

        foreach (explode(',', $tags) as $tag) {
            $translated[] = $this->_translate($tag);
        }

        return implode(',', $translated);
    }

    public function _translateContentUrls($content)
    {
        return \Dingedi\PsTranslationsApi\DgUrlTranslation::translateContentUrls($content, $this->to['id_lang']);
    }

    public function _translate($text, $original = '')
    {
        if (\Dingedi\PsTranslationsApi\TranslationRequest::isRegenerateLinksOnly()) {
            return $this->_translateContentUrls($original);
        }

        return \Dingedi\PsTranslationsApi\DgTranslateApi::translate(
            $text,
            \Dingedi\PsTools\DgTools::getLocale($this->from),
            \Dingedi\PsTools\DgTools::getLocale($this->to),
            $this->latin
        );
    }
}
