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

class Anshop_reviews_lang extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'googlemybusinessreviews';
    public $table = 'anshop_reviews';

    public function __construct()
    {
        $primary_key = 'id';

        $fields = array('text', 'time_description');
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function findAll($where, $limit = null, $offset = null)
    {
        $language = \Language::getLanguage((int)$where['id_lang']);

        unset($where['id_lang']);
        $where['language'] = $language['iso_code'];

        return parent::findAll($where, $limit, $offset);
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

        $language = \Language::getLanguage((int)\Dingedi\PsTranslationsApi\DgTranslationTools::getDefaultLangId());

        $query = new \DbQuery();
        $query->select('COUNT(' . $this->getPrimaryKey() . ') as total_items')
            ->from($this->getTableName(false))
            ->where('language = "' . $language['iso_code'] . '"');

        $query = $query->build();

        $items = \Db::getInstance()->executeS($query)[0]['total_items'];

        $this->total_items = (int)$items;

        return $this->total_items;
    }

    /**
     * @param array $itemDest
     * @param array $where
     * @param \Dingedi\TablesTranslation\DgTableTranslation $class
     */
    public function beforeSaveAction($itemDest, $where, $class)
    {
        unset($itemDest['id']);
        unset($itemDest['id_lang']);
        $itemDest['language'] = $class->to['iso_code'];

        $where = array(
            'language = "' . $class->to['iso_code'] . '"'
        );

        return array(
            $itemDest,
            $where
        );
    }
}
