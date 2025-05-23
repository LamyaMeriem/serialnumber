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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

// Assurez-vous d'inclure le fichier d'aide pour les numéros de série
require_once _PS_MODULE_DIR_ . 'serialnumber/classes/SerialNumberHelper.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class SerialNumber extends Module
{
    /**
     * @var string Nom du chemin du module
     */
    protected $module_path;

    public function __construct()
    {
        $this->name = 'serialnumber';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Mr-dev';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true; // Active le style Bootstrap pour le back-office

        parent::__construct();

        $this->displayName = $this->l('Serial Number Management');
        $this->description = $this->l('Manages serial numbers for products, including IMEI.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? All serial number data will be lost.');

        // Définit le chemin du module pour un accès facile aux fichiers de template et JS/CSS
        $this->module_path = $this->getLocalPath();
    }

    /**
     * Méthode d'installation du module.
     * Enregistre les hooks et installe les tables de la base de données.
     *
     * @return bool
     */
    public function install()
    {
        // Appelle la méthode d'installation parente et enregistre les hooks nécessaires
        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')       // Pour ajouter JS/CSS dans le back-office
            && $this->registerHook('actionValidateOrder')           // Pour attribuer les numéros de série lors de la validation de commande
            && $this->registerHook('displayAdminOrder')             // Pour afficher les numéros de série dans la page de commande du back-office
            && $this->registerHook('actionProductUpdate')           // Pour synchroniser le stock lors de la mise à jour d'un produit
            && $this->registerHook('actionProductAttributeUpdate')  // Pour synchroniser le stock lors de la mise à jour d'une déclinaison
            && $this->registerHook('displayAdminProductsExtra')     // Pour ajouter un onglet sur la page produit (gestion SN par produit)
            && $this->registerHook('actionObjectProductDeleteAfter') // Pour gérer les SN lors de la suppression d'un produit
            && $this->registerHook('actionObjectProductAttributeDeleteAfter') // Pour gérer les SN lors de la suppression d'une déclinaison
            && $this->registerHook('displayPDFInvoice')             // Pour inclure les numéros de série sur la facture PDF
            && $this->registerTab()                                 // Enregistre les onglets du back-office
            && $this->installDB();                                  // Installe les tables de la base de données
    }

    /**
     * Méthode de désinstallation du module.
     * Désenregistre les hooks et désinstalle les tables de la base de données.
     *
     * @return bool
     */
    public function uninstall()
    {
        // Appelle la méthode de désinstallation parente et désenregistre les hooks
        return parent::uninstall()
            && $this->unregisterTab()  // Désenregistre les onglets du back-office
            && $this->uninstallDB();   // Désinstalle les tables de la base de données
    }

    /**
     * Enregistre les onglets du back-office pour le module.
     *
     * @return bool
     */
    private function registerTab()
    {
        // Création de l'onglet parent "Serial Number"
        $tab = new Tab();
        $tab->class_name = 'AdminSerialNumber';
        $tab->id_parent = 0; // Onglet de niveau supérieur
        $tab->module = $this->name;
        $tab->active = true;
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Serial Number');
        }
        if (!$tab->add()) {
            return false;
        }

        $parentTabId = (int) Tab::getIdFromClassName('AdminSerialNumber');

        // Création des sous-onglets
        $subTabs = [
            [
                'class_name' => 'AdminSerialNumberSettings',
                'name' => $this->l('General Settings'),
            ],
            [
                'class_name' => 'AdminSerialNumberOrder',
                'name' => $this->l('Orders'),
            ],
            [
                'class_name' => 'AdminSerialNumberProductManagement', // Renommé pour plus de clarté
                'name' => $this->l('Products'),
            ],
            [
                'class_name' => 'AdminSerialNumbersList', // Pour la liste globale des numéros de série
                'name' => $this->l('Serial Numbers List'),
            ],
            [
                'class_name' => 'AdminSerialNumberHistory', // Pour l'historique des actions sur les SN
                'name' => $this->l('History'),
            ],
        ];

        foreach ($subTabs as $subTabInfo) {
            $subTab = new Tab();
            $subTab->class_name = $subTabInfo['class_name'];
            $subTab->id_parent = $parentTabId;
            $subTab->module = $this->name;
            $subTab->active = true;
            foreach (Language::getLanguages(true) as $lang) {
                $subTab->name[$lang['id_lang']] = $subTabInfo['name'];
            }
            if (!$subTab->add()) {
                // Si l'ajout d'un sous-onglet échoue, on tente de désinstaller les onglets déjà ajoutés
                $this->unregisterTab();
                return false;
            }
        }

        return true;
    }

    /**
     * Désenregistre les onglets du back-office du module.
     *
     * @return bool
     */
    private function unregisterTab()
    {
        // Liste de tous les onglets à supprimer (sous-onglets et onglet parent)
        $tabsToDelete = [
            'AdminSerialNumberSettings',
            'AdminSerialNumberOrder',
            'AdminSerialNumberProductManagement',
            'AdminSerialNumbersList',
            'AdminSerialNumberHistory',
            'AdminSerialNumber',
        ];

        foreach ($tabsToDelete as $tabClassName) {
            $id_tab = (int) Tab::getIdFromClassName($tabClassName);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                try {
                    $tab->delete();
                } catch (Exception $e) {
                    // Log l'erreur si la suppression échoue, mais continue pour les autres onglets
                    SerialNumberHelper::createLog(
                        'Failed to delete tab ' . $tabClassName . ': ' . $e->getMessage(),
                        'ERROR'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Exécute les requêtes SQL d'installation.
     *
     * @return bool
     */
    private function installDB()
    {
        // Inclut le fichier SQL d'installation
        return (bool) include(dirname(__FILE__) . '/sql/install.php');
    }

    /**
     * Exécute les requêtes SQL de désinstallation.
     *
     * @return bool
     */
    private function uninstallDB()
    {
        // Inclut le fichier SQL de désinstallation
        return (bool) include(dirname(__FILE__) . '/sql/uninstall.php');
    }

    /**
     * Affiche le contenu de la page de configuration du module.
     * Redirige vers le contrôleur de paramètres.
     *
     * @return string HTML du formulaire de configuration
     */
    public function getContent()
    {
        // Redirige vers le contrôleur de paramètres pour afficher le formulaire de configuration
        // C'est une bonne pratique de déléguer la gestion du formulaire à un contrôleur dédié.
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminSerialNumberSettings')
        );
        // Cette ligne ne sera normalement pas atteinte, mais est incluse pour la conformité.
        return '';
    }

    /**
     * Hook exécuté dans l'en-tête du back-office.
     * Ajoute les fichiers JS et CSS nécessaires au module.
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Vérifie si le contrôleur actuel est l'un de ceux du module pour charger les assets
        $controllerName = Tools::getValue('controller');
        if (
            in_array($controllerName, [
                'AdminSerialNumberSettings',
                'AdminSerialNumberOrder',
                'AdminSerialNumberProductManagement', // Nouveau nom
                'AdminSerialNumbersList',
                'AdminSerialNumberHistory',
                'AdminProducts', // Pour l'intégration dans la page produit
            ])
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/serialnumber.js');
            $this->context->controller->addCSS($this->_path . 'views/css/serialnumber.css');
        }
    }

    /**
     * Hook exécuté après la validation d'une commande.
     * Attribue les numéros de série aux produits commandés si l'option est activée.
     *
     * @param array $params Paramètres du hook, incluant l'objet Order.
     */
    public function hookActionValidateOrder($params)
    {
        // Vérifie si l'attribution automatique est activée dans les paramètres du module
        if (!(bool) Configuration::get('SERIALNUMBER_AUTO_ASSIGN')) {
            SerialNumberHelper::createLog('Automatic serial number assignment is disabled. Skipping for Order ID: ' . (int) $params['order']->id, 'INFO');
            return;
        }

        /** @var Order $order */
        $order = $params['order'];

        if (!Validate::isLoadedObject($order)) {
            SerialNumberHelper::createLog('Hook actionValidateOrder: Invalid Order object received.', 'ERROR');
            return;
        }

        $id_order = (int) $order->id;
        SerialNumberHelper::createLog('Starting serial number assignment for Order ID: ' . $id_order, 'INFO');

        // Récupération des détails de la commande
        $orderDetails = $order->getProductsDetail();

        if (empty($orderDetails)) {
            SerialNumberHelper::createLog('No product details found for Order ID: ' . $id_order, 'WARNING');
            return;
        }

        foreach ($orderDetails as $detail) {
            $id_order_detail = (int) $detail['id_order_detail'];
            $id_product = (int) $detail['product_id'];
            $id_product_attribute = (int) $detail['product_attribute_id'];
            $product_quantity = (int) $detail['product_quantity'];

            // Vérifier si le produit nécessite un numéro de série (peut être une option à ajouter au produit)
            // Pour l'instant, on suppose que tous les produits peuvent avoir des numéros de série.
            // Si vous avez un flag dans la table produit, vous pouvez l'ajouter ici:
            // $productNeedsSerial = (bool) Db::getInstance()->getValue('SELECT `has_serial_number` FROM `'._DB_PREFIX_.'product` WHERE `id_product` = '.(int)$id_product);
            // if (!$productNeedsSerial) { continue; }

            SerialNumberHelper::createLog(
                sprintf(
                    'Processing product ID: %d (Attribute ID: %d) - Quantity: %d for Order Detail ID: %d',
                    $id_product,
                    $id_product_attribute,
                    $product_quantity,
                    $id_order_detail
                ),
                'DEBUG'
            );

            // Tente d'attribuer les numéros de série pour chaque produit dans la commande
            $assignedCount = SerialNumberHelper::assignSerialNumberToOrder($id_order_detail);

            if ($assignedCount === false) {
                SerialNumberHelper::createLog(
                    sprintf(
                        'Failed to assign serial numbers for product ID: %d (Attribute ID: %d) in Order Detail ID: %d',
                        $id_product,
                        $id_product_attribute,
                        $id_order_detail
                    ),
                    'ERROR'
                );
            } elseif ($assignedCount < $product_quantity) {
                SerialNumberHelper::createLog(
                    sprintf(
                        'Only %d out of %d serial numbers assigned for product ID: %d (Attribute ID: %d) in Order Detail ID: %d. Check availability.',
                        $assignedCount,
                        $product_quantity,
                        $id_product,
                        $id_product_attribute,
                        $id_order_detail
                    ),
                    'WARNING'
                );
            } else {
                SerialNumberHelper::createLog(
                    sprintf(
                        '%d serial numbers successfully assigned for product ID: %d (Attribute ID: %d) in Order Detail ID: %d.',
                        $assignedCount,
                        $id_product,
                        $id_product_attribute,
                        $id_order_detail
                    ),
                    'INFO'
                );
            }
        }

        SerialNumberHelper::createLog('Finished serial number assignment for Order ID: ' . $id_order, 'INFO');
    }

    /**
     * Hook pour afficher les numéros de série dans la page de commande du back-office.
     *
     * @param array $params Paramètres du hook, incluant l'ID de la commande.
     * @return string HTML à afficher dans la page de commande.
     */
    public function hookDisplayAdminOrder($params)
    {
        $id_order = (int) $params['id_order'];

        // Récupérer les numéros de série associés à la commande
        $serialNumbers = Db::getInstance()->executeS('
            SELECT
                sn.id_serial,
                sn.serial_number,
                sn.status,
                od.product_name,
                od.product_attribute_id,
                od.product_quantity
            FROM ' . _DB_PREFIX_ . 'serial_numbers sn
            INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON sn.id_order_detail = od.id_order_detail
            WHERE od.id_order = ' . $id_order . '
            AND sn.deleted = 0
            ORDER BY od.product_name ASC, sn.serial_number ASC
        ');

        // Préparer les données pour le template, en ajoutant les noms d'attributs si nécessaire
        if (!empty($serialNumbers)) {
            foreach ($serialNumbers as &$serial) {
                $serial['display_name'] = $serial['product_name'];
                if ((int) $serial['product_attribute_id'] > 0) {
                    $productAttributeName = Product::getCombinationAttributesNames(
                        (int) $serial['product_attribute_id'],
                        $this->context->language->id,
                        false
                    );
                    if (!empty($productAttributeName)) {
                        $attributeNames = array_column($productAttributeName, 'attribute_name');
                        $serial['display_name'] .= ' - (' . implode(', ', $attributeNames) . ')';
                    }
                }
            }
        }

        // Assigner les données à Smarty et rendre le template
        $this->context->smarty->assign([
            'serialNumbers' => $serialNumbers,
            'id_order' => $id_order,
            'module_dir' => $this->_path, // Utile pour les assets dans le template
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_order_serial_numbers.tpl');
    }

    /**
     * Hook exécuté après la mise à jour d'un produit.
     * Synchronise la quantité de stock disponible pour ce produit.
     *
     * @param array $params Paramètres du hook, incluant l'ID du produit.
     */
    public function hookActionProductUpdate($params)
    {
        $id_product = (int) $params['id_product'];
        if ($id_product > 0) {
            // Synchronise le stock pour le produit principal et toutes ses déclinaisons
            // On peut appeler synchronizeStock sans id_product_attribute pour le produit principal
            // ou itérer sur toutes les déclinaisons si nécessaire.
            // Pour l'instant, on s'appuie sur la logique de synchronizeStock qui gère les deux.
            SerialNumberHelper::synchronizeStock($id_product);
            SerialNumberHelper::createLog(
                'Stock synchronized for product ID: ' . $id_product . ' after product update.',
                'INFO'
            );
        }
    }

    /**
     * Hook exécuté après la mise à jour d'une déclinaison de produit.
     * Synchronise la quantité de stock disponible pour cette déclinaison.
     *
     * @param array $params Paramètres du hook, incluant l'ID de la déclinaison.
     */
    public function hookActionProductAttributeUpdate($params)
    {
        $id_product = (int) $params['id_product'];
        $id_product_attribute = (int) $params['id_product_attribute'];

        if ($id_product > 0) { // id_product est toujours présent, id_product_attribute peut être 0 si c'est la déclinaison par défaut
            SerialNumberHelper::synchronizeStock($id_product, $id_product_attribute);
            SerialNumberHelper::createLog(
                'Stock synchronized for product ID: ' . $id_product . ' (Attribute ID: ' . $id_product_attribute . ') after attribute update.',
                'INFO'
            );
        }
    }

    /**
     * Hook pour ajouter un onglet ou un bloc sur la page d'édition d'un produit dans le back-office.
     *
     * @param array $params Contient l'ID du produit.
     * @return string HTML du contenu à afficher.
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) $params['id_product'];

        // Lien vers le contrôleur de gestion des numéros de série pour ce produit
        $link = $this->context->link->getAdminLink('AdminSerialNumberProductManagement', true);

        $this->context->smarty->assign([
            'id_product' => $id_product,
            'link_to_serial_management' => $link . '&id_product=' . $id_product,
            'module_name' => $this->name,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_product_extra_tab.tpl');
    }

    /**
     * Hook exécuté après la suppression d'un objet Product.
     * Marque les numéros de série associés comme supprimés.
     *
     * @param array $params Contient l'objet Product.
     */
    public function hookActionObjectProductDeleteAfter($params)
    {
        /** @var Product $product */
        $product = $params['object'];

        if (!Validate::isLoadedObject($product)) {
            SerialNumberHelper::createLog('Hook actionObjectProductDeleteAfter: Invalid Product object received.', 'ERROR');
            return;
        }

        $id_product = (int) $product->id;

        // Récupérer tous les numéros de série associés à ce produit
        $serialNumbers = SerialNumberHelper::getSerialNumbersByProduct($id_product, null, true); // Inclure les supprimés pour s'assurer de tout marquer

        if (empty($serialNumbers)) {
            SerialNumberHelper::createLog('No serial numbers found for deleted product ID: ' . $id_product, 'INFO');
            return;
        }

        foreach ($serialNumbers as $snData) {
            $serialNumber = new SerialNumberHelper((int) $snData['id_serial']);
            if (Validate::isLoadedObject($serialNumber)) {
                // Marquer comme supprimé et mettre à jour le statut
                $serialNumber->deleted = 1;
                $serialNumber->date_deleted = date('Y-m-d H:i:s');
                $serialNumber->status = 'deleted';
                if (Context::getContext()->employee) {
                    $serialNumber->deleted_by = (int) Context::getContext()->employee->id;
                }
                if ($serialNumber->update()) {
                    SerialNumberHelper::createLog(
                        'Serial number ID: ' . (int) $serialNumber->id . ' marked as deleted due to product deletion (Product ID: ' . $id_product . ').',
                        'INFO'
                    );
                } else {
                    SerialNumberHelper::createLog(
                        'Failed to mark serial number ID: ' . (int) $serialNumber->id . ' as deleted after product deletion (Product ID: ' . $id_product . ').',
                        'ERROR'
                    );
                }
            }
        }
        // Après suppression, resynchroniser le stock du produit (qui sera 0 ou géré par PrestaShop si le produit n'existe plus)
        SerialNumberHelper::synchronizeStock($id_product);
    }

    /**
     * Hook exécuté après la suppression d'un objet ProductAttribute.
     * Marque les numéros de série associés à cette déclinaison comme supprimés.
     *
     * @param array $params Contient l'objet ProductAttribute.
     */
    public function hookActionObjectProductAttributeDeleteAfter($params)
    {
        /** @var Combination $combination */
        $combination = $params['object'];

        if (!Validate::isLoadedObject($combination)) {
            SerialNumberHelper::createLog('Hook actionObjectProductAttributeDeleteAfter: Invalid ProductAttribute object received.', 'ERROR');
            return;
        }

        $id_product = (int) $combination->id_product;
        $id_product_attribute = (int) $combination->id;

        // Récupérer tous les numéros de série associés à cette déclinaison
        $serialNumbers = SerialNumberHelper::getSerialNumbersByProduct($id_product, $id_product_attribute, true);

        if (empty($serialNumbers)) {
            SerialNumberHelper::createLog('No serial numbers found for deleted product attribute ID: ' . $id_product_attribute, 'INFO');
            return;
        }

        foreach ($serialNumbers as $snData) {
            $serialNumber = new SerialNumberHelper((int) $snData['id_serial']);
            if (Validate::isLoadedObject($serialNumber)) {
                $serialNumber->deleted = 1;
                $serialNumber->date_deleted = date('Y-m-d H:i:s');
                $serialNumber->status = 'deleted';
                if (Context::getContext()->employee) {
                    $serialNumber->deleted_by = (int) Context::getContext()->employee->id;
                }
                if ($serialNumber->update()) {
                    SerialNumberHelper::createLog(
                        'Serial number ID: ' . (int) $serialNumber->id . ' marked as deleted due to product attribute deletion (Product ID: ' . $id_product . ', Attribute ID: ' . $id_product_attribute . ').',
                        'INFO'
                    );
                } else {
                    SerialNumberHelper::createLog(
                        'Failed to mark serial number ID: ' . (int) $serialNumber->id . ' as deleted after product attribute deletion (Product ID: ' . $id_product . ', Attribute ID: ' . $id_product_attribute . ').',
                        'ERROR'
                    );
                }
            }
        }
        // Après suppression, resynchroniser le stock de la déclinaison
        SerialNumberHelper::synchronizeStock($id_product, $id_product_attribute);
    }

    /**
     * Hook pour afficher les numéros de série sur la facture PDF.
     *
     * @param array $params Contient l'objet OrderInvoice.
     * @return string HTML à insérer dans la facture.
     */
    public function hookDisplayPDFInvoice($params)
    {
        // Vérifie si l'inclusion sur la facture est activée dans les paramètres du module
        if (!(bool) Configuration::get('SERIALNUMBER_INCLUDE_INVOICE')) {
            return '';
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = $params['object'];

        if (!Validate::isLoadedObject($orderInvoice)) {
            SerialNumberHelper::createLog('Hook displayPDFInvoice: Invalid OrderInvoice object received.', 'ERROR');
            return '';
        }

        $id_order = (int) $orderInvoice->id_order;

        // Récupérer les numéros de série associés à cette facture
        // Note: Les numéros de série sont liés à order_detail, pas directement à order_invoice.
        // Il faut donc passer par order_detail pour trouver les SNs de cette commande.
        $serialNumbers = Db::getInstance()->executeS('
            SELECT
                sn.serial_number,
                od.product_name,
                od.product_attribute_id
            FROM ' . _DB_PREFIX_ . 'serial_numbers sn
            INNER JOIN ' . _DB_PREFIX_ . 'order_detail od ON sn.id_order_detail = od.id_order_detail
            WHERE od.id_order = ' . $id_order . '
            AND sn.status = "assigned" -- Ou "shipped" si vous voulez les SNs des produits déjà expédiés
            AND sn.deleted = 0
            ORDER BY od.product_name ASC, sn.serial_number ASC
        ');

        if (empty($serialNumbers)) {
            return ''; // Aucun numéro de série à afficher
        }

        // Préparer les données pour le template PDF
        foreach ($serialNumbers as &$serial) {
            $serial['display_name'] = $serial['product_name'];
            if ((int) $serial['product_attribute_id'] > 0) {
                $productAttributeName = Product::getCombinationAttributesNames(
                    (int) $serial['product_attribute_id'],
                    $this->context->language->id,
                    false
                );
                if (!empty($productAttributeName)) {
                    $attributeNames = array_column($productAttributeName, 'attribute_name');
                    $serial['display_name'] .= ' - (' . implode(', ', $attributeNames) . ')';
                }
            }
        }

        $this->context->smarty->assign([
            'serialNumbers' => $serialNumbers,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/pdf_invoice_serial_numbers.tpl');
    }
}
