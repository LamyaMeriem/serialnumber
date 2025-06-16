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

// Vérifier si l'utilisateur souhaite supprimer les données
if (Configuration::get('SERIALNUMBER_DELETE_ON_UNINSTALL')) {
    // Supprimer les tables dans l'ordre inverse de création (à cause des contraintes)
    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'serial_numbers_history`';
    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'serial_numbers`';
    
    // Supprimer les configurations
    $configurations = [
        'SERIALNUMBER_AUTO_ASSIGN',
        'SERIALNUMBER_ASSIGN_MOMENT',
        'SERIALNUMBER_FORMAT',
        'SERIALNUMBER_ALLOW_DUPLICATES',
        'SERIALNUMBER_INCLUDE_INVOICE',
        'SERIALNUMBER_INCLUDE_DELIVERY',
        'SERIALNUMBER_SHOW_FRONTEND',
        'SERIALNUMBER_LOW_STOCK_ALERT',
        'SERIALNUMBER_LOW_STOCK_THRESHOLD',
        'SERIALNUMBER_DELETE_ON_UNINSTALL',
    ];
    
    foreach ($configurations as $config) {
        Configuration::deleteByName($config);
    }
}

// Exécuter les requêtes SQL
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;