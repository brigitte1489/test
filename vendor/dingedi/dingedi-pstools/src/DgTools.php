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

namespace Dingedi\PsTools;

class DgTools
{

    /**
     * @param string $controller
     * @param array $params
     */
    static function getAdminLink($controller, $params = array())
    {
        $link = \Context::getContext()->link;

        if ($link === null) {
            return '';
        }

        if (DgShopInfos::isPrestaShop17()) {
            $link = $link->getAdminLink($controller, true, [], $params);
        } else {
            $link = $link->getAdminLink($controller, true);

            foreach ($params as $k => $v) {
                $link .= "&" . $k . "=" . $v;
            }
        }

        return $link;
    }

    /**
     * @param array $array
     * @param string $key
     * @param string $value
     * @return array
     * @deprecated
     */
    static function searchArray($array, $key, $value)
    {
        $results = array();
        if (is_array($array)) {
            if (@$array[$key] == $value) {
                $results[] = $array;
            } else {
                foreach ($array as $subarray) {
                    $results = array_merge($results, self::searchArray($subarray, $key, $value));
                }
            }
        }

        return $results;
    }

    /**
     * @param array $array
     * @param string $key
     * @param string $value
     * @return mixed
     * @deprecated
     */
    static function searchSubArray($array, $key, $value)
    {
        foreach ($array as $subarray) {
            if (isset($subarray[$key]) && $subarray[$key] == $value)
                return $subarray;
        }
    }

    /**
     * @param array $data
     * @param string $by_column
     * @return array
     * @deprecated
     */
    static function arrayGroup($data, $by_column)
    {
        $result = array();

        foreach ($data as $item) {
            $column = $item[$by_column];
            if (isset($result[$column])) {
                $result[$column][] = $item;
            } else {
                $result[$column] = array($item);
            }
        }

        return $result;
    }

    /**
     * @param array $datas
     * @param int $responseCode
     * @return string
     */
    static function jsonResponse($datas, $responseCode = 200)
    {
        ob_end_clean();
        header('Content-type: application/json');
        http_response_code($responseCode);
        echo json_encode($datas);
        die();
    }

    static function jsonError($datas)
    {
        return self::jsonResponse($datas, 400);
    }

    /**
     * @param array|int $language
     * @return string
     * @throws \Exception
     */
    static function getLocale($language)
    {
        if (is_int($language)) {
            $language = \Language::getLanguage($language);
        }

        if (isset($language['iso_code'])) {
            return $language['iso_code'];
        }

        if (isset($language['locale'])) {
            if (\Tools::strlen($language['locale']) > 2) {
                return \Tools::substr($language['locale'], 0, 2);
            }

            return $language['locale'];
        }

        throw new \Exception('Error while detecting iso code for language ' . $language['id_lang']);
    }

    /**
     * @param $queryParameters
     * @param $needle
     * @return bool
     * @throws \Dingedi\PsTools\Exception\MissingParametersException
     */
    static function hasParameters($queryParameters, $needle)
    {
        if (!is_array($queryParameters)) {
            $queryParameters = array($queryParameters);
        }

        $missing = array();

        foreach ($needle as $i) {
            if (!array_key_exists($i, $queryParameters)) {
                $missing[] = $i;
            }
        }

        if (!empty($missing)) {
            throw new \Dingedi\PsTools\Exception\MissingParametersException('Some parameters are missing in the query: ' . implode(', ', $missing));
        }

        return true;
    }

    /**
     * @param $iso
     * @return string
     * @deprecated
     */
    static function normalizeLocale($iso)
    {
        $replacements = array(
            'gb' => 'en',
            'nn' => 'no',
            'br' => 'pt',
            'vn' => 'vi',
            'qc' => 'fr',
            'dk' => 'da',
            'si' => 'sl',
            'tw' => 'zh',
            'mx' => 'es',
        );

        if (strlen($iso) > 2) {
            $iso = substr($iso, 0, 2);
        }

        return strtr($iso, $replacements);
    }
}
