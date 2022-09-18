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


class Abstract_Ybc_blog extends \Dingedi\TablesTranslation\DgTableTranslatable17
{
    public $module = 'ybc_blog';
    public $controller = 'AdminModules';

    public function supportController($controller)
    {
        $type = preg_replace("/^id_/m", "", $this->primary_key);

        return \Tools::getValue('control') === $type && parent::supportController($controller);
    }
}
