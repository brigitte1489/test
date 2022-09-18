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
 * @copyright Copyright 2021 © Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */


namespace Dingedi\PsTranslationsApi\TranslationsProviders;

class YandexTranslateV15 extends AbstractTranslationProvider
{

    public function __construct()
    {
        $this->key = 'yandex_v15';
        $this->title = 'Yandex Translate';

        parent::__construct();

        $this->api_version = '1.5';
        $this->max_chars_per_request = 10000;
        $this->iso = array(
            "az", "sq", "am", "en", "ar", "hy", "af", "eu", "ba", "be", "bn", "my", "bg", "bs", "cy", "hu", "vi", "ht", "gl", "nl", "mrj", "el", "ka", "gu", "da", "he", "yi", "id", "ga", "it", "is", "es", "kk", "kn", "ca", "ky", "zh", "ko", "xh", "km", "lo", "la", "lv", "lt", "lb", "mg", "ms", "ml", "mt", "mk", "mi", "mr", "mhr", "mn", "de", "ne", "no", "pa", "pap", "fa", "pl", "pt", "ro", "ru", "ceb", "sr", "si", "sk", "sl", "sw", "su", "tg", "th", "tl", "ta", "tt", "te", "tr", "udm", "uz", "uk", "ur", "fi", "fr", "hi", "hr", "cs", "sv", "gd", "et", "eo", "jv", "ja"
        );

        $this->excluded_words_wrappers = array("<span translate='no'>", "</span>");

        $this->errors = array(
            '200' => 'The operation was completed successfully',
            '401' => 'Invalid API key',
            '402' => 'The API key is blocked',
            '404' => 'The daily limit on the amount of translated text is exceeded',
            '413' => 'The maximum allowed text size is exceeded',
            '422' => 'The text can\'t be translated',
            '501' => 'The specified translation direction isn\'t supported',
        );

        $this->informations = array(
            'pricing_url'      => "https://translate.yandex.com/developers/prices",
            'registration_url' => "https://translate.yandex.com/developers",
            'trial_offer'      => array(
                'ammount' => '75 $'
            )
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

        $url = "https://translate.yandex.net/api/v1.5/tr.json/translate";
        $url .= "?key=" . $this->api_key . "&lang=" . $isoFrom . '-' . $isoTo . "&format=html";

        $response = $this->curlRequest($url, array(
            'text' => $this->excludeWords($text, true)
        ));

        return $this->response($response);
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
                if (!empty($response['data']['message'])) {
                    $error = $response['data']['message'];
                } else {
                    $error = print_r($response, true);
                }
            }

            throw new \Dingedi\PsTranslationsApi\Exception\TranslationErrorException($error);
        }

        return $this->unexcludeWords($response['data']['text'][0]);
    }
}
