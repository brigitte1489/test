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

class Product_lang extends \Dingedi\TablesTranslation\DgTableTranslatable16
{
    public $controller = 'AdminProducts';
    public $table = 'product_lang';
    public $object_model = 'Product';
    public $active_filter = 'product.active';

    public function __construct()
    {
        $primary_key = 'id_product';

        $fields = array('description', 'description_short', 'link_rewrite', 'meta_description', 'meta_keywords', 'meta_title', 'name', 'available_now', 'available_later');
        $fields_rewrite = array(
            'link_rewrite' => 'name'
        );
        $fields_tags = array('meta_keywords');

        parent::__construct($this->table, $primary_key, $fields, $fields_rewrite, $fields_tags);
    }

    /**
     * @param $class \Dingedi\TablesTranslation\DgTableTranslation
     * @throws PrestaShopDatabaseException
     */
    public function afterAction($objectSource, $objectDest, $class)
    {
        $id_product = (int)$objectSource['id_product'];

        $to_id = $class->to['id_lang'];
        $from_id = $class->from['id_lang'];

        $tags = \Tag::getProductTags($id_product);

        if ($tags !== false && isset($tags[$from_id])) {
            if ($class->overwrite || (isset($tags[$to_id]) && count($tags[$to_id]) < count($tags[$from_id])) || !isset($tags[$to_id])) {
                $langs = array($from_id, $to_id);

                $hash = $this->hash(
                    $tags[$from_id],
                    isset($tags[$to_id]) ? $tags[$to_id] : []
                );

                if (!$class->dgSameTranslations->needTranslation($id_product, 'tags|' . $hash, $langs)) {
                    return true;
                }

                $tags[$to_id] = array();

                foreach ($tags[$from_id] as $tag) {
                    $_translation = $class->_translate($tag);

                    if (trim($_translation) !== "") {
                        $tags[$to_id][] = $_translation;
                    }
                }

                $tags[$to_id] = array_filter($tags[$to_id]);

                if (method_exists('Tag', 'deleteProductTagsInLang')) {
                    \Tag::deleteProductTagsInLang($id_product, $to_id);
                    \Tag::addTags($to_id, $id_product, $tags[$to_id]);
                } else {
                    \Tag::deleteTagsForProduct($id_product);

                    foreach ($tags as $id_lang => $tags_lang) {
                        \Tag::addTags($id_lang, $id_product, $tags_lang);
                    }
                }

                $tags = \Tag::getProductTags($id_product);

                $hash = $this->hash($tags[$from_id], $tags[$to_id]);

                $class->dgSameTranslations->addTranslations(array(
                    'i'     => $id_product,
                    'f'     => 'tags|' . $hash,
                    'langs' => $langs
                ));
            }
        }

        return true;
    }

    private function hash($arr1, $arr2)
    {
        return sha1(serialize($arr1) . serialize($arr2));
    }

    public function getLabel()
    {
        return $this->l('Products');
    }
}
