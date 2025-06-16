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

class AdminSerialNumberProductController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();
        
        // Gestion des messages de confirmation et d'erreur
        if (!empty($this->confirmations)) {
            $this->context->smarty->assign('confirmations', $this->confirmations);
        }
        if (!empty($this->errors)) {
            $this->context->smarty->assign('errors', $this->errors);
        }

        // Gestion de la suppression des numéros de série
        if (Tools::getValue('action') === 'delete' && Tools::getValue('id_serial')) {
            $this->processDeleteSerialNumber();
        }

        // Gestion du changement de statut
        if (Tools::getValue('action') === 'toggleStatus' && Tools::getValue('id_serial')) {
            $this->processToggleStatus();
        }
        
        $this->context->smarty->assign('current_tab', $this->controller_name);

        // Ajouter le header commun à toutes les pages du module
        $header = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/header.tpl');
        $this->context->smarty->assign('header', $header);

        // Gestion des différentes actions
        $action = Tools::getValue('action');
        
        if ($action === 'view') {
            $content = $this->renderViewSerialNumbersPage();
            $this->context->smarty->assign('content', $content);
            $this->setTemplate('content.tpl');
            return;
        }

        if ($action === 'add') {
            $content = $this->renderAddSerialNumberPage();
            $this->context->smarty->assign('content', $content);
            $this->setTemplate('content.tpl');
            return;
        }

        // Gestion des requêtes AJAX
        if (Tools::getValue('ajax')) {
            if (Tools::getValue('action') == 'searchProduct') {
                $this->ajaxSearchProducts();
                exit;
            }
            
            if (Tools::getValue('action') == 'saveSerialNumbers') {
                $this->processSaveSerialNumbers();
                exit;
            }
        }

        // Assigner le template de base si aucune action spécifique n'est définie
        $this->setTemplate('content.tpl');
    }

    /**
     * Traite la suppression d'un numéro de série
     */
    private function processDeleteSerialNumber()
    {
        $id_serial = (int) Tools::getValue('id_serial');
        
        if ($id_serial) {
            $serialNumber = new SerialNumberHelper($id_serial);
            if (Validate::isLoadedObject($serialNumber)) {
                if ($serialNumber->delete()) {
                    // Synchroniser le stock après suppression
                    SerialNumberHelper::synchronizeStock($serialNumber->id_product, $serialNumber->id_product_attribute);
                    $this->confirmations[] = $this->module->l('Le numéro de série a été supprimé et le stock mis à jour.');
                } else {
                    $this->errors[] = $this->module->l('Erreur lors de la suppression du numéro de série.');
                }
            } else {
                $this->errors[] = $this->module->l('Numéro de série introuvable.');
            }
        } else {
            $this->errors[] = $this->module->l('ID de numéro de série manquant.');
        }
    }

    /**
     * Traite le changement de statut d'un numéro de série
     */
    private function processToggleStatus()
    {
        $id_serial = (int) Tools::getValue('id_serial');
        
        if ($id_serial) {
            $serialNumber = new SerialNumberHelper($id_serial);
            if (Validate::isLoadedObject($serialNumber)) {
                $serialNumber->active = !$serialNumber->active;
                if ($serialNumber->update()) {
                    $status = $serialNumber->active ? 'activé' : 'désactivé';
                    $this->confirmations[] = $this->module->l('Le numéro de série a été ' . $status . '.');
                } else {
                    $this->errors[] = $this->module->l('Erreur lors du changement de statut.');
                }
            }
        }
    }

    public function renderList()
    {
        $searchQuery = Tools::getValue('search_query', '');
        $products = [];
        $groupedProducts = [];

        // Si une recherche est effectuée
        if (Tools::isSubmit('submitSearchProduct') || !empty($searchQuery)) {
            $products = $this->searchProducts($searchQuery);
            $groupedProducts = $this->groupProductsByProductId($products);
        }

        // Utilisation de HelperList pour afficher les résultats
        if (!empty($groupedProducts)) {
            return $this->renderProductListWithHelper($groupedProducts);
        }

        // Affichage des messages
        if (!empty($this->confirmations)) {
            $this->displayInformation(implode('<br>', $this->confirmations));
        }
        if (!empty($this->errors)) {
            $this->displayWarning(implode('<br>', $this->errors));
        }

        // Assigner les variables à Smarty
        $this->context->smarty->assign([
            'search_query' => $searchQuery,
            'products' => $products,
            'groupedProducts' => $groupedProducts,
        ]);

        // Retourner le rendu du template
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/product_list.tpl');
    }

    private function renderProductListWithHelper($groupedProducts)
    {
        try {
            if (empty($groupedProducts)) {
                return '<p>Aucun produit trouvé.</p>';
            }

            $fields_list = [
                'id_product' => ['title' => 'ID Produit', 'width' => 50, 'type' => 'int'],
                'id_product_attribute' => ['title' => 'ID Déclinaison', 'width' => 50, 'type' => 'int'],
                'name' => ['title' => 'Nom du Produit', 'width' => 200],
                'product_reference' => ['title' => 'Référence Produit', 'width' => 100],
                'attribute_reference' => ['title' => 'Référence Déclinaison', 'width' => 100],
                'ean13' => ['title' => 'EAN13', 'width' => 100],
                'available_serial_numbers' => ['title' => 'N° série disponibles', 'width' => 100, 'align' => 'center'],
            ];

            $list = [];
            foreach ($groupedProducts as $productGroup) {
                foreach ($productGroup as $product) {
                    // Compter les numéros de série disponibles
                    $availableSerialCount = (int) Db::getInstance()->getValue('
                        SELECT COUNT(*)
                        FROM ' . _DB_PREFIX_ . 'serial_numbers sn
                        WHERE sn.id_product = ' . (int) $product['id_product'] . '
                            AND sn.id_product_attribute = ' . (int) $product['id_product_attribute'] . '
                            AND sn.status = "available"
                            AND sn.deleted = 0
                    ');

                    $list[] = [
                        'id_product' => $product['id_product'] ?: '--',
                        'id_product_attribute' => $product['id_product_attribute'] ?: '--',
                        'name' => $product['name'] ?: '--',
                        'product_reference' => $product['product_reference'] ?: '--',
                        'attribute_reference' => $product['attribute_reference'] ?: '--',
                        'ean13' => $product['ean13'] ?: '--',
                        'available_serial_numbers' => $availableSerialCount,
                    ];
                }
            }

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = false;
            $helper->identifier = 'id_product';
            $helper->table = 'serialnumber_product';
            $helper->show_toolbar = false;
            $helper->module = $this->module;
            $helper->title = 'Résultats de la recherche';
            $helper->listTotal = count($list);
            $helper->currentIndex = $this->context->link->getAdminLink('AdminSerialNumberProduct');
            $helper->token = Tools::getAdminTokenLite('AdminSerialNumberProduct');
            $helper->actions = ['view', 'add'];

            return $helper->generateList($list, $fields_list);

        } catch (Exception $e) {
            return '<div class="alert alert-danger">Erreur : ' . $e->getMessage() . '</div>';
        }
    }

    public function displayViewLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        if (!array_key_exists('View', self::$cache_lang)) {
            self::$cache_lang['View'] = $this->l('View');
        }

        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminSerialNumberProduct') .
                     '&action=view&id_product=' . $id . '&id_product_attribute=' . Tools::getValue('id_product_attribute'),
            'action' => self::$cache_lang['View'],
        ]);

        return $tpl->fetch();
    }

    public function displayAddLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_add.tpl');
        if (!array_key_exists('Add', self::$cache_lang)) {
            self::$cache_lang['Add'] = $this->l('Add');
        }

        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminSerialNumberProduct') .
                     '&action=add&id_product=' . $id . '&id_product_attribute=' . Tools::getValue('id_product_attribute'),
            'action' => self::$cache_lang['Add'],
        ]);

        return $tpl->fetch();
    }

    public function renderAddSerialNumberPage()
    {
        $id_product = (int) Tools::getValue('id_product');
        $id_product_attribute = (int) Tools::getValue('id_product_attribute');

        if (!$id_product) {
            return '<div class="alert alert-danger">Produit non spécifié.</div>';
        }

        // Récupérer les informations du produit
        $product = new Product($id_product, false, $this->context->language->id);
        $productName = $product->name;

        if ($id_product_attribute) {
            $combination = new Combination($id_product_attribute);
            $attributes = $combination->getAttributesName($this->context->language->id);
            if (!empty($attributes)) {
                $attributeNames = array_column($attributes, 'name');
                $productName .= ' - ' . implode(', ', $attributeNames);
            }
        }

        $this->context->smarty->assign([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'product_name' => $productName,
            'save_action_url' => $this->context->link->getAdminLink('AdminSerialNumberProduct') . '&action=saveSerialNumbers',
        ]);

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/add_serial_number.tpl');
    }

    public function renderViewSerialNumbersPage()
    {
        try {
            $id_product = (int) Tools::getValue('id_product');
            $id_product_attribute = (int) Tools::getValue('id_product_attribute');
          
            if (!$id_product) {
                throw new Exception('Produit non trouvé.');
            }

            // Requête pour récupérer les numéros de série
            $sql = '
                SELECT sn.id_serial, sn.serial_number, sn.id_order_detail, sn.status, sn.active, 
                       p.id_product, pl.name as product_name, pa.reference as attribute_reference, 
                       CONCAT(c.firstname, " ", c.lastname) AS customer, o.id_order
                FROM ' . _DB_PREFIX_ . 'serial_numbers sn
                LEFT JOIN ' . _DB_PREFIX_ . 'product p ON sn.id_product = p.id_product
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int) $this->context->language->id . '
                LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON sn.id_product_attribute = pa.id_product_attribute
                LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON sn.id_order_detail = od.id_order_detail
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON o.id_customer = c.id_customer
                WHERE sn.id_product = ' . $id_product . ' 
                  AND sn.id_product_attribute = ' . $id_product_attribute . ' 
                  AND sn.deleted = 0
                ORDER BY sn.date_added DESC
            ';

            $serialNumbers = Db::getInstance()->executeS($sql);
            $hasSerialNumbers = !empty($serialNumbers);

            if (!$hasSerialNumbers) {
                $serialNumbers = [
                    [
                        'id_serial' => '-',
                        'serial_number' => 'Aucun numéro de série disponible',
                        'status' => '-',
                        'id_order_detail' => '-',
                        'id_order' => '-',
                        'product_name' => '-',
                        'attribute_reference' => '-',
                        'customer' => '-',
                        'active' => '-',
                    ],
                ];
            }

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = false;
            $helper->identifier = 'id_serial';
            $helper->table = 'serial_numbers';
            $helper->show_toolbar = false;
            $helper->module = $this->module;
            $helper->title = 'Numéros de série pour ce produit';
            $helper->currentIndex = $this->context->link->getAdminLink('AdminSerialNumberProduct');
            $helper->token = Tools::getAdminTokenLite('AdminSerialNumberProduct');

            if ($hasSerialNumbers) {
                $helper->actions = ['delete'];
            }

            $fields_list = [
                'id_serial' => ['title' => 'ID', 'width' => 50],
                'serial_number' => ['title' => 'Numéro de Série', 'width' => 200],
                'product_name' => ['title' => 'Produit', 'width' => 150],
                'attribute_reference' => ['title' => 'Déclinaison', 'width' => 150],
                'id_order' => ['title' => 'Commande', 'width' => 100, 'callback' => 'renderOrderLink'],
                'customer' => ['title' => 'Client', 'width' => 150, 'callback' => 'renderCustomerName'],
                'active' => ['title' => 'Actif', 'width' => 50, 'align' => 'center', 'type' => 'bool', 'callback' => 'renderActiveIcon'],
            ];

            return $helper->generateList($serialNumbers, $fields_list);

        } catch (Exception $e) {
            return '<div class="alert alert-danger">Erreur : ' . $e->getMessage() . '</div>';
        }
    }

    public function renderOrderLink($id_order, $row)
    {
        if ($id_order && $id_order != '-') {
            return '<a href="' . $this->context->link->getAdminLink('AdminOrders', true, [], ['vieworder' => 1, 'id_order' => $id_order]) . '">#' . $id_order . '</a>';
        }
        return '-';
    }

    public function renderCustomerName($customer)
    {
        return ($customer && $customer != '-') ? $customer : '-';
    }

    public function renderActiveIcon($active, $row)
    {
        if ($row['id_serial'] == '-') {
            return '-';
        }
        
        $activeIcon = $active ? 'icon-check text-success' : 'icon-remove text-danger';
        $toggleUrl = $this->context->link->getAdminLink('AdminSerialNumberProduct') . 
                    '&action=toggleStatus&id_serial=' . $row['id_serial'];
        return '<a href="' . $toggleUrl . '"><i class="' . $activeIcon . '"></i></a>';
    }

    public function displayDeleteLink($token, $id, $name = null)
    {
        if ($id == '-') {
            return '';
        }

        $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = $this->l('Delete');
        }

        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminSerialNumberProduct') .
                     '&action=delete&id_serial=' . $id,
            'action' => self::$cache_lang['Delete'],
            'confirm' => $this->l('Êtes-vous sûr de vouloir supprimer ce numéro de série ?'),
        ]);

        return $tpl->fetch();
    }

    private function searchProducts($query)
    {
        $id_lang = (int) $this->context->language->id;

        $sql = '
        SELECT p.id_product, pa.id_product_attribute, p.reference AS product_reference, 
               pa.reference AS attribute_reference, pl.name, pa.ean13, p.ean13 AS product_ean13
        FROM ' . _DB_PREFIX_ . 'product p
        LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON p.id_product = pa.id_product
        LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
        WHERE pl.id_lang = ' . $id_lang . '
          AND p.active = 1';

        if (!empty($query)) {
            $query = pSQL($query);
            $sql .= '
            AND (
                p.reference LIKE "%' . $query . '%"
                OR pa.reference LIKE "%' . $query . '%"
                OR pl.name LIKE "%' . $query . '%"
                OR p.ean13 LIKE "%' . $query . '%"
                OR pa.ean13 LIKE "%' . $query . '%"
            )';
        }

        $sql .= ' ORDER BY pl.name ASC LIMIT 50';

        return Db::getInstance()->executeS($sql);
    }

    private function groupProductsByProductId($products)
    {
        $groupedProducts = [];
        foreach ($products as $product) {
            $groupedProducts[$product['id_product']][] = $product;
        }
        return $groupedProducts;
    }

    public function ajaxSearchProducts()
    {
        $searchQuery = Tools::getValue('search_query', '');
        $products = $this->searchProducts($searchQuery);
        $groupedProducts = $this->groupProductsByProductId($products);

        echo $this->renderProductListWithHelper($groupedProducts);
    }

    public function processSaveSerialNumbers()
    {
        $response = ['success' => false, 'message' => ''];

        try {
            $serialNumbersString = Tools::getValue('serial_numbers');
            $idProduct = (int) Tools::getValue('id_product');
            $idProductAttribute = (int) Tools::getValue('id_product_attribute');
            $isActive = (bool) Tools::getValue('active', 0);

            if (empty($serialNumbersString) || !$idProduct) {
                throw new Exception('Les numéros de série ou l\'ID produit sont manquants.');
            }

            $errors = [];
            $serialNumbers = preg_split('/[\r\n\s]+/', trim($serialNumbersString));
            $quantityToAdd = 0;
            $addedCount = 0;

            foreach ($serialNumbers as $serial) {
                $serial = trim($serial);
                if (empty($serial)) {
                    continue;
                }

                // Vérifier si le numéro de série existe déjà
                $exists = Db::getInstance()->getValue('
                    SELECT COUNT(*) 
                    FROM ' . _DB_PREFIX_ . 'serial_numbers 
                    WHERE serial_number = "' . pSQL($serial) . '" 
                      AND deleted = 0
                ');

                if ($exists) {
                    $errors[] = 'Le numéro de série "' . $serial . '" existe déjà.';
                    continue;
                }

                $serialNumber = new SerialNumberHelper();
                $serialNumber->id_product = $idProduct;
                $serialNumber->id_product_attribute = $idProductAttribute ? $idProductAttribute : 0;
                $serialNumber->serial_number = $serial;
                $serialNumber->status = 'available';
                $serialNumber->active = $isActive;
                $serialNumber->deleted = 0;
                $serialNumber->date_added = date('Y-m-d H:i:s');

                if ($serialNumber->add()) {
                    $quantityToAdd++;
                    $addedCount++;
                } else {
                    $errors[] = 'Impossible d\'enregistrer le numéro de série : ' . $serial;
                }
            }

            // Mettre à jour la quantité de stock
            if ($quantityToAdd > 0) {
                StockAvailable::updateQuantity(
                    $idProduct,
                    $idProductAttribute,
                    $quantityToAdd,
                    $this->context->shop->id
                );
            }

            if ($addedCount > 0) {
                $response['success'] = true;
                $response['message'] = $addedCount . ' numéro(s) de série ajouté(s) avec succès.';
                
                if (!empty($errors)) {
                    $response['message'] .= '<br>Erreurs : ' . implode('<br>', $errors);
                }
            } else {
                $response['message'] = 'Aucun numéro de série n\'a pu être ajouté.';
                if (!empty($errors)) {
                    $response['message'] .= '<br>' . implode('<br>', $errors);
                }
            }

        } catch (Exception $e) {
            $response['message'] = 'Erreur : ' . $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}