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

class Cms_lang extends \Dingedi\TablesTranslation\DgTableTranslatable16
{
    public $controller = 'AdminCmsContent'; // TODO
    public $table = 'cms_lang';
    public $object_model = 'CMS';

    public function __construct()
    {
        $primary_key = 'id_cms';

        $fields = array('meta_title', 'meta_description', 'meta_keywords', 'content', 'link_rewrite');

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            $fields[] = 'head_seo_title';
        }

        $fields_rewrite = array('link_rewrite' => 'meta_title');
        $fields_tags = array('meta_keywords');

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    /**
     * @param $objectSource
     * @param $objectDest
     * @param $where
     * @param $class
     * @return array
     */
    public function beforeSaveAction($objectSource, $objectDest, $where, $class)
    {
        if (trim($objectSource['link_rewrite']) !== "") {
            $id_cms = $objectDest['id_cms'];
            $link_rewrite = $objectDest['link_rewrite'];

            if ($this->linkRewriteExist($link_rewrite, $id_cms, $objectDest['id_lang'])) {
                for ($i = 1; $i < 10; $i++) {
                    $new_link = $link_rewrite . '-' . $i;

                    if (!$this->linkRewriteExist($new_link, $id_cms, $objectDest['id_lang'])) {
                        break;
                    }
                }

                $objectDest['link_rewrite'] = $new_link;
            }
        }

        return array($objectDest, $where);
    }

    private function linkRewriteExist($link_rewrite, $id_cms, $id_lang)
    {
        return !empty(\Db::getInstance()->executeS("SELECT id_cms FROM " . _DB_PREFIX_ . "cms_lang WHERE id_cms != " . (int)$id_cms . " AND id_lang = " . (int)$id_lang . " AND link_rewrite = '" . \pSQL($link_rewrite) . "' " . \Shop::addSqlRestriction()));
    }

    public function getLabel()
    {
        return $this->l('Pages');
    }
}
