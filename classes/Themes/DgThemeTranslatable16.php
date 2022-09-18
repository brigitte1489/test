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

class DgThemeTranslatable16 extends AbstractThemeTranslationSource
{
    /** @var string */
    public $themeName;

    /** @var array $language */
    public $language;

    /** @var array $translations */
    public $translations;

    /** @var \Dingedi\PsTranslationsApi\DgSameTranslations $sameTranslations */
    public $sameTranslations;

    /**
     * @return array
     * @throws \Exception
     */
    public function getTranslations()
    {
        $_POST['type'] = 'front';
        $_POST['theme'] = $this->themeName;
        $_POST['iso_code'] = $this->langTo['iso_code'];

        $admin_translations_obj = new \AdminTranslationsController();
        $admin_translations_obj->getInformations();
        $admin_translations_obj->initContent();

        $translationsTabs = $admin_translations_obj->tpl_view_vars['tabsArray'];

        $translations = array();
        $missing = array();

        foreach ($translationsTabs as $tk => $translationsTab) {
            foreach ($translationsTab as $key => $value) {
                $encodedKey = \Tools::strtolower($tk) . '_' . md5($key);
                $translations[] = array(
                    'key'   => $encodedKey,
                    'value' => \Tools::stripslashes(html_entity_decode($key, ENT_COMPAT, 'UTF-8')),
                    'trad'  => \Tools::stripslashes(html_entity_decode($value['trad'], ENT_COMPAT, 'UTF-8'))
                );
            }
        }

        $isoLangTo = \Dingedi\PsTools\DgTools::getLocale($this->langTo);

        if (!in_array($isoLangTo, array('en', 'gb'))) {
            foreach ($translations as $translation) {
                if (($this->sameTranslations->needTranslation($this->themeName, $translation['key'], [-1, (int)$this->langTo['id_lang']]) && $translation['trad'] === $translation['value'])
                    || in_array($translation['trad'], array(null, ''))
                ) {
                    $missing[] = $translation;
                }
            }
        }

        return array(
            'all'     => $translations,
            'missing' => $missing
        );
    }

    /**
     * @param array $translations
     * @param int $idLangFrom
     * @param int $latin
     * @return bool
     * @throws \Exception
     */
    public function translateMissingTranslations($translations, $idLangFrom = -1, $latin = 0)
    {
        if ($idLangFrom === -1) {
            $isoLangFrom = 'en';
        } else {
            $isoLangFrom = \Dingedi\PsTools\DgTools::getLocale(\Dingedi\PsTranslationsApi\DgTranslationTools::getLanguage($idLangFrom));
        }

        $isoLangTo = \Dingedi\PsTools\DgTools::getLocale($this->langTo);

        foreach ($translations as &$translation) {
            if (in_array($isoLangFrom, array('en', 'gb')) && in_array($isoLangTo, array('en', 'gb'))) {
                $translated = $translation['value'];
            } else {
                $translated = \Dingedi\PsTranslationsApi\DgTranslateApi::translate($translation['value'], $isoLangFrom, $isoLangTo, $latin);
            }

            $translation['trad'] = $translated;

            if ($translated === $translation['value']) {
                $this->sameTranslations->addTranslations(array(
                    'i'     => $this->themeName,
                    'f'     => $translation['key'],
                    'langs' => array($idLangFrom, (int)$this->langTo['id_lang'])
                ));
            }
        }

        return $this->saveMissingTranslations($translations);
    }

    /**
     * @param $translations
     * @return bool
     * @throws \Exception
     */
    public function saveMissingTranslations($translations)
    {
        $translationsToSave = array();

        foreach ($this->getTranslations()['all'] as $translation) {
            $translationsToSave[$translation['key']] = $translation['trad'];
        }

        foreach ($translations as $translation) {
            $translationsToSave[$translation['key']] = $translation['trad'];
        }

        $file_path = _PS_ALL_THEMES_DIR_ . $this->themeName . '/lang/' . $this->langTo['iso_code'] . '.php';

        return $this->writeTranslationFile($file_path, '_LANG', $translationsToSave);
    }

    /**
     * @source AdminTranslationsController::writeTranslationFile
     * @param $file_path
     * @param $tab
     * @param $translations
     * @return bool
     * @throws \Exception
     */
    private function writeTranslationFile($file_path, $tab, $translations)
    {
        if ($file_path && !file_exists($file_path)) {
            if (!file_exists(dirname($file_path)) && !mkdir(dirname($file_path), 0777, true)) {
                throw new \Exception(sprintf('Directory "%s" cannot be created', dirname($file_path)));
            } elseif (!touch($file_path)) {
                throw new \Exception(sprintf(\Tools::displayError('File "%s" cannot be created'), $file_path));
            }
        }

        if ($fd = fopen($file_path, 'w')) {
            fwrite($fd, "<?php\n\nglobal \$" . $tab . ";\n\$" . $tab . " = array();\n");

            foreach ($translations as $key => $value) {
                fwrite($fd, '$' . $tab . '[\'' . \pSQL($key, true) . '\'] = \'' . \pSQL($value, true) . '\';' . "\n");
            }

            fwrite($fd, "\n?>");
            fflush($fd);
            ftruncate($fd, ftell($fd));
            fclose($fd);
        } else {
            throw new \Exception(sprintf(\Tools::displayError('Cannot write this file: "%s"'), $file_path));
        }

        return true;
    }

    public function jsonSerialize()
    {
        $translations = $this->getTranslations();

        $missingTranslationsTotal = count($translations['missing']);
        $translationsTotal = count($translations['all']);

        $translationsPercent = @(($translationsTotal - $missingTranslationsTotal) / $translationsTotal) * 100;

        return array(
            'type'                       => 'theme',
            'is_default'                 => $this->themeName === \Dingedi\PsTools\DgShopInfos::getDefaultTheme(),
            'name'                       => $this->themeName,
            'translations'               => $translations['all'],
            'translations_total'         => $translationsTotal,
            'translations_percent'       => round($translationsPercent, 2),
            'missing_translations_total' => $missingTranslationsTotal,
            'missing_translations'       => $translations['missing']
        );
    }
}
