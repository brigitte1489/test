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

if (!defined('_PS_VERSION_')) {
    exit;
}

class DgTableTranslatable16 extends AbstractTableAdapter implements \JsonSerializable
{
    /**
     * Get table name with or without prefix
     *
     * @param bool $with_prefix
     * @return string
     */
    public function getTableName($with_prefix = true)
    {
        $withoutPrefix = preg_replace('/^' . _DB_PREFIX_ . '/', '', $this->table);

        if ($with_prefix) {
            return _DB_PREFIX_ . $withoutPrefix;
        }

        return $withoutPrefix;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPrimaryKey()
    {
        if ($this->primary_key === null) {
            $this->primary_key = $this->guessPrimaryKey();
        }

        return $this->primary_key;
    }

    /**
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public function getFields()
    {
        if (empty($this->fields)) {
            $this->fields = $this->guessFields();
        }

        return $this->fields;
    }


    /**
     * @param array $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        foreach ($this->fields_rewrite as $k => $v) {
            if (!in_array($k, $fields)) {
                unset($this->fields_rewrite[$k]);
            }
        }

        return $this;
    }

    /**
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public function getFieldsRewrite()
    {
        $fields = array_keys($this->fields_rewrite);
        $i = array();

        foreach ($fields as $field) {
            if (in_array($field, $this->getFields())) {
                $i[$field] = $this->fields_rewrite[$field];
            }
        }

        return $i;
    }

    /**
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public function getFieldsTags()
    {
        return array_intersect($this->getFields(), $this->fields_tags);
    }

    /**
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function isExist()
    {
        if ($this->exist !== null) {
            return $this->exist;
        }

        try {
            $this->exist = !empty(\Db::getInstance()->executeS("SHOW TABLES LIKE '" . \pSQL($this->getTableName()) . "'"));
        } catch (\PrestaShopDatabaseException $e) {
            $this->exist = false;
        }

        return $this->exist;
    }

    public function isCertified()
    {
        if ($this->certified !== null) {
            return $this->certified;
        }

        $this->certified = in_array($this->getTableName(), DgTablesList::getCertifiedList());

        return $this->certified;
    }

    public function supportedItemRewrite($item)
    {
        if ($this->supported_item_rewrite !== null) {
            return $this->supported_item_rewrite;
        }

        $item = array_keys($item);

        foreach ($item as $field) {
            if (isset($this->fields_rewrite[$field])) {
                if (!is_array($this->fields_rewrite[$field])) {
                    $this->fields_rewrite[$field] = array($this->fields_rewrite[$field]);
                }

                $i = array_intersect($this->fields_rewrite[$field], $item);

                if (!empty($i)) {
                    $this->supported_item_rewrite = array($field => array_values($i)[0]);
                    return $this->supported_item_rewrite;
                }
            }
        }

        $this->supported_item_rewrite = false;

        return false;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPrimaryKeys()
    {
        $keys = array();
        $keys[] = $this->getPrimaryKey();
        $keys[] = 'id_lang';

        if ($this->isMultiShop()) {
            $keys[] = 'id_shop';
        }

        return $keys;
    }

    /**
     * @return bool
     */
    public function isMultiShop()
    {
        if ($this->multi_shop !== null) {
            return $this->multi_shop;
        }

        $this->multi_shop = \Shop::isFeatureActive() && $this->hasColumn('id_shop');

        return $this->multi_shop;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getTotalItems()
    {
        if ($this->total_items !== null) {
            return $this->total_items;
        }

        $query = new \DbQuery();
        $query->select('COUNT(' . $this->getPrimaryKey() . ') as total_items')
            ->from($this->getTableName(false))
            ->where('id_lang = ' . \Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId());

        $query = $query->build();

        if ($this->isMultiShop()) {
            $query .= ' ' . \Shop::addSqlRestrictionOnLang();
        }

        $items = \Db::getInstance()->executeS($query)[0]['total_items'];

        $this->total_items = (int)$items;

        return $this->total_items;
    }

    /***
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    private function guessFields()
    {
        $columns = \Db::getInstance()->executeS("SHOW COLUMNS FROM " . $this->getTableName());

        if (\Dingedi\PsTools\DgTools::searchSubArray($columns, 'Field', 'id_lang') === null) {
            return array();
        }

        $item = $this->findOne(array('id_lang' => (int)\Configuration::get('PS_LANG_DEFAULT')));

        $translatableColumns = array();

        foreach ($columns as $column) {
            if (in_array($column["Field"], array('id_lang', 'id_shop'))) {
                continue;
            }

            $re = '/([a-z]*text)|(varchar\([0-9]*\))/m';

            if (preg_match($re, $column["Type"]) && (!empty($item) && !$this->isJson($item[0][$column['Field']]))) {
                $translatableColumns[] = $column["Field"];
            }
        }

        return $translatableColumns;
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @param string $column
     * @return bool
     */
    private function hasColumn($column)
    {
        try {
            return !empty(\Db::getInstance()->executeS("SHOW COLUMNS FROM " . \pSQL($this->getTableName()) . " LIKE '" . \pSQL($column) . "'"));
        } catch (\PrestaShopDatabaseException $e) {
            return false;
        }
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    private function guessPrimaryKey()
    {
        $primaryKey = 'id_' . preg_replace('/_lang$/i', '', $this->getTableName(false));

        if ($this->hasColumn($primaryKey)) {
            return $primaryKey;
        }

        if (\Tools::substr($primaryKey, -1) === "s") {
            $primaryKey = \Tools::substr($primaryKey, 0, -1);

            if ($this->hasColumn($primaryKey)) {
                return $primaryKey;
            }
        }

        try {
            $columns = \Db::getInstance()->executeS("SHOW COLUMNS FROM " . \pSQL($this->getTableName()));
        } catch (\PrestaShopDatabaseException $e) {
            $columns = array();
        }

        $columns = array_filter($columns, function ($column) use ($primaryKey) {
            return $column['Key'] === 'PRIMARY' || (preg_match("/^id_/", $column['Field'], $m) && preg_match("/^(tinyint|smallint|mediumint|int|bigint)[\(0-9\)]{0,}/i", $column['Type'], $f)) || in_array($column, array(\pSQL($primaryKey), 'id_shop', 'id_lang'));
        });

        $primaryKeys = array_map(function ($item) {
            return $item['Field'];
        }, $columns);

        $primaryKeys = array_unique($primaryKeys);
        $primaryKeys = array_values(array_filter($primaryKeys, function ($v) {
            return !in_array($v, ['id_lang', 'id_shop']);
        }));

        if (count($primaryKeys) > 1 || count($primaryKeys) === 0) {
            return false;
        }

        return $primaryKeys[0];
    }

    public function getShopId()
    {
        if ($this->id_shop !== null) {
            return $this->id_shop;
        }

        if (!$this->isMultiShop()) {
            $this->id_shop = 1;
        }

        $this->id_shop = \Context::getContext()->shop->id;

        return $this->id_shop;
    }

    /**
     * @return \DbQueryCore
     */
    private function getBaseQuery()
    {
        $queryBuilder = new \DbQueryCore();

        $queryBuilder->select('*')
            ->from($this->getTableName(false));

        if ($this->isMultiShop()) {
            $queryBuilder->where('id_shop = ' . $this->getShopId());
        }

        return $queryBuilder;
    }

    public function findAll($where, $limit = null, $offset = null)
    {
        $sql = $this->getBaseQuery();

        if ($this->isMultiShop()) {
            $where['id_shop'] = array_values(\Shop::getContextListShopID());
        }

        foreach ($where as $k => $v) {
            if (is_array($v)) {
                if (preg_match('/<|>|=/i', $v[0])) {
                    $sql->where($this->getTableName() . '.' . $k . ' ' . $v[0]);
                    $sql->where($this->getTableName() . '.' . $k . ' ' . $v[1]);

                    continue;
                } else {
                    $whereValue = " IN (" . implode(",", $v) . ")";
                }
            } else {
                $whereValue = " = " . (is_numeric($v) ? $v : "'" . $v . "'");
            }

            $sql->where($this->getTableName() . '.' . $k . ' ' . $whereValue);
        }

        $filterType = (int)\Dingedi\PsTranslationsApi\DgTranslationTools::getTranslationFilter();

        if ($filterType !== 2 && $this->supportActiveFilter()) {
            $filter = explode('.', $this->getActiveFilter());

            $filterTable = \pSQL(_DB_PREFIX_ . $filter[0]);
            $filterField = \pSQL($filter[1]);

            $sql->innerJoin(
                $filter[0],
                null,
                $filterTable . '.' . $filterField . '=' . $filterType . ' AND ' . $filterTable . '.' . $this->getPrimaryKey() . '=' . $this->getTableName() . '.' . $this->getPrimaryKey()
            );
        }

        if ($limit !== null) {
            $sql->limit($limit, $offset);
        }

        return \Db::getInstance()->executeS($sql->build());
    }

    /**
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function findOne($where)
    {
        return $this->findAll($where, 1);
    }

    /**
     * @param int $id
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function findOneByPrimaryKey($id, $where = array())
    {
        return $this->findOne(array_merge(array(
            $this->getPrimaryKey() => $id
        ), $where));
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return null;
    }

    public function l($string)
    {
        return \Translate::getModuleTranslation('dgtranslationall', $string, get_class($this));
    }

    public function jsonSerialize()
    {
        if (!$this->isExist()) {
            return false;
        }

        return array(
            'type'             => 'table',
            'name'             => $this->getTableName(false),
            'name_with_prefix' => $this->getTableName(),
            'certified'        => $this->isCertified(),
            'fields'           => $this->getFields(),
            'multi_shop'       => $this->isMultiShop(),
            'total_items'      => $this->getTotalItems(),
            'exist'            => $this->isExist(),
            'label'            => $this->getLabel()
        );
    }
}
