<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2016 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PsOneSixMigrator\Db;

function ps1_6_0_6_module_exceptions()
{
    $modulesDir = scandir(_PS_MODULE_DIR_);
    $modulesControllers = $coreControllers = array();
    $coreControllers = array();

    foreach ($modulesDir as $moduleDir) {
        $modulePath = _PS_MODULE_DIR_.$moduleDir;

        if ($moduleDir[0] == '.' || $moduleDir == 'index.php') {
            continue;
        }

        if (file_exists($modulePath.'/controllers/') && is_dir($modulePath.'/controllers/')) {
            $modulePathAdmin = $modulePath.'/controllers/admin/';
            if (file_exists($modulePathAdmin) && is_dir($modulePathAdmin)) {
                $admin = scandir($modulePathAdmin);
                foreach ($admin as $aController) {
                    if ($aController[0] == '.' || $aController == 'index.php') {
                        continue;
                    }
                    if (isset($modulesControllers[$moduleDir])) {
                        $modulesControllers[$moduleDir][] = str_replace('.php', '', $aController);
                    } else {
                        $modulesControllers[$moduleDir] = array(str_replace('.php', '', $aController));
                    }
                }
            }

            $modulePathFront = $modulePath.'/controllers/front/';
            if (file_exists($modulePathFront) && is_dir($modulePathFront)) {
                $front = scandir($modulePathFront);
                foreach ($front as $fController) {
                    if ($fController[0] == '.' || $fController == 'index.php') {
                        continue;
                    }
                    if (isset($modulesControllers[$moduleDir])) {
                        $modulesControllers[$moduleDir][] = str_replace('.php', '', $fController);
                    } else {
                        $modulesControllers[$moduleDir] = array(str_replace('.php', '', $fController));
                    }
                }
            }
        }
    }

    $controllerDir = _PS_ROOT_DIR_.'/controllers/front/';

    if (file_exists($controllerDir) && is_dir($controllerDir)) {
        $frontControllers = scandir($controllerDir);

        foreach ($frontControllers as $controller) {
            if ($controller[0] == '.' || $controller == 'index.php') {
                continue;
            }
            $coreControllers[] = strtolower(str_replace('Controller.php', '', $controller));
        }
    }

    $hookModuleExceptions = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'hook_module_exceptions`');
    $sqlInsert = 'INSERT INTO `'._DB_PREFIX_.'hook_module_exceptions` (`id_hook_module_exceptions`, `id_shop`, `id_module`, `id_hook`, `file_name`) VALUES ';
    $sqlDelete = 'DELETE FROM `'._DB_PREFIX_.'hook_module_exceptions` WHERE ';

    foreach ($hookModuleExceptions as $exception) {
        foreach ($modulesControllers as $module => $controllers) {
            if (in_array($exception['file_name'], $controllers) && !in_array($exception['file_name'], $coreControllers)) {
                $sqlDelete .= ' `id_hook_module_exceptions` = '.(int) $exception['id_hook_module_exceptions'].' AND';
                foreach ($controllers as $cont) {
                    if ($exception['file_name'] == $cont) {
                        $sqlInsert .= '(null, '.(int) $exception['id_shop'].', '.(int) $exception['id_module'].', '.(int) $exception['id_hook'].', \'module-'.pSQL($module).'-'.pSQL($exception['file_name']).'\'),';
                    }
                }
            }
        }
    }
    Db::getInstance()->execute($sqlInsert);
    Db::getInstance()->execute($sqlDelete);
}
