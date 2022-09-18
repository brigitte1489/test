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

class GoogleTranslateV2 extends AbstractTranslationProvider
{

    public function __construct()
    {
        $this->key = 'google_v2';
        $this->title = 'Google Translate';

        parent::__construct();

        $this->api_version = '2';
        $this->max_chars_per_request = 12000;
        $this->iso = array(
            'af', 'am', 'ar', 'az', 'be', 'bg', 'bn', 'bs', 'ca', 'ceb', 'co', 'cs', 'cy', 'da', 'de', 'el',
            'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'fy', 'ga', 'gd', 'gl', 'gu', 'ha', 'haw', 'hi',
            'hmn', 'hr', 'ht', 'hu', 'hy', 'id', 'ig', 'is', 'it', 'iw', 'ja', 'jw', 'ka', 'kk', 'km', 'kn',
            'ko', 'ku', 'ky', 'la', 'lb', 'lo', 'lt', 'lv', 'mg', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt',
            'my', 'ne', 'nl', 'no', 'ny', 'pa', 'pl', 'ps', 'pt', 'ro', 'ru', 'sd', 'si', 'sk', 'sl', 'sm',
            'sn', 'so', 'sq', 'sr', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'tl', 'tr', 'uk', 'ur',
            'uz', 'vi', 'xh', 'yi', 'yo', 'zh', 'zh-TW', 'he', 'zu',
        );
        $this->iso_replacements['tw'] = 'zh-TW';
        $this->excluded_words_wrappers = array('<span translate="no">', '</span>');

        $this->informations = array(
            'pricing_url'        => "https://cloud.google.com/translate/pricing",
            'registration_url'   => "https://console.cloud.google.com/freetrial/signup",
            'free_monthly_quota' => "500 000",
            'trial_offer'        => array(
                'ammount'         => '300 $',
                'months_duration' => 3
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

        $url = 'https://translation.googleapis.com/language/translate/v2';

        if ($this->isText()) {
            if ($this->isMail()) {
                $text = "<pre>" . $text . "</pre>";
            }

            $text = $this->excludeWords($text, true);
        }

        $response = $this->curlRequest($url, array(
            'key'    => $this->api_key,
            'format' => 'html',
            'q'      => $text,
            'source' => $isoFrom,
            'target' => $isoTo
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
                if (!empty($response['data']['error']['errors'][0]['message'])) {
                    $error = $response['data']['error']['errors'][0]['message'];
                } else if (empty($response['data']['error']['message'])) {
                    $error = $response['data']['error']['message'];
                }else {
                    $error = print_r($response, true);
                }
            }

            throw new \Dingedi\PsTranslationsApi\Exception\TranslationErrorException($error);
        }

        $responseText = $response['data']['data']['translations'][0]['translatedText'];

        if ($this->isText()) {
            $responseText = $this->unexcludeWords($responseText);
            $responseText = strip_tags($responseText);
        }

        return $responseText;
    }
}
