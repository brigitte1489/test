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

namespace Dingedi\PsTranslationsApi;

class DgUrlTranslation
{
    static $url_regex = "/(?<!@)\b((?:https?:\/\/(?:.+?\.)?)?(?:__HOST__)(?:\/[A-Za-z0-9\-\._~:\/\?#\[\]@!$&\'\(\)\*\+,;\=]*)?)/m";

    /**
     * @param string $content
     * @param int $id_lang
     * @return string
     */
    static function translateContentUrls($content, $id_lang)
    {
        if (\Configuration::get('PS_REWRITING_SETTINGS') !== "1") {
            return $content;
        }

        $regex = str_replace('__HOST__', self::getHost(), self::$url_regex);

        $urlsToTranslate = array();

        $content = preg_replace_callback($regex, function ($a) use (&$urlsToTranslate) {
            $urlsToTranslate[] = $a[0];
            return md5($a[0]);
        }, $content);

        try {
            if (empty($urlsToTranslate)) {
                return $content;
            }

            $body = http_build_query(array(
                'dingedi_urlstotranslate' => $urlsToTranslate,
                'dingedi_idlang'          => $id_lang,
                'dingedi_secret_key'      => \Configuration::get('dingedi_secret_key')
            ));

            $link = new \Link();
            $url = $link->getModuleLink('dgtranslationall', 'urlstranslation');

            $agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0';

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($handle, CURLOPT_USERAGENT, $agent);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
            $response = curl_exec($handle);
            $responseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            if ($responseCode !== 200) {
                throw new \Exception();
            }

            $urlsTranslated = json_decode($response, true);

            foreach ($urlsTranslated as $k => $v) {
                $content = str_replace(md5($k), $v, $content);

                if (($key = array_search($k, $urlsToTranslate)) !== false) {
                    unset($urlsToTranslate[$key]);
                }
            }
        } catch (\Exception $e) {
        }

        foreach ($urlsToTranslate as $k) {
            $content = str_replace(md5($k), $k, $content);
        }

        return $content;
    }

    /**
     * @param bool $regex
     * @return string|string[]
     */
    public static function getHost($regex = true)
    {
        $host = \Tools::getShopDomain();

        if ($regex === true) {
            $host = str_replace('-', '\-', $host);
            $host = str_replace('.', '\.', $host);
        }

        return $host;
    }
}
