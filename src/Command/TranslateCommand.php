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
 * @copyright Copyright 2022 © Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 */

namespace Dingedi\Dgtranslationall\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslateCommand extends Command
{
    protected function configure()
    {
        $this->setName('dgtranslationall:translate')
            ->addOption('from_lang', null, InputOption::VALUE_REQUIRED)
            ->addOption('dest_lang', null, InputOption::VALUE_REQUIRED)
            ->addOption('overwrite', null, InputOption::VALUE_OPTIONAL, 'Overwrite translations. "on" or "off" (default: off)', 'off')
            ->addOption('tables', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (['from_lang', 'dest_lang', 'tables'] as $option) {
            if (empty($input->getOption($option))) {
                throw new \Exception(sprintf('Option --%s must be set.', $option));
            }
        }

        $fromLangId = \Language::getIdByIso($input->getOption('from_lang'));
        $destLangId = \Language::getIdByIso($input->getOption('dest_lang'));

        $tables = $input->getOption('tables');

        if (\Validate::isLoadedObject(new \Language($fromLangId)) === false) {
            throw new \Exception(sprintf('%s is not a valid iso code.', $input->getOption('from_lang')));
        }

        if (\Validate::isLoadedObject(new \Language($destLangId)) === false) {
            throw new \Exception(sprintf('%s is not a valid iso code.', $input->getOption('dest_lang')));
        }

        $overwrite = $input->getOption('overwrite');

        if (!in_array($overwrite, ['on', 'off'])) {
            throw new \Exception(sprintf('Overwrite must be set "on" or "off"', $input->getOption('dest_lang')));
        }

        $overwrite = $overwrite === "on";

        $module = \Module::getInstanceByName('dgtranslationall');
        $module->initContent(true);

        $tables = $this->parseTables($tables);

        foreach ($tables as $tableName => $fields) {
            $table = $module->getContentTable($tableName);

            $requests = ceil($table->getTotalItems() / \Dingedi\PsTranslationsApi\DgTranslationTools::getPerRequest());

            $_POST['translation_data'] = array();

            if (is_array($fields)) {
                $_POST['translation_data']['selected_fields'] = $fields;
            }

            for ($i = 1; $i <= $requests; $i++) {
                $module->translateContentTable($tableName, $fromLangId, $destLangId, 0, $overwrite, $i);
            }
        }

        $output->write('Data has been translated');

        return 0;
    }

    private function parseTables($tables)
    {
        if ($tables === "*") {
            return $this->getAllTablesList();
        }

        $r = [];

        foreach (explode('|', $tables) as $table) {
            $e = explode(':', $table);

            if (isset($e[1])) {
                $r[$e[0]] = explode(',', $e[1]);
            } else {
                $r[$e[0]] = '';
            }
        }

        return $r;
    }

    private function getAllTablesList()
    {
        $tables = [];

        $contentTablesGroups = \Module::getInstanceByName('dgtranslationall')->getContentTables();

        foreach ($contentTablesGroups as $group) {
            foreach ($group['tables'] as $table) {
                $tables[$table->getTableName(false)] = '';
            }
        }

        return $tables;
    }
}
