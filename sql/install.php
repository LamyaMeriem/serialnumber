<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = [];

// Table principale pour les numéros de série
$sql[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."serial_numbers` (
    `id_serial` INT(11) NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) NOT NULL,
    `id_product_attribute` INT(11) DEFAULT NULL,
    `id_order_detail` INT(11) DEFAULT NULL,
    `serial_number` VARCHAR(255) NOT NULL,
    `status` ENUM('available', 'assigned', 'shipped', 'deleted') DEFAULT 'available',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `added_by` INT(11) DEFAULT NULL,
    `modified_by` INT(11) DEFAULT NULL,
    `deleted_by` INT(11) DEFAULT NULL,
    `date_added` DATETIME NOT NULL,
    `date_modified` DATETIME DEFAULT NULL,
    `date_deleted` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id_serial`)
) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";

// Table pour l'historique des numéros de série
$sql[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."serial_numbers_history` (
    `id_history` INT(11) NOT NULL AUTO_INCREMENT,
    `id_serial` INT(11) NOT NULL,
    `action` ENUM('added', 'modified', 'deleted', 'assigned', 'shipped') NOT NULL,
    `user_id` INT(11) NOT NULL,
    `date_action` DATETIME NOT NULL,
    `details` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_history`),
    FOREIGN KEY (`id_serial`) REFERENCES `"._DB_PREFIX_."serial_numbers`(`id_serial`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;";

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
