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

namespace Dingedi\PsTranslationsApi\TranslationsProviders;

abstract class AbstractTranslationProvider implements \JsonSerializable
{
    /** @var string $key */
    public $key;

    /** @var string $title */
    public $title;

    /** @var string $api_key */
    public $api_key;

    /** @var array $excluded_words */
    public $excluded_words = array();

    /** @var array|false $iso */
    public $iso = array();

    /** @var array $iso_replacements */
    public $iso_replacements = array();

    /** @var array excluded_words_wrappers */
    public $excluded_words_wrappers = array();

    /** @var array $errors */
    public $errors = array();

    /** @var string $api_version */
    public $api_version;

    /** @var int $max_chars_per_request */
    public $max_chars_per_request = 5000;

    /** @var array $informations */
    public $informations;

    /** @var string $iso_from */
    public $iso_from;

    /** @var string $iso_to */
    public $iso_to;

    public function __construct()
    {
        $this->api_key = \Dingedi\PsTranslationsApi\DgTranslationTools::getApiKey($this->key);

        if (\Configuration::get('dingedi_exclude') === 'true') {
            $this->excluded_words = array_filter(explode(',', \Configuration::get('dingedi_excluded')));
        }

        $this->iso_replacements = array(
            'ie' => 'ga', // Irish
            'at' => 'de', // Autrish language is German
            'gb' => 'en',
            'vn' => 'vi',
            'si' => 'sl',
            'nn' => 'no',
            'qc' => 'fr',
            'mx' => 'es',
            'br' => 'pt',
            'tw' => 'zh',
            'dk' => 'da'
        );
    }

    public function translate($text, $isoFrom, $isoTo)
    {
        $this->canTranslate($isoFrom, $isoTo);

        if ($this->max_chars_per_request !== null && \Tools::strlen($text) > $this->max_chars_per_request) {
            return $text;
        }

        if ($this->isOnlyExcludedWords($text)) {
            if ($this->isText()) {
                $text = $this->unexcludeWords($text);
                $text = strip_tags($text);
            }

            return $text;
        }
    }

    /**
     * @param $text
     * @return bool
     */
    private function isOnlyExcludedWords($text)
    {
        if ($this->isText()) {
            $text = $this->excludeWords($text, true);
        }

        $excluded = $this->excludeWords($text, false, null, true);

        if (trim($excluded) === "") {
            return true;
        }

        return false;
    }

    public function unexcludeWords($text)
    {
        return $this->excludeWords($text, false);
    }

    private function preg_quote($string)
    {
        $string = preg_quote($string, '/');

        if (\Tools::version_compare(PHP_VERSION, '7.3', '<')) {
            $string = str_replace('#', '\#', $string);
        }

        return $string;
    }

    /**
     * @param string $text
     * @return string
     */
    public function getExcludedWordWrapped($text)
    {
        return $this->excluded_words_wrappers[0] . $text . $this->excluded_words_wrappers[1];
    }

    public function excludeWords($text, $replace, $excludedWords = null, $replaceByEmptyValue = false)
    {
        \Dingedi\PsTranslationsApi\DgSmartDictionary::init(\Dingedi\PsTranslationsApi\DgTranslateApi::getCorrectLanguageId($this->iso_from, $this), \Dingedi\PsTranslationsApi\DgTranslateApi::getCorrectLanguageId($this->iso_to, $this));

        $excluded = array_merge($this->excluded_words, \Dingedi\PsTranslationsApi\DgSmartDictionary::getExclusions());

        if (is_array($excludedWords)) {
            $excluded = array_merge($excluded, $excludedWords);
        }

        if (empty($excluded)) {
            return $text;
        }

        if ($replace === true) {
            usort($excluded, function ($a, $b) {
                return strlen($a) < strlen($b);
            });

            $match = $this->getExcludedWordWrapped("$0");

            $groups = array_chunk($excluded, 150);
            foreach ($groups as $group) {
                $group = array_map(function ($i) {
                    return $this->preg_quote($i);
                }, array_filter($group, function ($e) {
                    return trim($e) !== "";
                }));

                foreach ($group as $k => $word) {
                    preg_match_all('/' . $this->preg_quote(str_replace('$0', $word, $match)) . '/m', $text, $matches, PREG_SET_ORDER);

                    if (!empty($matches) && isset($matches[0])) {
                        unset($group[$k]);
                    }
                }

                if (count($group) >= 1) {
                    $pattern = '/' . '\b' . implode('\b|\b', array_filter($group)) . '\b' . '|\B' . implode('\B|\B', array_filter($group)) . '\B/';
                    $text = preg_replace($pattern, $match, $text);
                }
            }
        } else {
            usort($excluded, function ($a, $b) {
                return strlen($a) > strlen($b);
            });

            foreach ($excluded as $excluded_word) {
                $match = $this->getExcludedWordWrapped($excluded_word);

                $smartDictionaryReplacement = \Dingedi\PsTranslationsApi\DgSmartDictionary::getReplacement($excluded_word);

                if ($smartDictionaryReplacement !== false) {
                    $excluded_word = $smartDictionaryReplacement;
                }

                if ($replaceByEmptyValue === true) {
                    $excluded_word = '';
                }

                $text = str_replace($match, $excluded_word, $text);
            }
        }

        return $text;
    }

    /**
     * @param string $iso_from
     * @param string $iso_to
     * @throws \Dingedi\PsTranslationsApi\Exception\NotSupportedLanguageException
     */
    protected function canTranslate($iso_from, $iso_to)
    {
        if (is_array($this->iso) && !empty($this->iso)) {
            if (!($this->isCompatibleIso($this->parseIso($iso_from)) && $this->isCompatibleIso($this->parseIso($iso_to)))) {
                throw new \Dingedi\PsTranslationsApi\Exception\NotSupportedLanguageException();
            }
        }
    }

    /**
     * @param string $iso
     * @return bool
     */
    public function isCompatibleIso($iso)
    {
        return in_array($iso, $this->iso);
    }

    /**
     * @param string $iso
     * @return mixed|string
     */
    protected function parseIso($iso)
    {
        if (isset($this->iso_replacements[$iso])) {
            return $this->iso_replacements[$iso];
        }

        return $iso;
    }

    /**
     * @param string $url
     * @param string|array $posts
     * @param array $headers
     * @return array
     */
    protected function curlRequest($url, $posts, $headers = array())
    {
        for ($retry = 0; $retry < 3; $retry++) {
            $response = $this->_curlRequest($url, $posts, $headers);

            if (!in_array($response['code'], [0, 504])) {
                break;
            }
        }

        return $response;
    }

    private function _curlRequest($url, $posts, $headers = array())
    {
        $body = http_build_query($posts);
        $handle = curl_init($url);

        $curl_headers = array(
            'Expect: */*',
        );

        if ($body !== false) {
            $curl_headers[] = 'Content-length: ' . \Tools::strlen($body);
        }

        if (empty($headers)) {
            $curl_headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        if (is_array($posts)) {
            $posts = http_build_query($posts);
        }

        if (defined('_PS_BASE_URL_SSL_')) {
            curl_setopt($handle, CURLOPT_REFERER, _PS_BASE_URL_SSL_);
        }

        curl_setopt($handle, CURLOPT_HTTPHEADER, array_merge($headers, $curl_headers));
        curl_setopt($handle, CURLOPT_POSTFIELDS, $posts);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_MAXREDIRS, 15);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($handle);
        $responseDecoded = json_decode($response, true);
        $responseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return array(
            'code' => (int)$responseCode,
            'data' => $responseDecoded
        );
    }

    /**
     * @param string $error_code
     * @return false|string
     */
    protected function catchErrorCode($error_code)
    {
        if (isset($this->errors[$error_code])) {
            return 'API: ' . $this->errors[$error_code];
        }

        return false;
    }

    /**
     * @param string $contentType
     * @return bool
     */
    private function isContentType($contentType)
    {
        $translation_data = \Tools::getValue('translation_data');

        if (!$translation_data) {
            return false;
        }

        return isset($translation_data['content_type']) && $translation_data['content_type'] === $contentType;
    }

    public function isMail()
    {
        $translation_data = \Tools::getValue('translation_data');

        if (!$translation_data) {
            return false;
        }

        return isset($translation_data['mail']) && $translation_data['mail'] === true;
    }

    public function isHtml()
    {
        return $this->isContentType('html');
    }

    public function isText()
    {
        return $this->isContentType('text');
    }

    public function jsonSerialize()
    {
        $iso = array_merge($this->iso, array_keys($this->iso_replacements));

        return array(
            'key'          => $this->key,
            'title'        => $this->title,
            'api_key'      => (string)$this->api_key,
            'api_version'  => $this->api_version,
            'iso'          => $iso,
            'informations' => $this->informations
        );
    }
}
