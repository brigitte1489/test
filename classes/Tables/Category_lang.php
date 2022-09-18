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

class Category_lang extends \Dingedi\TablesTranslation\DgTableTranslatable16
{
    public $controller = 'AdminCategories';
    public $table = 'category_lang';
    public $object_model = 'Category';

    public function __construct()
    {
        $primary_key = 'id_category';

        $fields = array('name', 'description', 'link_rewrite', 'meta_title', 'meta_keywords', 'meta_description');
        $fields_rewrite = array('link_rewrite' => 'name');
        $fields_tags = array('meta_keywords');

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    public function getLabel()
    {
        return $this->l('Categories');
    }
}
