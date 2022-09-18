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

class Meta_lang extends \Dingedi\TablesTranslation\DgTableTranslatable16
{
    public $controller = 'AdminMeta';
    public $table = 'meta_lang';
    public $object_model = 'Meta';

    public function __construct()
    {
        $primary_key = 'id_meta';

        $fields = array('title', 'description', 'keywords', 'url_rewrite');
        $fields_rewrite = array('url_rewrite' => 'title');
        $fields_tags = array();

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    /**
     * @param $objectDest
     * @param $where
     * @param $class
     * @return array
     */
    public function beforeSaveAction($objectSource, $objectDest, $where, $class)
    {
        if (trim($objectSource['url_rewrite']) !== "") {
            $id_meta = $objectDest['id_meta'];
            $url_rewrite = $objectDest['url_rewrite'];

            if ($this->linkRewriteExist($url_rewrite, $id_meta, $objectDest['id_lang'])) {
                for ($i = 1; $i < 10; $i++) {
                    $new_link = $url_rewrite . '-' . $i;

                    if (!$this->linkRewriteExist($new_link, $id_meta, $objectDest['id_lang'])) {
                        break;
                    }
                }

                $objectDest['url_rewrite'] = $new_link;
            }
        }

        return array($objectDest, $where);
    }

    private function linkRewriteExist($url_rewrite, $id_meta, $id_lang)
    {
        return !empty(\Db::getInstance()->executeS("SELECT id_meta FROM " . _DB_PREFIX_ . "meta_lang WHERE id_meta != " . (int)$id_meta . " AND id_lang= " . (int)$id_lang . " AND  url_rewrite = '" . \pSQL($url_rewrite) . "' " . \Shop::addSqlRestriction()));
    }
}
