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
require_once _PS_MODULE_DIR_ . 'serialnumber/classes/SerialNumberHelper.php';

class AdminSerialNumberOrderController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        // Définir le nom de l'onglet courant
        $this->context->smarty->assign('current_tab', $this->controller_name);

        // Ajouter le header commun à toutes les pages du module
        $header = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/header.tpl');
        $this->context->smarty->assign('header', $header);

        // Assigner le template de base si aucune action spécifique n'est définie
        $this->setTemplate('content.tpl');
    }

    public function renderList()
    {
        // Exemple d'utilisation de la méthode partagée pour valider un numéro
        $isValid = SerialNumberHelper::validateSerialNumber('ABC123XYZ9');
        return $this->displayInformation('Le numéro de série est ' . ($isValid ? 'valide' : 'non valide') . '.');
    }
}