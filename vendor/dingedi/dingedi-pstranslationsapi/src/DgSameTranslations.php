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

class DgSameTranslations
{
    static $table_name = 'dg_same_translations';
    /** @var string $type */
    private $type;
    /** @var int $id_shop */
    private $id_shop;
    /**  @var array $translations */
    private $translations;
    /** @var array $replaces */
    private $replaces = array(
        'themes'  => array('<' => '', '>' => ''),
        'modules' => array('<' => '', '>' => ''),
    );

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->id_shop = (int)\Context::getContext()->shop->id;
    }

    public static function install()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::$table_name . '` (
            `name` varchar(255) NOT NULL,
            `value` LONGTEXT NULL
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return \Db::getInstance()->execute($sql);
    }

    public static function uninstall()
    {
        $sql = array();
        $sql[] = 'SET foreign_key_checks = 0;';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::$table_name . '`;';
        $sql[] = 'SET foreign_key_checks = 1;';

        foreach ($sql as $s) {
            if (!\Db::getInstance()->execute($s)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $items
     */
    public function addTranslations($items)
    {
        if (!isset($items[0]) || !is_array($items[0])) {
            $items = array($items);
        }

        $datas = $this->getTranslations();

        foreach ($items as $i) {
            $fields = !is_array($i['f']) ? array($i['f']) : $i['f'];
            $item = [
                's' => $this->id_shop,
                'i' => (string)$i['i'], // Object id
                'f' => $this->purifyField($fields), // Field
                '1' => (int)$i['langs'][0], // First lang
                '2' => (int)$i['langs'][1] // Second lang
            ];

            $new = true;

            foreach ($datas as $k => $v) {
                if (
                    (int)$v['s'] === (int)$item['s']
                    && (string)$v['i'] === (string)$item['i']
                    && (in_array((int)$v['1'], $i['langs']) && in_array((int)$v['2'], $i['langs']))
                ) {
                    $datas[$k]['f'] = array_unique(array_merge($v['f'], $item['f']));
                    $new = false;
                }
            }
            if ($new === true) {
                $datas[] = $item;
            }
        }

        $this->translations = $datas;

        $translations = json_encode(array_unique($datas, SORT_REGULAR), JSON_NUMERIC_CHECK);

        \Db::getInstance()->update(self::$table_name, array(
            'value' => \pSQL($translations, false)
        ), ' `name`= "' . \pSQL($this->type, false) . '" ');
    }

    /**
     * @return array
     */
    private function getTranslations()
    {
        if ($this->translations !== null) {
            return $this->translations;
        }

        $datas = \Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . self::$table_name . ' WHERE `name` = "' . $this->type . '" ');

        if (empty($datas)) {
            $to_insert = array(
                'name'  => \pSQL($this->type, false),
                'value' => \pSQL('[]', false)
            );

            \Db::getInstance()->insert(self::$table_name, $to_insert);

            $datas[0] = $to_insert;
        }

        $datas = json_decode($datas[0]['value'], true);

        // If invalid json
        if ($datas === null) {
            $datas = array();
        }

        $translations = array_unique($datas, SORT_REGULAR);

        $this->translations = $translations;

        return $this->translations;
    }

    /**
     * Purify field for save in configuration
     *
     * @param array|string $field
     * @return array|string
     */
    private function purifyField($field)
    {
        $type = explode('-', $this->type)[0];

        if (!array_key_exists($type, $this->replaces)) {
            return $field;
        }

        $isArray = is_array($field);

        if (!$isArray) {
            $field = array($field);
        }
        foreach ($field as $k => $v) {
            $field[$k] = strtr($v, $this->replaces[$type]);
        }

        return $isArray ? $field : $field[0];
    }

    /**
     * @param string $id
     * @param string $field
     * @param array $langIds
     * @return bool
     */
    public function needTranslation($id, $field, $langIds)
    {
        foreach ($this->getTranslations() as $translation) {
            if (
                in_array($this->purifyField($field), $translation['f'])
                && ((int)$translation['s'] === (int)$this->id_shop)
                && ((string)$translation['i'] === (string)$id)
                && in_array((string)$translation['1'], $langIds)
                && in_array((string)$translation['2'], $langIds)
            ) {
                return false;
            }
        }

        return true;
    }
}
