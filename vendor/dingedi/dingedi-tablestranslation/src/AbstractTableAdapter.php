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

abstract class AbstractTableAdapter
{
    /** @var string|false $module module name */
    public $module = false;

    /** @var string|false $controller controller */
    public $controller = false;

    /** @var string $table table name */
    public $table;

    /** @var \ObjectModel|false $object_model object model */
    public $object_model = false;

    /** @var string $primary_key table name */
    public $primary_key;

    /** @var array $fields fields to translate */
    public $fields = array();

    /** @var array<array> $fields_rewrite */
    public $fields_rewrite = array(
        'link_rewrite' => array('name', 'title', 'meta_title'),
        'url_rewrite'  => array('name', 'title', 'meta_title'),
    );

    /** @var array $fields_tags */
    public $fields_tags = array('meta_keywords', 'tags');

    /** @var false|string $active_filter */
    public $active_filter = false;

    /** @var bool $exist */
    public $exist;

    /** @var bool $multi_shop */
    public $multi_shop;

    /** @var int $total_items */
    public $total_items;

    /** @var false|array $supported_item_rewrite */
    public $supported_item_rewrite;

    /** @var int $id_shop */
    public $id_shop;

    /** @var bool $certified */
    public $certified;

    public function __construct($table, $primary_key = null, $fields = array(), $fields_rewrite = array(), $fields_tags = array())
    {
        $this->table = $table;
        $this->primary_key = $primary_key;
        $this->fields = $fields;

        $this->fields_rewrite = array_merge_recursive($fields_rewrite, $this->fields_rewrite);
        $this->fields_tags = array_unique(array_merge($fields_tags, $this->fields_tags));

    }

    /**
     * @param string $table table name
     * @return bool
     */
    public function supportTable($table)
    {
        return $table === $this->table;
    }

    /**
     * @param string $controller controller
     * @return bool
     */
    public function supportController($controller)
    {
        if ($this->controller === 'AdminModules' && \Tools::getValue('configure') !== $this->module) {
            return false;
        }

        return $controller === $this->controller && $this->getObjectIdInRequest() !== false;
    }

    public function getObjectIdInRequest()
    {
        $base = explode('?', $_SERVER['REQUEST_URI']);
        $default = basename($base[0]);

        if ($default === 'edit') {
            $default = basename(dirname($base[0]));
        }

        $id = (int)\Tools::getValue($this->primary_key, $default);

        if ($id === 0) {
            return false;
        }

        return $id;
    }

    public function supportObjectModel($objectModel)
    {
        return get_class($objectModel) === $this->object_model;
    }

    public function supportActiveFilter()
    {
        return $this->active_filter !== false;
    }

    public function getActiveFilter()
    {
        return $this->active_filter;
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $class
     * @return mixed objectDest
     */
    public function beforeAction($objectSource, $objectDest, $class)
    {
        return $objectDest;
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $class
     * @return array
     */
    public function beforeTranslateAction($objectSource, $objectDest, $class)
    {
        return array($objectSource, $objectDest);
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $class
     * @return array
     */
    public function afterTranslateAction($objectSource, $objectDest, $class)
    {
        return array($objectSource, $objectDest);
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $class
     * @return bool
     */
    public function afterAction($objectSource, $objectDest, $class)
    {
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
        return true;
    }
}
