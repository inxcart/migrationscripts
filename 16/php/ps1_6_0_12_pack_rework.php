<?php
/**
 * 2007-2014 PrestaShop
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
 * /ersions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2014 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PsOneSixMigrator\Db;

function ps1_6_0_12_pack_rework()
{
    Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'configuration` (`id_configuration`, `name`, `value`, `date_add`, `date_upd`) VALUES (NULL, "PS_PACK_STOCK_TYPE", "0", NOW(), NOW())');
    $allProductInPack = Db::getInstance()->ExecuteS('SELECT `id_product_item` FROM '._DB_PREFIX_.'pack GROUP BY `id_product_item`');
    foreach ($allProductInPack as $value) {
        Db::getInstance()->execute(
            'UPDATE '._DB_PREFIX_.'pack
		 	SET `id_product_attribute_item` = '.(getDefaultAttribute($value['id_product_item']) ? getDefaultAttribute($value['id_product_item']).' ' : '0 ').'
		 	WHERE `id_product_item` = '.$value['id_product_item']
        );
    }

    $allProductPack = Db::getInstance()->ExecuteS('SELECT `id_product_pack` FROM '._DB_PREFIX_.'pack GROUP BY `id_product_pack`');
    foreach ($allProductPack as $value) {
        $workWithStock = 1;
        $lang = Db::getInstance()->ExecuteS('SELECT value FROM '._DB_PREFIX_.'configuration WHERE `id_shop` = NULL AND `id_shop_group` = NULL AND `name` = "PS_LANG_DEFAULT"');
        $products = getItems($value['id_product_pack']);
        foreach ($products as $product) {
            if ($product != 1) {
                $workWithStock = 0;
                break;
            }
        }
        if ($workWithStock) {
            Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'product SET `pack_stock_type` = 1 WHERE `id_product` = '.(int) $value['id_product_pack']);
        }
    }
}

function getDefaultAttribute($idProduct)
{
    static $combinations = array();

    if (!isset($combinations[$idProduct])) {
        $combinations[$idProduct] = array();
    }
    if (isset($combinations[$idProduct]['default'])) {
        return $combinations[$idProduct]['default'];
    }

    $sql = 'SELECT id_product_attribute
			FROM '._DB_PREFIX_.'product_attribute
			WHERE default_on = 1 AND id_product = '.(int) $idProduct;
    $result = Db::getInstance()->getValue($sql);

    $combinations[$idProduct]['default'] = $result ? $result : ($result = Db::getInstance()->getValue(
        'SELECT id_product_attribute
			FROM '._DB_PREFIX_.'product_attribute
			WHERE id_product = '.(int) $idProduct
    ));

    return $result;
}

function getItems($idProduct)
{
    $result = Db::getInstance()->executeS('SELECT id_product_item, quantity FROM '._DB_PREFIX_.'pack where id_product_pack = '.(int) $idProduct);
    $arrayResult = array();
    foreach ($result as $row) {
        $p = Db::getInstance()->executeS('SELECT `advanced_stock_management` FROM '._DB_PREFIX_.'product WHERE `id_product` = '.(int) $row['id_product_item']);
        $arrayResult[] = $p[0]['advanced_stock_management'];
    }

    return $arrayResult;
}
