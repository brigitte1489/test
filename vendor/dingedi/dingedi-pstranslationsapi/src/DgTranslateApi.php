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

class DgTranslateApi
{
    /** @var string $REGEX_DEFAULT elements not to be translated */

    // TODO: improve by excluding elements in html tags
    private static $REGEX_DEFAULT = '/(%[0-9a-zA-Z\_\-]{1,}\$d)|(%[0-9a-zA-Z\_\-]{1,}(%)?)|(\[?\(?\/?{[^}]+}\]?\)?)|(\$[a-zA-Z\_]+)|(\[\/?[0-9a-zA-Z\ ]+\])|(\[.+\])/m';
    private static $REGEX_URLS = "/(?:(?:http(?:s)?:\/\/)?(?:[\w-]+\.)+[\w-]+[.com]+(?:[\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?)/";

    /**
     * @param string $text
     * @param string $isoLangFrom
     * @param string $isoLangTo
     * @param int $latin
     * @return string translated string
     * @throws \Exception
     */
    public static function translate($text, $isoLangFrom, $isoLangTo, $latin)
    {
        if (!function_exists('curl_init')) {
            throw new \Exception('PHP cURL extension is not installed on your server.');
        }

        if (in_array($isoLangFrom, array('en', 'gb')) && in_array($isoLangTo, array('en', 'gb'))) {
            return $text;
        }

        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_COMPAT, 'UTF-8');

        if ($latin === 1 || $latin === 3) {
            $text = \Dingedi\PsTranslationsApi\Transliterations\DgTransliterator::transliterate($isoLangFrom, $text, false);
        }

        $translated = self::translateText($isoLangFrom, $isoLangTo, $text);

        if ($latin === 2 || $latin === 3) {
            $translated = \Dingedi\PsTranslationsApi\Transliterations\DgTransliterator::transliterate($isoLangTo, $translated);
        }

        return $translated;
    }

    /**
     * @param string $isoLangFrom
     * @param string $isoLangTo
     * @param string $text
     * @return string
     */
    private static function translateText($isoLangFrom, $isoLangTo, $text)
    {
        if (\Dingedi\PsTranslationsApi\TranslationRequest::isRegenerateLinksOnly() === false) {
            $isHTML = self::isHTML($text);

            /** @var $provider \Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider */
            $provider = \Dingedi\PsTranslationsApi\DgTranslationTools::getProvider(false);
            $provider->iso_from = $isoLangFrom;
            $provider->iso_to = $isoLangTo;

            $text = self::fixUrlsEncoded($text);

            $exclusions = array();

            // Text
            if (!$isHTML) {
                $_POST['translation_data']['content_type'] = "text";
                $exclusions = array_merge($exclusions, self::getExclusionForUnexceptedElements($text));
            } else {
                // Html
                $_POST['translation_data']['content_type'] = "html";

                $addSurround = false;

                preg_match("/<html [^>]*>.*<\/html>/mis", $text, $hasSurround);

                if (empty($hasSurround)) {
                    $addSurround = true;
                    $text = "<div>" . $text . "</div>";
                }

                libxml_clear_errors();
                libxml_use_internal_errors(true);

                $dom = new \DOMDocument('1.0');
                $xmlEncoding = "<?xml encoding='utf-8'>";

                if (defined('LIBXML_HTML_NODEFDTD') || defined('LIBXML_HTML_NOIMPLIED')) {
                    $dom->loadHTML($xmlEncoding . $text, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
                } else {
                    $dom->loadHTML($xmlEncoding . $text);
                }

                $xpath = new \DOMXPath($dom);

                foreach ($xpath->query('//text()') as $node) {
                    if (trim($node->nodeValue) !== "" && !in_array($node->parentNode->tagName, array('style', 'script'))) {
                        $exclusions = array_merge($exclusions, self::getExclusionForUnexceptedElements($node->nodeValue));
                        $node->nodeValue = $provider->excludeWords($node->nodeValue, true, $exclusions);
                    }
                }

                foreach ($xpath->query('//comment()') as $comment) {
                    $comment->parentNode->removeChild($comment);
                }

                foreach ($dom->childNodes as $item) {
                    if ($item->nodeType == XML_PI_NODE) {
                        $dom->removeChild($item);
                    }
                }

                $dom->encoding = 'UTF-8';
                $text = html_entity_decode($dom->saveHTML(), ENT_QUOTES | ENT_COMPAT, 'UTF-8');
                $text = str_replace('<br>', '<br/>', $text);

                if ($addSurround) {
                    $text = preg_replace('/^<div>/', '', $text);
                    $text = preg_replace('/<\/div>$/', '', $text);
                }
            }

            if ($isHTML) {
                if (!defined('LIBXML_HTML_NODEFDTD') || !defined('LIBXML_HTML_NOIMPLIED')) {
                    $toRemove = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';
                    if (strpos($text, $toRemove) === 0) {
                        $text = substr($text, strlen($toRemove));
                    }

                    if (empty($hasSurround)) {
                        $text = preg_replace('/^<html><body><div>/', '', $text);
                        $text = preg_replace('/<\/div><\/body><\/html>$/', '', $text);
                    }
                }

                $text = self::fixHtmlContentBeforeTranslate($text, $provider);
            } else {
                $text = $provider->excludeWords($text, true, $exclusions);
            }

            if (\Tools::strlen($text) > ($provider->max_chars_per_request * 0.95)) {
                $splitted = self::splitLongHtmlContent($text, ($provider->max_chars_per_request * 0.95));

                $translated = '';

                if (is_array($splitted)) {
                    foreach ($splitted as $part) {
                        $translated .= html_entity_decode($provider->translate($part, $isoLangFrom, $isoLangTo), ENT_QUOTES | ENT_COMPAT, 'UTF-8');
                    }
                } else {
                    // text was not well splitted :(
                    $encoding = "UTF-8";
                    if (function_exists('mb_detect_encoding')) {
                        $encoding = mb_detect_encoding($text);
                    }

                    $dom = new \DOMDocument('1.0');
                    $dom->loadHTML('<?xml encoding="' . $encoding . '">' . $text, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
                    $xpath = new \DOMXPath($dom);
                    foreach ($xpath->query('//text()') as $node) {
                        if (trim($node->nodeValue) && $node->parentNode->tagName !== 'style') {

                            $charsFix = [',', '.', '?', '!'];
                            $start = '';
                            $end = '';

                            foreach ($charsFix as $char) {
                                $arr = [
                                    ' ' . $char . ' ',
                                    ' ' . $char,
                                    $char . ' '
                                ];

                                foreach ($arr as $k) {
                                    if (str_starts_with($node->nodeValue, $k)) {
                                        $start = $k;
                                    }

                                    if (str_ends_with($node->nodeValue, $k)) {
                                        $end = $k;
                                    }
                                }
                            }

                            $spaceStart = strlen($node->nodeValue) - strlen(ltrim($node->nodeValue));
                            $spaceEnd = strlen($node->nodeValue) - strlen(rtrim($node->nodeValue));

                            $firstCharUppercase = $node->nodeValue === ucfirst($node->nodeValue);

                            $exclusions = array_merge($exclusions, self::getExclusionForUnexceptedElements($node->nodeValue));
                            $node->nodeValue = $provider->excludeWords($node->nodeValue, true, $exclusions);
                            $node->nodeValue = html_entity_decode($provider->translate($node->nodeValue, $isoLangFrom, $isoLangTo), ENT_QUOTES | ENT_COMPAT, 'UTF-8');

                            $node->nodeValue = str_repeat(' ', $spaceStart) . trim($node->nodeValue) . str_repeat(' ', $spaceEnd);
                            $node->nodeValue = $start . ltrim(rtrim($node->nodeValue, $end), $start) . $end;

                            if ($firstCharUppercase) {
                                $node->nodeValue = ucfirst($node->nodeValue);
                            }
                        }
                    }

                    foreach ($dom->childNodes as $item) {
                        if ($item->nodeType == XML_PI_NODE) {
                            $dom->removeChild($item);
                        }
                    }

                    $dom->encoding = 'UTF-8';
                    $replace = array(
                        array('%7B', '%7D'),
                        array('{', '}')
                    );

                    $translated = str_replace($replace[0], $replace[1], html_entity_decode($dom->saveHTML()));
                }

                $text = $translated;
            } else {
                $text = html_entity_decode($provider->translate($text, $isoLangFrom, $isoLangTo), ENT_QUOTES | ENT_COMPAT, 'UTF-8');
            }

            if ($isHTML) {
                $text = self::fixHtmlContentAfterTranslate($text, $provider);
            }

            $text = $provider->excludeWords($text, false, $exclusions);
        }

        // Translate shop urls
        $text = \Dingedi\PsTranslationsApi\DgUrlTranslation::translateContentUrls($text, self::getCorrectLanguageId($isoLangTo, $provider));

        return $text;
    }

    private static function splitLongHtmlContent($string, $limit)
    {
        $methods = ['splitLongString2', 'splitLongString'];
        $splitted = false;

        foreach ($methods as $method) {
            $splitted = self::$method($string, $limit);

            if (!is_array($splitted) && !empty($splitted)) {
                $splitted = false;
                continue;
            }

            // check chunks limit
            foreach ($splitted as $v) {
                if (strlen($v) > $limit) {
                    $splitted = false;
                    continue;
                }
            }
            // all chunks are in limit, check to re-assemble
            if (is_array($splitted) && trim($string) !== trim(implode('', $splitted))) {
                $splitted = false;
                continue;
            }

            break;
        }

        return $splitted;
    }

    private static function splitLongString2($text, $limit)
    {
        $tags = array('</div>', '</p>', '</section>');
        $translationsPart = array();
        $tags_copy = $tags;

        foreach ($tags as $k => $v) {
            unset($tags_copy[$k]);
            $tags_copy[] = $v;

            if (\Tools::strpos($text, $v) !== false) {
                $truncated = self::truncateHtml2($v, $text, $limit);
                $translationsPart = $truncated;

                if (is_array($translationsPart)) {
                    break;
                }
            }
        }

        return $translationsPart;
    }

    /**
     * @source https://stackoverflow.com/questions/57108447/how-to-split-html-to-n-parts-with-preserving-the-markup-from-php
     */
    private static function truncateHtml2($del, $string, $limit)
    {
        $parts = array();
        $i = 0;

        foreach (explode($del, $string) as $str) {
            if (trim($str) === "") continue;

            $str = $str . $del;

            if (\Tools::strlen($str) > $limit) {
                return false;
            }

            if (!empty($parts) && isset($parts[$i]) && \Tools::strlen($parts[$i] . $str) > $limit) {
                ++$i;
            }
            if (isset($parts[$i])) {
                $parts[$i] .= $str;
            } else {
                $parts[$i] = $str;
            }
        }

        return $parts;
    }


    private static function splitLongString($string, $limit)
    {
        $splitted = array();
        $count = 0;
        do {
            $truncated = self::truncateHtml($string, $limit);
            $splitted[] = $truncated;
            $string = \Tools::substr($string, \Tools::strlen($truncated));
            $count++;

            if ($count > 1000) {
                return [$string];
            }
        } while ($string);

        return $splitted;
    }

    /**
     * @source https://github.com/urodoz/truncateHTML/blob/master/src/TruncateService.php
     */
    private static function truncateHtml($text, $length = 5000)
    {
        // if the plain text is shorter than the maximum length, return the whole text
        if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        // splits all html-tags to scanable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = 0;
        $open_tags = array();
        $truncate = '';

        foreach ($lines as $line_matchings) {
            // if there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($line_matchings[1])) {
                // if it's an "empty element" with or without xhtml-conform closing slash
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                    // do nothing if tag is a closing tag
                } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                    // delete tag from $open_tags list
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false) {
                        unset($open_tags[$pos]);
                    }
                    // if tag is an opening tag
                } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                    // add tag to the beginning of $open_tags list
                    array_unshift($open_tags, strtolower($tag_matchings[1]));
                }
                // add html-tag to $truncate'd text
                $truncate .= $line_matchings[1];
            }
            // calculate the length of the plain text part of the line; handle entities as one character
            $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length + $content_length > $length) {
                // the number of characters which are left
                $left = $length - $total_length;
                $entities_length = 0;
                // search for html entities
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                    // calculate the real length of all entities in the legal range
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entities_length <= $left) {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        } else {
                            // no more characters left
                            break;
                        }
                    }
                }
                $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                // maximum lenght is reached, so get off the loop
                break;
            } else {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            // if the maximum length is reached, get off the loop
            if ($content_length >= $length) {
                break;
            }
        }

        // if the words shouldn't be cut in the middle, search the last occurance of a space...
        $spacepos = strrpos($truncate, ' ');
        if (isset($spacepos)) {
            // ...and cut the text in this position
            $truncate = substr($truncate, 0, $spacepos);
        }

        return $truncate;
    }

    private static function getExclusionForUnexceptedElements($text)
    {
        preg_match_all(self::$REGEX_DEFAULT, $text, $matches, PREG_SET_ORDER, 0);

        $tempExcluded = array();

        if (!empty($matches)) {
            $tempExcluded = array_unique(call_user_func_array('array_merge', $matches));
        }

        return array_map(function ($e) {
            return trim($e);
        }, array_filter($tempExcluded));
    }

    private static function fixUrlsEncoded($text)
    {
        return preg_replace_callback(self::$REGEX_URLS, function ($elem) {
            return urldecode($elem[0]);
        }, $text);
    }

    /**
     * @param string $text
     * @param \Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider $provider
     * @return string
     */
    private static function fixHtmlContentBeforeTranslate($text, $provider)
    {
        $text = str_replace('<title>', '<data-title>', $text);
        $text = str_replace('</title>', '</data-title>', $text);


        $replaces = array(
            "%7B" => "{",
            "%7D" => "}",
            "%24" => "$",
            "<title>" => "</title>",
            "<data-title>" => "</data-title>",
            "\xc2\xa0" => $provider->getExcludedWordWrapped("\xc2\xa0")
        );


        return str_replace(array_keys($replaces), array_values($replaces), $text);
    }

    /**
     * @param string $text
     * @param \Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider $provider
     * @return string
     */
    private static function fixHtmlContentAfterTranslate($text, $provider)
    {
        $replaces = array(
            "<data-title>" => "<title>",
            "</data-title>" => "</title>",
            $provider->getExcludedWordWrapped("\xc2\xa0") => "\xc2\xa0"
        );

        return str_replace(array_keys($replaces), array_values($replaces), $text);
    }

    /**
     * @param $text
     * @return bool
     */
    private static function isHTML($text)
    {
        return $text !== strip_tags($text);
    }

    /**
     * Reverse iso replacement
     *
     * @param string $isoLangTo
     * @param \Dingedi\PsTranslationsApi\TranslationsProviders\AbstractTranslationProvider $provider
     * @return int
     */
    public static function getCorrectLanguageId($isoLangTo, $provider)
    {
        $id_lang = \Language::getIdByIso($isoLangTo);
        if ($id_lang === false) {
            $search = array_search($isoLangTo, array_values($provider->iso_replacements));

            if ($search !== false) {
                $id_lang = \Language::getIdByIso(array_keys($provider->iso_replacements)[$search]);
            }
        }

        return (int)$id_lang;
    }
}
