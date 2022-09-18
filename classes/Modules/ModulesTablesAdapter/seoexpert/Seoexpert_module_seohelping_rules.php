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

class Seoexpert_module_seohelping_rules extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'seoexpert';
    public $table = 'module_seohelping_rules';

    public $seohelping_patterns_table = "module_seohelping_patterns";
    public $seohelping_objects_table = "module_seohelping_objects";

    public function __construct()
    {
        $primary_key = 'id_rule';

        $fields = array('name');
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function beforeAction($objectSource, $objectDest, $class)
    {
        unset($objectDest['id_rule']);

        return $objectDest;
    }

    public function afterAction($objectSource, $objectDest, $class)
    {
        $id = \Db::getInstance()->Insert_ID();

        $seohelping_objects = \Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . $this->seohelping_objects_table . " WHERE id_rule = " . (int)$objectSource['id_rule'])[0];
        $seohelping_pattern = \Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . $this->seohelping_patterns_table . " WHERE id_rule = " . (int)$objectSource['id_rule']);

        $seohelping_objects['id_rule'] = $id;

        foreach ($seohelping_pattern as $k => $v) {
            $seohelping_pattern[$k]['id_rule'] = $id;
            \Db::getInstance()->insert($this->seohelping_patterns_table, $seohelping_pattern[$k]);
        }

        \Db::getInstance()->insert($this->seohelping_objects_table, $seohelping_objects);

        $class->dgSameTranslations->addTranslations(array(
            'i'     => $objectSource['id_rule'],
            'f'     => '*',
            'langs' => array((int)$class->from['id_lang'], (int)$class->to['id_lang'])
        ));
        $class->dgSameTranslations->addTranslations(array(
            'i'     => $id,
            'f'     => '*',
            'langs' => array((int)$class->from['id_lang'], (int)$class->to['id_lang'])
        ));
    }

    public function needTranslation($objectSource, $objectDest, $class)
    {
        if (!$class->dgSameTranslations->needTranslation($objectSource['id_rule'], '*', array((int)$class->from['id_lang'], (int)$class->to['id_lang']))) {
            return false;
        }

        return true;
    }
}
