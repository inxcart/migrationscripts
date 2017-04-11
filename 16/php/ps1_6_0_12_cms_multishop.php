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
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2014 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PsOneSixMigrator\Db;

function ps1_6_0_12_cms_multishop()
{
    $shops = Db::getInstance()->executeS(
        '
		SELECT `id_shop`
		FROM `'._DB_PREFIX_.'shop`
		'
    );

    $cmsLang = Db::getInstance()->executeS(
        '
		SELECT *
		FROM `'._DB_PREFIX_.'cms_lang`
	'
    );
    foreach ($cmsLang as $value) {
        $data = array();
        $cms = array(
            'id_cms'           => $value['id_cms'],
            'id_lang'          => $value['id_lang'],
            'content'          => pSQL($value['content'], true),
            'link_rewrite'     => pSQL($value['link_rewrite']),
            'meta_title'       => pSQL($value['meta_title']),
            'meta_keywords'    => pSQL($value['meta_keywords']),
            'meta_description' => pSQL($value['meta_description']),
        );
        foreach ($shops as $shop) {
            if ($shop['id_shop'] != 1) {
                $cms['id_shop'] = $shop['id_shop'];
                $data[] = $cms;
            }
        }
        Db::getInstance()->insert('cms_lang', $data);
    }

    $cmsCategoryLang = Db::getInstance()->executeS(
        '
		SELECT *
		FROM `'._DB_PREFIX_.'cms_category_lang`
	'
    );
    foreach ($cmsCategoryLang as $value) {
        $data = array();
        $dataBis = array();

        $cmsCategoryShop = array(
            'id_cms_category' => $value['id_cms_category'],
        );
        $cmsCategory = array(
            'id_cms_category'  => $value['id_cms_category'],
            'id_lang'          => $value['id_lang'],
            'name'             => pSQL($value['name']),
            'description'      => pSQL($value['description']),
            'link_rewrite'     => pSQL($value['link_rewrite']),
            'meta_title'       => pSQL($value['meta_title']),
            'meta_keywords'    => pSQL($value['meta_keywords']),
            'meta_description' => pSQL($value['meta_description']),
        );
        foreach ($shops as $shop) {
            if ($shop['id_shop'] != 1) {
                $cmsCategory['id_shop'] = $shop['id_shop'];
                $data[] = $cmsCategory;
            }
            $cmsCategoryShop['id_shop'] = $shop['id_shop'];
            $dataBis[] = $cmsCategoryShop;
        }
        Db::getInstance()->insert('cms_category_lang', $data, false, true, Db::INSERT_IGNORE);
        Db::getInstance()->insert('cms_category_shop', $dataBis, false, true, Db::INSERT_IGNORE);
    }
}
