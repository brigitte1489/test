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

class DgTablesList
{
    /**
     * Excluded for security reasons
     *
     * @var string[] $excluded
     */
    private static $excluded = array(
        'product_lang',
        'category_lang',
        'cms_lang',
        'attribute_lang',
        'attribute_group_lang',
        'attachment_lang',
        'carrier_lang',
        'cart_rule_lang',
        'cms_category_lang',
        'contact_lang',
        'customization_field_lang',
        'feature_lang',
        'feature_value_lang',
        'gender_lang',
        'homeslider_slides_lang',
        'image_lang',
        'linksmenutop_lang',
        'manufacturer_lang',
        'meta_lang',
        'order_message_lang',
        'order_return_state_lang',
        'order_state_lang',
        'profile_lang',
        'quick_access_lang',
        'reassurance_lang',
        'risk_lang',
        'stock_mvt_reason_lang',
        'supplier_lang',
        'supply_order_state_lang',
        'psreassurance_lang',
        'configuration_lang',
        'configuration_kpi_lang',
        'lang',
        'country',
        'employee',

        'cart', 'orders', 'mail', 'customer', 'customer_thread'
        // TODO: exclude all default prestashop tables to avoid users errors by translating sensitive datas
    );

    /**
     * PrestaShop certified tables
     *
     * @var string[]
     */
    private static $certified = array(
        "access",
        "accessory",
        "address",
        "address_format",
        "admin_filter",
        "advice",
        "advice_lang",
        "alias",
        "attachment",
        "attachment_lang",
        "attribute",
        "attribute_group",
        "attribute_group_lang",
        "attribute_group_shop",
        "attribute_impact",
        "attribute_lang",
        "attribute_shop",
        "authorization_role",
        "badge",
        "badge_lang",
        "carrier",
        "carrier_group",
        "carrier_lang",
        "carrier_shop",
        "carrier_tax_rules_group_shop",
        "carrier_zone",
        "cart",
        "cart_cart_rule",
        "cart_product",
        "cart_rule",
        "cart_rule_carrier",
        "cart_rule_combination",
        "cart_rule_country",
        "cart_rule_group",
        "cart_rule_lang",
        "cart_rule_product_rule",
        "cart_rule_product_rule_group",
        "cart_rule_product_rule_value",
        "cart_rule_shop",
        "category",
        "category_group",
        "category_lang",
        "category_product",
        "category_shop",
        "cms",
        "cms_category",
        "cms_category_lang",
        "cms_category_shop",
        "cms_lang",
        "cms_role",
        "cms_role_lang",
        "cms_shop",
        "condition",
        "condition_advice",
        "condition_badge",
        "configuration",
        "configuration_kpi",
        "configuration_kpi_lang",
        "configuration_lang",
        "connections",
        "connections_page",
        "connections_source",
        "contact",
        "contact_lang",
        "contact_shop",
        "country",
        "country_lang",
        "country_shop",
        "currency",
        "currency_lang",
        "currency_shop",
        "customer",
        "customer_group",
        "customer_message",
        "customer_message_sync_imap",
        "customer_thread",
        "customization",
        "customization_field",
        "customization_field_lang",
        "customized_data",
        "date_range",
        "delivery",
        "emailsubscription",
        "employee",
        "employee_shop",
        "feature",
        "feature_lang",
        "feature_product",
        "feature_shop",
        "feature_value",
        "feature_value_lang",
        "gender",
        "gender_lang",
        "group",
        "group_lang",
        "group_reduction",
        "group_shop",
        "gsitemap_sitemap",
        "guest",
        "homeslider",
        "homeslider_slides",
        "homeslider_slides_lang",
        "hook",
        "hook_alias",
        "hook_module",
        "hook_module_exceptions",
        "image",
        "image_lang",
        "image_shop",
        "image_type",
        "import_match",
        "info",
        "info_lang",
        "info_shop",
        "lang",
        "lang_shop",
        "layered_category",
        "layered_filter",
        "layered_filter_block",
        "layered_filter_shop",
        "layered_indexable_attribute_group",
        "layered_indexable_attribute_group_lang_value",
        "layered_indexable_attribute_lang_value",
        "layered_indexable_feature",
        "layered_indexable_feature_lang_value",
        "layered_indexable_feature_value_lang_value",
        "layered_price_index",
        "layered_product_attribute",
        "link_block",
        "link_block_lang",
        "link_block_shop",
        "linksmenutop",
        "linksmenutop_lang",
        "log",
        "mail",
        "manufacturer",
        "manufacturer_lang",
        "manufacturer_shop",
        "memcached_servers",
        "message",
        "message_readed",
        "meta",
        "meta_lang",
        "module",
        "module_access",
        "module_carrier",
        "module_country",
        "module_currency",
        "module_group",
        "module_history",
        "module_preference",
        "module_shop",
        "operating_system",
        "order_carrier",
        "order_cart_rule",
        "order_detail",
        "order_detail_tax",
        "order_history",
        "order_invoice",
        "order_invoice_payment",
        "order_invoice_tax",
        "order_message",
        "order_message_lang",
        "order_payment",
        "order_return",
        "order_return_detail",
        "order_return_state",
        "order_return_state_lang",
        "order_slip",
        "order_slip_detail",
        "order_slip_detail_tax",
        "order_state",
        "order_state_lang",
        "orders",
        "pack",
        "page",
        "page_type",
        "page_viewed",
        "pagenotfound",
        "product",
        "product_attachment",
        "product_attribute",
        "product_attribute_combination",
        "product_attribute_image",
        "product_attribute_shop",
        "product_carrier",
        "product_country_tax",
        "product_download",
        "product_group_reduction_cache",
        "product_lang",
        "product_sale",
        "product_shop",
        "product_supplier",
        "product_tag",
        "profile",
        "profile_lang",
        "pscheckout_order_matrice",
        "psgdpr_consent",
        "psgdpr_consent_lang",
        "psgdpr_log",
        "psreassurance",
        "psreassurance_lang",
        "quick_access",
        "quick_access_lang",
        "range_price",
        "range_weight",
        "reassurance",
        "reassurance_lang",
        "referrer",
        "referrer_cache",
        "referrer_shop",
        "request_sql",
        "required_field",
        "risk",
        "risk_lang",
        "search_engine",
        "search_index",
        "search_word",
        "sekeyword",
        "shop",
        "shop_group",
        "shop_url",
        "smarty_cache",
        "smarty_last_flush",
        "smarty_lazy_cache",
        "specific_price",
        "specific_price_priority",
        "specific_price_rule",
        "specific_price_rule_condition",
        "specific_price_rule_condition_group",
        "state",
        "statssearch",
        "stock",
        "stock_available",
        "stock_mvt",
        "stock_mvt_reason",
        "stock_mvt_reason_lang",
        "store",
        "store_lang",
        "store_shop",
        "supplier",
        "supplier_lang",
        "supplier_shop",
        "supply_order",
        "supply_order_detail",
        "supply_order_history",
        "supply_order_receipt_history",
        "supply_order_state",
        "supply_order_state_lang",
        "tab",
        "tab_advice",
        "tab_lang",
        "tab_module_preference",
        "tag",
        "tag_count",
        "tax",
        "tax_lang",
        "tax_rule",
        "tax_rules_group",
        "tax_rules_group_shop",
        "timezone",
        "translation",
        "warehouse",
        "warehouse_carrier",
        "warehouse_product_location",
        "warehouse_shop",
        "web_browser",
        "webservice_account",
        "webservice_account_shop",
        "webservice_permission",
        "zone",
        "zone_shop"
    );

    /**
     * @return DgTableTranslatable16[]
     * @throws \Exception
     */
    public static function getList()
    {
        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return self::getListFor16();
        } else if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            return self::getListFor17();
        } else {
            throw new \Exception(\Dingedi\PsTools\DgShopInfos::$unsupportedVersion);
        }
    }

    /**
     * @param string $table_name table name with prefix
     * @param string|null $primary_key
     * @param array $fields
     * @param array $fields_rewrite
     * @param array $fields_tags
     * @return AbstractTableAdapter
     */
    public static function getObject($table_name, $primary_key = null, $fields = array(), $fields_rewrite = array(), $fields_tags = array())
    {
        $tableAdapter = \Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->supportTable($table_name);
        if ($tableAdapter !== false) {
            return $tableAdapter;
        }

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop16()) {
            return new DgTableTranslatable16($table_name, $primary_key, $fields, $fields_rewrite, $fields_tags);
        } else {
            return new DgTableTranslatable17($table_name, $primary_key, $fields, $fields_rewrite, $fields_tags);
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private static function getListFor16()
    {
        $tables = array();
        $datas = \Db::getInstance()->executeS("SHOW TABLES");

        foreach ($datas as $k) {
            foreach ($k as $table) {
                if (in_array($table, self::getExcludedList())) {
                    continue;
                }

                try {
                    $dgTableTranslatable = self::getObject($table);
                    $tableAdapter = \Dingedi\TablesTranslation\TablesAdaptersStore::getInstance()->supportTable($dgTableTranslatable->getTableName(false));

                    if ($tableAdapter !== false) {
                        $tables[] = $tableAdapter;
                    } else if (!empty($dgTableTranslatable->getFields()) && $dgTableTranslatable->getPrimaryKey() !== false) {
                        $tables[] = $dgTableTranslatable;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $tables;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private static function getListFor17()
    {
        return self::getListFor16();
    }

    static function getCertifiedList()
    {
        $prefix = _DB_PREFIX_;
        return array_map(function ($i) use ($prefix) {
            return $prefix . $i;
        }, self::$certified);
    }

    static function getExcludedList()
    {
        $prefix = _DB_PREFIX_;
        return array_map(function ($i) use ($prefix) {
            return $prefix . $i;
        }, self::$excluded);
    }
}
