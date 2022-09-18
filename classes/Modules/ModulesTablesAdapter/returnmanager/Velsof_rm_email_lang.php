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

class Velsof_rm_email_lang extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'returnmanager';
    public $table = 'velsof_rm_email';

    public function __construct()
    {
        $primary_key = 'id_template';

        $fields = array('text_content', 'subject', 'body');
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    private function _findObject($objectSource, $objectDest, $class)
    {
        $elem = $class->dgTableTranslatable->findOne([
            'template_name' => $objectSource['template_name'],
            'id_lang'       => $class->to['id_lang']
        ]);

        if (isset($elem[0])) {
            return $elem[0];
        }

        return false;
    }

    public function beforeTranslateAction($objectSource, $objectDest, $class)
    {
        $objectSource['body'] = html_entity_decode($objectSource['body'], ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $objectDest['body'] = html_entity_decode($objectDest['body'], ENT_QUOTES | ENT_COMPAT, 'UTF-8');

        return array($objectSource, $objectDest);
    }

    public function afterAction($objectSource, $objectDest, $class)
    {
        $previous = $this->_findObject($objectSource, $objectDest, $class);

        $copy = $objectDest;
        $copy['iso_code'] = $class->to['iso_code'];
        $copy['body'] = htmlentities($copy['body']);
        $copy['body'] = str_replace('\\\\\\', '\\', $copy['body']);

        unset($copy['id_template']);

        if (is_array($previous)) {
            \Db::getInstance()->update($this->table, $copy, "id_template=" . $previous['id_template']);
        } else {
            \Db::getInstance()->insert($this->table, $copy);
        }
    }
}
