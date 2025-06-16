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

class AdminSerialNumberSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        // Traitement du formulaire
        if (Tools::isSubmit('submitSerialNumberSettings')) {
            $this->processForm();
        }

        $this->context->smarty->assign('current_tab', $this->controller_name);

        // Ajouter le header commun à toutes les pages du module
        $header = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/header.tpl');
        $this->context->smarty->assign('header', $header);

        // Générer le contenu du formulaire
        $content = $this->renderForm();
        $this->context->smarty->assign('content', $content);

        // Assigner le template de base
        $this->setTemplate('content.tpl');
    }

    /**
     * Génère le formulaire de configuration
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration du Module Serial Number'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Assignation automatique'),
                        'name' => 'SERIALNUMBER_AUTO_ASSIGN',
                        'desc' => $this->l('Activer l\'assignation automatique des numéros de série lors de la validation des commandes'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Activé')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Désactivé')
                            ]
                        ],
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->l('Moment d\'assignation'),
                        'name' => 'SERIALNUMBER_ASSIGN_MOMENT',
                        'desc' => $this->l('Choisir quand assigner les numéros de série'),
                        'values' => [
                            [
                                'id' => 'assign_validation',
                                'value' => 'validation',
                                'label' => $this->l('À la validation de la commande')
                            ],
                            [
                                'id' => 'assign_status',
                                'value' => 'status',
                                'label' => $this->l('Lors du changement de statut')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Format des numéros de série (RegEx)'),
                        'name' => 'SERIALNUMBER_FORMAT',
                        'desc' => $this->l('Expression régulière pour valider les numéros de série. Ex: /^[A-Z0-9]{10}$/'),
                        'size' => 50,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Autoriser les doublons'),
                        'name' => 'SERIALNUMBER_ALLOW_DUPLICATES',
                        'desc' => $this->l('Permettre qu\'un même numéro de série soit utilisé pour des produits différents'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'duplicates_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'duplicates_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher sur la facture PDF'),
                        'name' => 'SERIALNUMBER_INCLUDE_INVOICE',
                        'desc' => $this->l('Inclure les numéros de série sur les factures PDF'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'invoice_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'invoice_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher sur le bon de livraison'),
                        'name' => 'SERIALNUMBER_INCLUDE_DELIVERY',
                        'desc' => $this->l('Inclure les numéros de série sur les bons de livraison'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'delivery_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'delivery_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Afficher dans l\'espace client'),
                        'name' => 'SERIALNUMBER_SHOW_FRONTEND',
                        'desc' => $this->l('Permettre aux clients de voir leurs numéros de série dans leur espace client'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'frontend_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'frontend_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Notifications de stock faible'),
                        'name' => 'SERIALNUMBER_LOW_STOCK_ALERT',
                        'desc' => $this->l('Envoyer des notifications quand le stock de numéros de série est faible'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'alert_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'alert_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Seuil de stock faible'),
                        'name' => 'SERIALNUMBER_LOW_STOCK_THRESHOLD',
                        'desc' => $this->l('Nombre minimum de numéros de série avant alerte'),
                        'class' => 'fixed-width-xs',
                        'suffix' => $this->l('numéros'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Supprimer les données à la désinstallation'),
                        'name' => 'SERIALNUMBER_DELETE_ON_UNINSTALL',
                        'desc' => $this->l('ATTENTION: Supprimer définitivement toutes les données du module lors de la désinstallation'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'delete_on',
                                'value' => 1,
                                'label' => $this->l('Oui')
                            ],
                            [
                                'id' => 'delete_off',
                                'value' => 0,
                                'label' => $this->l('Non')
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'serialnumber_settings';
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = 'id_configuration';
        $helper->submit_action = 'submitSerialNumberSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminSerialNumberSettings', false);
        $helper->token = Tools::getAdminTokenLite('AdminSerialNumberSettings');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Récupère les valeurs actuelles de configuration
     */
    protected function getConfigFormValues()
    {
        return [
            'SERIALNUMBER_AUTO_ASSIGN' => Configuration::get('SERIALNUMBER_AUTO_ASSIGN', true),
            'SERIALNUMBER_ASSIGN_MOMENT' => Configuration::get('SERIALNUMBER_ASSIGN_MOMENT', 'validation'),
            'SERIALNUMBER_FORMAT' => Configuration::get('SERIALNUMBER_FORMAT', '/^[A-Z0-9]{10}$/'),
            'SERIALNUMBER_ALLOW_DUPLICATES' => Configuration::get('SERIALNUMBER_ALLOW_DUPLICATES', false),
            'SERIALNUMBER_INCLUDE_INVOICE' => Configuration::get('SERIALNUMBER_INCLUDE_INVOICE', true),
            'SERIALNUMBER_INCLUDE_DELIVERY' => Configuration::get('SERIALNUMBER_INCLUDE_DELIVERY', true),
            'SERIALNUMBER_SHOW_FRONTEND' => Configuration::get('SERIALNUMBER_SHOW_FRONTEND', false),
            'SERIALNUMBER_LOW_STOCK_ALERT' => Configuration::get('SERIALNUMBER_LOW_STOCK_ALERT', false),
            'SERIALNUMBER_LOW_STOCK_THRESHOLD' => Configuration::get('SERIALNUMBER_LOW_STOCK_THRESHOLD', 5),
            'SERIALNUMBER_DELETE_ON_UNINSTALL' => Configuration::get('SERIALNUMBER_DELETE_ON_UNINSTALL', false),
        ];
    }

    /**
     * Traite la soumission du formulaire
     */
    protected function processForm()
    {
        $form_values = $this->getConfigFormValues();
        $errors = [];

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);
            
            // Validation spécifique pour le format RegEx
            if ($key === 'SERIALNUMBER_FORMAT' && !empty($value)) {
                if (@preg_match($value, '') === false) {
                    $errors[] = $this->l('Le format RegEx spécifié n\'est pas valide.');
                    continue;
                }
            }

            // Validation pour le seuil de stock
            if ($key === 'SERIALNUMBER_LOW_STOCK_THRESHOLD') {
                $value = (int) $value;
                if ($value < 0) {
                    $value = 0;
                }
            }

            Configuration::updateValue($key, $value);
        }

        if (empty($errors)) {
            $this->confirmations[] = $this->l('Configuration mise à jour avec succès.');
            
            // Log de la modification
            SerialNumberHelper::createLog(
                'Configuration du module mise à jour par l\'utilisateur ID: ' . 
                (Context::getContext()->employee ? Context::getContext()->employee->id : 'inconnu'),
                'INFO'
            );
        } else {
            $this->errors = array_merge($this->errors, $errors);
        }
    }
}