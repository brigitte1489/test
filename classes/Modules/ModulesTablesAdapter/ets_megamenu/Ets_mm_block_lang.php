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

class Ets_mm_block_lang extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'ets_megamenu';
    public $table = 'ets_mm_block_lang';

    public function __construct()
    {
        $primary_key = 'id_block';

        $fields = array('title', 'content');
        $fields_rewrite = array();
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function beforeAction($objectSource, $objectDest, $class)
    {
        $objectDest['title_link'] = $class->_translateContentUrls($objectSource['title_link']);
        $objectDest['image_link'] = $class->_translateContentUrls($objectSource['image_link']);

        return $objectDest;
    }
}
