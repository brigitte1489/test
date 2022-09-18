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

class Theme_Bestwidget_lang extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'bestwidget';
    public $table = 'ps_configuration';

    public $matchesToTranslate = [];

    public function __construct()
    {
        $primary_key = '';

        $fields = array();
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function translateAction($idLangFrom, $idLangTo)
    {
        $data = $this->findOne(array('name' => 'BESTWIDGET_LAYOUT'))[0];

        if (empty($data)) {
            return;
        }

        $bestWigetData = json_decode(str_replace('\\\\"', "'", html_entity_decode($data['value'])), true);
        $bestWigetData = $this->findElementsToTranslate($bestWigetData, $idLangFrom, $idLangTo);

        foreach ($this->matchesToTranslate as $key => $value) {
            $translated = \Dingedi\PsTranslationsApi\DgTranslateApi::translate(
                $value,
                \Dingedi\PsTools\DgTools::getLocale($idLangFrom),
                \Dingedi\PsTools\DgTools::getLocale($idLangTo),
                0
            );

            $bestWigetData = $this->replace_in_array($key, $translated, $bestWigetData);
        }

        $bestWigetData = htmlentities(json_encode($bestWigetData));

        $id_shop = $this->isMultiShop() ? ' AND id_shop=' . $this->getShopId() : '';

        return \Db::getInstance()->update("configuration", array(
            'value' => \pSQL($bestWigetData, true)
        ), "name = 'BESTWIDGET_LAYOUT' " . $id_shop);
    }

    private function findElementsToTranslate($data, $idLangFrom, $idLangTo)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && !$this->_match($key)) {
                $data[$key] = $this->findElementsToTranslate($data[$key], $idLangFrom, $idLangTo);
            }

            if ($this->_match($key) && is_array($data[$key])) {
                preg_match('/(\w*?)-lang$/', $key, $matches);

                $i = $matches[0];

                $source = $data[$key][$i . $idLangFrom];

                if (!array_key_exists($i . $idLangTo, $data[$key])) {
                    $data[$key][$i . $idLangTo] = "";
                }

                if (!$source) {
                    continue;
                }

                if ($data[$key][$i . $idLangFrom] !== "" && $data[$key][$i . $idLangFrom] !== "img/logo.jpg") {
                    $idDest = md5(uniqid() . $data[$key][$i . $idLangTo]);
                    $this->matchesToTranslate[$idDest] = html_entity_decode($source, ENT_COMPAT, 'UTF-8');

                    $data[$key][$i . $idLangTo] = $idDest;
                }
            }
        }

        return $data;
    }

    private function _match($string)
    {
        $match = false;
        $m = preg_match('/(\w*?)-lang$/', $string);
        if ($m !== 0) {
            $match = true;
        }

        return $match;
    }

    private function replace_in_array($find, $replace, &$array)
    {
        array_walk_recursive($array, function (&$array) use ($find, $replace) {
            if ($array === $find) {
                $array = $replace;
            }
        });

        return $array;
    }

}
