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
 * @copyright Copyright 2021 Â© Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */


namespace Dingedi\PsTranslationsApi\TranslationsProviders;

class DingediTranslateV1 extends AbstractTranslationProvider
{

    public function __construct()
    {
        $this->key = 'dingedi_v1';
        $this->title = 'Dingedi Free Translate';

        parent::__construct();

        $this->api_version = '1';
        $this->max_chars_per_request = 2000000;
        $this->iso = array(
            'en', 'ar', 'zh', 'nl', 'fr', 'de', 'fi', 'hu', 'hi', 'id', 'ga', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'es', 'tr', 'uk', 'vi', 'sv', 'eo', 'az', 'cs', 'sv', 'da'
        );
        $this->excluded_words_wrappers = array('<span translate="no">', '</span>');

        $this->informations = array(
            'registration_url' => "",
        );
    }

    /**
     * @param string $text
     * @param string $isoFrom
     * @param string $isoTo
     * @return mixed
     * @throws \Exception
     */
    public function translate($text, $isoFrom, $isoTo)
    {
        if ($return = parent::translate($text, $isoFrom, $isoTo)) {
            return $return;
        }
        $isoFrom = $this->parseIso($isoFrom);
        $isoTo = $this->parseIso($isoTo);

        $url = 'https://translate.dingedi.com/api/v1/translate';

        if ($this->isText()) {
            if ($this->isMail()) {
                $text = "<pre>" . $text . "</pre>";
            }

            $text = $this->excludeWords($text, true);
        }

        $response = $this->curlRequest($url, array(
            'order_id' => $this->api_key,
            'format'   => 'html',
            'q'        => $text,
            'source'   => $isoFrom,
            'target'   => $isoTo
        ));

        return true;
    }

    /**
     * @param array $response
     * @return mixed
     * @throws \Dingedi\PsTranslationsApi\Exception\TranslationErrorException
     */
    public function response($response)
    {
        $responseCode = (int)$response['code'];

        if ($responseCode != 200) {
            if (!$error = $this->catchErrorCode($responseCode)) {
                if (!empty($response['data']['error'])) {
                    $error = $response['data']['message'];
                } else {
                    $error = print_r($response, true);
                }
            }

            throw new \Dingedi\PsTranslationsApi\Exception\TranslationErrorException($error);
        }

        $responseText = $response['data']['translatedText'];

        if ($this->isText()) {
            $responseText = $this->unexcludeWords($responseText);
            $responseText = strip_tags($responseText);
        }

        return $responseText;
    }
}
