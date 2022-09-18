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

class Totcustomfields_input_textarea_value_lang extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'totcustomfields';
    public $table = 'totcustomfields_input_textarea_value';

    public function __construct()
    {
        $primary_key = 'id_object';

        $fields = array('value');
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function getPrimaryKeys()
    {
        $keys = parent::getPrimaryKeys();


        $keys[] = 'id_input_value';
        $keys[] = 'id_input';
        $keys[] = 'id_object';

        return array_unique($keys);
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param \Dingedi\TablesTranslation\DgTableTranslation $class
     * @return bool
     */
    public function beforeTranslateAndCheckAction($objectSource, $objectDest, $class)
    {
        $elems = $class->dgTableTranslatable->findAll([
            'id_object' => $objectSource['id_object'],
            'id_lang'   => $objectSource['id_lang']
        ]);

        $elemsToTranslate = $class->dgTableTranslatable->findAll([
            'id_object' => $objectSource['id_object'],
            'id_lang'   => $objectDest['id_lang']
        ]);

        foreach ($elems as $item) {
            $itemToTranslate = array_filter($elemsToTranslate, function($arr) use($item) {
                return $arr['id_object'] === $item['id_object'] && $arr['id_input'] === $item['id_input'];
            });

            if (empty($itemToTranslate)) {
                $itemToTranslate = $item;
                $itemToTranslate['id_lang'] = $objectDest['id_lang'];
                unset($itemToTranslate['id_input_value']);
            } else {
                $itemToTranslate = array_values($itemToTranslate)[0];
            }

            if (trim($itemToTranslate['value']) === "" || trim($itemToTranslate["value"]) === trim($item["value"])) {
                $itemToTranslate['value'] = $class->_translate($item['value']);

                if (isset($itemToTranslate['id_input_value'])) {
                    \Db::getInstance()->update(
                        $this->table,
                        ['value' => \pSQL($itemToTranslate['value'], true)],
                        "id_input_value=" . $itemToTranslate['id_input_value']);
                } else {
                    \Db::getInstance()->insert($this->table, $itemToTranslate);
                }
            }
        }

        return true;
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $class
     * @return bool
     */
    public function needTranslation($objectSource, $objectDest, $class)
    {
        return false;
    }
}
