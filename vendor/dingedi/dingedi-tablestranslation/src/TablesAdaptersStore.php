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

class TablesAdaptersStore
{
    private $_TablesAdaptersItems = array();
    private static $_instance = null;

    private function __construct()
    {

    }

    /**
     * @return TablesAdaptersStore
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new TablesAdaptersStore();
        }

        return self::$_instance;
    }

    /**
     * @param array<\Dingedi\TablesTranslation\AbstractTableAdapter>|\Dingedi\TablesTranslation\AbstractTableAdapter $item
     */
    public function add($item)
    {
        if (is_array($item)) {
            $this->_TablesAdaptersItems = array_merge($item, $this->_TablesAdaptersItems);
        } else {
            $this->_TablesAdaptersItems[] = $item;
        }
    }

    public function getAdapters()
    {
        return $this->_TablesAdaptersItems;
    }

    private function support($type, $value)
    {
        /** @var AbstractTableAdapter $item */
        $method = 'support' . \Tools::toCamelCase($type, true);
        foreach ($this->_TablesAdaptersItems as $item) {
            if ($item->$method($value)) {
                return $item;
            }
        }

        return false;
    }

    /**
     * @param string $controller
     * @return AbstractTableAdapter|false
     */
    public function supportController($controller)
    {
        return $this->support('controller', $controller);
    }

    /**
     * @param string $tableName
     * @return AbstractTableAdapter|false
     */
    public function supportTable($tableName)
    {
        return $this->support('table', $tableName);
    }

    /**
     * @param string $objectModel
     * @return AbstractTableAdapter|false
     */
    public function supportObjectModel($objectModel)
    {
        return $this->support('object_model', $objectModel);
    }
}
