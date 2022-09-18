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

class DgHTMLParser
{

    /** @var \DOMDocument $html */
    private $domDocument;

    /** @var bool $addSurround */
    private $addSurround = false;

    public function __construct($html)
    {
        $this->setDomDocument($html);
    }

    /**
     * @param string $html
     */
    private function setDomDocument($html)
    {
        preg_match("/<\s*html.*>.*<\/\s*html.*>/smi", $html, $hasSurround);

        if (empty($hasSurround)) {
            $this->addSurround = true;
            $html = "<div>" . $html . "</div>";
        }

        $this->domDocument = new \DOMDocument('1.0');

        $xmlEncoding = "<?xml encoding='utf-8'>";

        if (defined('LIBXML_HTML_NODEFDTD') || defined('LIBXML_HTML_NOIMPLIED')) {
            $this->domDocument->loadHTML($xmlEncoding . $html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        } else {
            $this->domDocument->loadHTML($xmlEncoding . $html);
        }
    }

    /**
     * @return array
     */
    public function getTextNodes()
    {
        $xpath = new \DOMXPath($this->domDocument);

        $nodes = array();
        foreach ($xpath->query('//text()') as $node) {
            if (trim($node->nodeValue) !== "" && !in_array($node->parentNode->tagName, array('style', 'script'))) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    public function getHTMLOutput()
    {
        foreach ($this->domDocument->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $this->domDocument->removeChild($item);
            }
        }

        $this->domDocument->encoding = 'UTF-8';

        $html = html_entity_decode($this->domDocument->saveHTML(), ENT_QUOTES | ENT_COMPAT, 'UTF-8');

        if ($this->addSurround) {
            $html = preg_replace('/^<div>/', '', $html);
            $html = preg_replace('/<\/div>$/', '', $html);
        }

        if (!defined('LIBXML_HTML_NODEFDTD') || !defined('LIBXML_HTML_NOIMPLIED')) {
            $toRemove = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';
            if (strpos($html, $toRemove) === 0) {
                $html = substr($html, strlen($toRemove));
            }

            if ($this->addSurround) {
                $html = preg_replace('/^<html><body><div>/', '', $html);
                $html = preg_replace('/<\/div><\/body><\/html>$/', '', $html);
            }
        }

        return trim($html);
    }
}
