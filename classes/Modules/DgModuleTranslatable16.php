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

class DgModuleTranslatable16 extends AbstractModuleTranslationSource
{
    /** @var \AdminTranslationsController $admin_translations_controller */
    public $admin_translations_controller;

    /** @var \ReflectionClass $reflection */
    public $reflection;

    /** @var array $filesArray */
    public $filesArray;

    /**
     * @return array
     * @throws \Exception
     */
    public function getTranslations()
    {
        $filePath = _PS_MODULE_DIR_ . $this->moduleName . '/translations/' . $this->langTo['iso_code'] . '.php';

        if (!file_exists($filePath) || (file_exists($filePath) && trim(\Tools::file_get_contents($filePath)) === "")) {
            file_put_contents($filePath, "<?php\n\nglobal \$_MODULE;\n\$_MODULE = array();\n");
        }

        $this->admin_translations_controller = new \AdminTranslationsController();
        $this->reflection = new \ReflectionClass('AdminTranslationsController');

        $_POST['type'] = 'modules';
        $_POST['iso_code'] = $this->langTo['iso_code'];
        $_POST['theme'] = \Dingedi\PsTools\DgShopInfos::getDefaultTheme();
        $GLOBALS['_MODULES'] = array();

        $this->admin_translations_controller->getInformations();

        $get_all_modules_files_method = $this->reflection->getMethod('getAllModuleFiles');
        $get_all_modules_files_method->setAccessible(true);

        $this->filesArray = $get_all_modules_files_method->invokeArgs(
            $this->admin_translations_controller,
            array(
                [$this->moduleName],
                null,
                $this->langTo['iso_code'],
                true
            )
        );

        $findAndFillTranslationsMethod = $this->reflection->getMethod('findAndFillTranslations');
        $findAndFillTranslationsMethod->setAccessible(true);

        foreach ($this->filesArray as $value) {
            if ($value['module'] !== $this->moduleName) {
                continue;
            }

            $findAndFillTranslationsMethod->invokeArgs(
                $this->admin_translations_controller,
                array(
                    $value['files'],
                    $value['theme'],
                    $value['module'],
                    $value['dir']
                )
            );
        }

        $translations_prop = $this->reflection->getProperty('modules_translations');
        $translations_prop->setAccessible(true);
        $translationsTabs = $translations_prop->getValue($this->admin_translations_controller);

        $translations = array();
        $missing = array();


        foreach ($translationsTabs as $themeName => $theme) {
            foreach ($theme as $moduleName => $module) {
                foreach ($module as $templateName => $string) {
                    foreach ($string as $key => $value) {
                        $encodedKey = \Tools::strtolower($moduleName);

                        if ($themeName) {
                            $encodedKey .= '_' . \Tools::strtolower($themeName);
                        }

                        $encodedKey .= '_' . \Tools::strtolower($templateName);

                        $encodedKey .= '_' . md5($key);
                        $encodedKey = md5($encodedKey);

                        $translations[] = array(
                            'key'   => $encodedKey,
                            'value' => \Tools::stripslashes(html_entity_decode($key, ENT_COMPAT, 'UTF-8')),
                            'trad'  => \Tools::stripslashes(html_entity_decode($value['trad'], ENT_COMPAT, 'UTF-8'))
                        );
                    }
                }
            }
        }

        $isoLangTo = \Dingedi\PsTools\DgTools::getLocale($this->langTo);

        if (!in_array($isoLangTo, array('en', 'gb'))) {
            foreach ($translations as $translation) {
                if (($this->sameTranslations->needTranslation($this->moduleName, $translation['key'], [-1, (int)$this->langTo['id_lang']]) && $translation['trad'] === $translation['value'])
                    || in_array($translation['trad'], array(null, ''))) {
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
    public function translateMissingTranslations($translations, $idLangFrom, $latin = 0)
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
                    'i'     => $this->moduleName,
                    'f'     => $translation['key'],
                    'langs' => array($idLangFrom, (int)$this->langTo['id_lang'])
                ));
            }
        }

        return $this->saveMissingTranslations($translations);
    }

    public function saveMissingTranslations($translations)
    {
        $translationsToSave = array();

        foreach ($this->getTranslations()['all'] as $translation) {
            $translationsToSave[$translation['key']] = str_replace(
                array('\"', "\'"),
                array('"', "'"),
                $translation['trad']);
        }

        foreach ($translations as $translation) {
            $translationsToSave[$translation['key']] = $translation['trad'];
        }

        unset($_POST);

        foreach ($translationsToSave as $k => $v) {
            $_POST[$k] = $v;
        }

        $write_method = $this->reflection->getMethod('findAndWriteTranslationsIntoFile');
        $write_method->setAccessible(true);

        foreach ($this->filesArray as $value) {
            $write_method->invokeArgs($this->admin_translations_controller, array(
                $value['file_name'],
                $value['files'],
                $value['theme'],
                $value['module'],
                $value['dir']
            ));
        }

        if (\Dingedi\PsTools\DgShopInfos::isPrestaShop17()) {
            \Tools::clearAllCache();
        } else {
            \Tools::clearSmartyCache();
        }

        return true;
    }

    public function jsonSerialize()
    {
        $translations = $this->getTranslations();

        $missingTranslationsTotal = count($translations['missing']);
        $translationsTotal = count($translations['all']);

        if ($translationsTotal === 0) {
            $translationsPercent = 100;
        } else {
            $translationsPercent = @(($translationsTotal - $missingTranslationsTotal) / $translationsTotal) * 100;
        }

        return array(
            'type'                       => 'module',
            'name'                       => $this->moduleName,
            'certified'                  => in_array($this->moduleName, DgModulesList::$certified),
            'id_lang'                    => (int)$this->langTo['id_lang'],
            'translations'               => $translations['all'],
            'translations_total'         => $translationsTotal,
            'translations_percent'       => round($translationsPercent, 2),
            'missing_translations_total' => $missingTranslationsTotal,
            'missing_translations'       => $translations['missing']
        );
    }
}
