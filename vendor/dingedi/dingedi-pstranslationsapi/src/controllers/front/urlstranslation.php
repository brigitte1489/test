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

class DgtranslationallUrlstranslationModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        if (\Tools::getValue('dingedi_secret_key') !== \Configuration::get('dingedi_secret_key')) {
            die();
        }

        $saved_GET = $_GET;
        $saved_SERVER = $_SERVER;
        $saved_dispatcher_instance = \Dispatcher::$instance;

        $urlsToTranslate = \Tools::getValue('dingedi_urlstotranslate');

        if (!is_array($urlsToTranslate)) {
            $urlsToTranslate = array();
        }

        $id_lang = (int)\Tools::getValue('dingedi_idlang');
        $urlsTranslated = array();

        try {
            foreach (array_unique($urlsToTranslate) as $url) {
                $urlsTranslated[$url] = $this->translateUrl($url, $id_lang);
            }
        } catch (\Exception $e) {
        }

        $_GET = $saved_GET;
        $_SERVER = $saved_SERVER;
        \Dispatcher::$instance = $saved_dispatcher_instance;

        return \Dingedi\PsTools\DgTools::jsonResponse($urlsTranslated);
    }

    private function translateUrl($url, $id_lang)
    {
        $slug = $this->getSlug($url);

        $_GET = array();
        $_SERVER['REQUEST_URI'] = $slug;
        $_SERVER['HTTP_X_REWRITE_URL'] = $slug;

        \Dispatcher::$instance = null;

        $controller = \Dispatcher::getInstance()->getController();

        if (in_array($controller, array('product', 'category', 'supplier', 'manufacturer', 'cms', 'module'))) {
            $error = false;
            foreach (array('id_product', 'id_category', 'id_supplier', 'id_manufacturer', 'id_cms', 'id_cms_category') as $v) {
                if (\Tools::getValue($v)) {
                    $error = true;
                    break;
                }
            }

            if (!$error) {
                return $url;
            }
        }

        if ($controller == 'pagenotfound') {
            return $url;
        }

        $new_url = \Context::getContext()->link->getLanguageLink($id_lang);

        $new_url = preg_replace("/(\??id_shop=\d?)/", "", $new_url);
        $new_url = $new_url . $this->getUrlParameters($url);

        if ($url[0] === '/' && $new_url[0] !== '/') {
            $new_url = '/' . $new_url;
        }

        return $new_url;
    }

    private function getUrlParameters($url)
    {
        $parameters = '';

        if (\Tools::strpos($url, '?') !== false) {
            $parameters = \Tools::substr($url, \Tools::strpos($url, '?'));
        } elseif (\Tools::strpos($url, '#') !== false) {
            $parameters = \Tools::substr($url, \Tools::strpos($url, '#'));
        }

        return $parameters;
    }

    /**
     * @param string $url
     * @return string
     */
    private function getSlug($url)
    {
        if (!preg_match('/.+\..+/', $url)) {
            $url = \Dingedi\PsTranslationsApi\DgUrlTranslation::getHost() . $url;
        }

        if (\Tools::strpos($url, 'http') === false) {
            $url = 'http://' . $url;
        }

        $parts = parse_url($url);
        if ($parts) {
            $slug = '/' . ltrim($parts['path'], '/');
        } else {
            $slug = $url;
        }

        return $slug;
    }

    private function getBaseLink($id_shop = null, $ssl = null, $relative_protocol = false)
    {
        static $force_ssl = null;

        if ($ssl === null) {
            if ($force_ssl === null) {
                $force_ssl = (\Configuration::get('PS_SSL_ENABLED') && \Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }
            $ssl = $force_ssl;
        }

        if (\Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null) {
            $shop = new \Shop($id_shop);
        } else {
            $shop = \Context::getContext()->shop;
        }

        $ssl_enable = \Configuration::get('PS_SSL_ENABLED');

        if ($relative_protocol) {
            $base = '//' . ($ssl && $ssl_enable ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = (($ssl && $ssl_enable) ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);
        }

        return $base . $shop->getBaseURI();
    }
}
