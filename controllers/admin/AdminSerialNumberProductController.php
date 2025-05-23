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
        if (!empty($this->confirmations)) {
            $this->context->smarty->assign('confirmations', $this->confirmations);
        }

        if (strpos($_SERVER['QUERY_STRING'], 'deleteserial_numbers') !== false) {
            $id_serial = isset($_GET['id_serial']) ? (int)$_GET['id_serial'] : null;
        
            if ($id_serial) {
                $serialNumber = new SerialNumberHelper($id_serial);
                if ($serialNumber->delete()) {
                    // Synchroniser le stock après suppression
                    SerialNumberHelper::synchronizeStock($serialNumber->id_product, $serialNumber->id_product_attribute);
                    
                    $this->confirmations[] = $this->module->l('Le numéro de série a été supprimé et le stock mis à jour.');
                } else {
                    $this->errors[] = $this->module->l('Erreur lors de la suppression du numéro de série.');
                }
            } else {
                $this->errors[] = $this->module->l('ID de numéro de série manquant.');
            }
        }
        
        
        
        
      
        if (!empty($this->errors)) {
            $this->context->smarty->assign('errors', $this->errors);
        }
    
        $this->context->smarty->assign('current_tab', $this->controller_name);

       
        $header = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/header.tpl');
        $this->context->smarty->assign('header', $header);

        if (Tools::getValue('action') === 'view') {
        
            $content = $this->renderViewSerialNumbersPage();
            $this->context->smarty->assign('content', $content);

            
            $this->setTemplate('content.tpl');
            return;
        }
        if (Tools::getValue('action') == 'searchProduct') {
            $this->context->smarty->assign('ajaxUrl', $this->context->link->getAdminLink('AdminSerialNumberProduct'));
        }

        if (Tools::getValue('ajax') && Tools::getValue('action') == 'searchProduct') {
            $this->ajaxSearchProducts();
            exit;
        }

        if (Tools::getValue('action') === 'add') {
            $content = $this->renderAddSerialNumberPage();
            $this->context->smarty->assign('content', $content);

            // On utilise setTemplate avec un fichier de template vide ou minimaliste
            $this->setTemplate('content.tpl');
            return;
        }

        if (Tools::getValue('ajax') && Tools::getValue('action') == 'saveSerialNumbers') {
            $this->processSaveSerialNumbers();
            exit; // Important pour arrêter l'exécution après l'appel
        }
        


        // Assigner le template de base si aucune action spécifique n'est définie
        $this->setTemplate('content.tpl');
    }

    public function renderList()
    {
        $searchQuery = Tools::getValue('search_query', '');
        $products = [];
        $groupedProducts = [];

        // Si une recherche est effectuée
        if (Tools::isSubmit('submitSearchProduct')) {
            $products = $this->searchProducts($searchQuery);
            $groupedProducts = $this->groupProductsByProductId($products);
        }

        // Utilisation de HelperList pour afficher les résultats
        if (!empty($groupedProducts)) {

            return $this->renderProductListWithHelper($groupedProducts);
        }
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
            // Vérifier si le tableau est vide
            if (empty($groupedProducts)) {
                return '<p>Aucun produit trouvé.</p>';
            }

            $fields_list = [
                'id_product' => ['title' => 'ID Produit', 'width' => 50, 'type' => 'int'],
                'id_product_attribute' => ['title' => 'ID Produit Attribute', 'width' => 50, 'type' => 'int'],
                'name' => ['title' => 'Nom du Produit', 'width' => 200],
                'product_reference' => ['title' => 'Référence Produit', 'width' => 100],
                'attribute_reference' => ['title' => 'Référence Déclinaison', 'width' => 100],
                'ean13' => ['title' => 'EAN13', 'width' => 100],
                'available_serial_numbers' => ['title' => 'Numéro de série disponible', 'width' => 150],
                'actions' => ['title' => 'Actions', 'width' => 150, 'align' => 'text-center', 'remove_onclick' => true, 'callback' => 'renderActionsButtons'],
            ];

            $list = [];
            foreach ($groupedProducts as $productGroup) {
                foreach ($productGroup as $product) {
                    // Compter les numéros de série disponibles pour ce produit et cette déclinaison
                    $availableSerialCount = Db::getInstance()->getValue('
                    SELECT COUNT(*)
                    FROM ' . _DB_PREFIX_ . 'serial_numbers sn
                    WHERE sn.id_product = ' . (int) $product['id_product'] . '
                        AND sn.id_product_attribute = ' . (int) $product['id_product_attribute'] . '
                        AND (sn.id_order_detail = 0 OR sn.id_order_detail IS NULL)
                        AND sn.deleted = 0
                ');

                    $list[] = [
                        'id_product' => $product['id_product'] ?: '--',
                        'id_product_attribute' => $product['id_product_attribute'] ?: '--',
                        'name' => $product['name'] ?: '--',
                        'product_reference' => $product['product_reference'] ?: '--',
                        'attribute_reference' => $product['attribute_reference'] ?: '--',
                        'ean13' => $product['ean13'] ?: '--',
                        'available_serial_numbers' => $availableSerialCount ?: '0',
                        'actions' => $product ?: '--',
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

            return $helper->generateList($list, $fields_list);

        } catch (PrestaShopDatabaseException $e) {
            return '<div style="color: red;">Erreur de base de données : ' . $e->getMessage() . '</div>';
        } catch (Exception $e) {
            return '<div style="color: red;">Une erreur est survenue (renderProductListWithHelper)  : ' . $e->getMessage() . '</div>';
        }
    }


    public function renderActionsButtons($product)
    {
        $viewUrl = $this->context->link->getAdminLink('AdminSerialNumberProduct')
            . '&action=view&id_product=' . $product['id_product']
            . '&id_product_attribute=' . $product['id_product_attribute'];

        $addUrl = $this->context->link->getAdminLink('AdminSerialNumberProduct')
            . '&action=add&id_product=' . $product['id_product']
            . '&id_product_attribute=' . $product['id_product_attribute'];

        return '
        <div class="btn-group action-dropdown" style="display: flex;">
            <a href="' . $addUrl . '" class="btn btn-default">
                <i class="icon-plus"></i> Ajouter
            </a>
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li>
                    <a href="' . $viewUrl . '">
                        <i class="icon-search-plus"></i> Afficher
                    </a>
                </li>
            </ul>
        </div>';
    }



    public function renderAddSerialNumberPage()
    {
        $id_product = Tools::getValue('id_product');
        $id_product_attribute = Tools::getValue('id_product_attribute');

        // Assigner les variables nécessaires à Smarty pour utilisation dans le template
        $this->context->smarty->assign([
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'save_action_url' => $this->context->link->getAdminLink('AdminSerialNumberProduct') . '&action=saveSerialNumbers',
        ]);

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/add_serial_number.tpl');
    }

    // Méthode pour afficher la page de visualisation des numéros de série
    public function renderViewSerialNumbersPage()
    {
        try {
            $id_product = Tools::getValue('id_product');
            $id_product_attribute = Tools::getValue('id_product_attribute');
          
            // Vérifier si id_product est bien récupéré
            if (!$id_product) {
                throw new Exception('Produit non trouvé.');
            }

            // Requête pour récupérer les numéros de série associés à ce produit et non supprimés (deleted = 0)
            if ($id_product > 0 && $id_product_attribute > 0) {
                $serialNumbers = Db::getInstance()->executeS('
                    SELECT sn.id_serial, sn.serial_number, sn.id_order_detail, sn.status, sn.active, p.id_product, pl.name as product_name, pa.reference as attribute_reference, CONCAT(c.firstname, " ", c.lastname) AS customer
                    FROM ' . _DB_PREFIX_ . 'serial_numbers sn
                    LEFT JOIN ' . _DB_PREFIX_ . 'product p ON sn.id_product = p.id_product
                    LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int) $this->context->language->id . '
                    LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON sn.id_product_attribute = pa.id_product_attribute
                    LEFT JOIN ' . _DB_PREFIX_ . 'order_detail od ON sn.id_order_detail = od.id_order_detail
                    LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                    LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON o.id_customer = c.id_customer
                    WHERE sn.id_product = ' . $id_product . ' AND sn.id_product_attribute = ' . $id_product_attribute . ' AND sn.deleted = 0
                ');
            } else {
                throw new Exception('Les filtres de produit et d\'attribut de produit sont incorrects.');
            }
        

            // Vérifier si la requête a renvoyé un résultat vide
            $hasSerialNumbers = !empty($serialNumbers);

            // Ajouter une ligne vide avec un message personnalisé pour afficher le header du tableau si aucun numéro de série n'est trouvé
            if (!$hasSerialNumbers) {
                $serialNumbers = [
                    [
                        'id_serial' => '-',
                        'serial_number' => 'Aucun numéro de série disponible',
                        'status' => '-',
                        'id_order_detail' => '-',
                        'product_name' => '-',
                        'attribute_reference' => '-',
                        'customer' => '-',
                        'active' => '-',
                        'custom_actions' => 'Aucune action disponible',
                    ],
                ];
            }

            // Utiliser HelperList pour afficher les numéros de série
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

            // Définir les actions uniquement si des numéros de série existent
            if ($hasSerialNumbers) {
                $helper->actions = ['delete', 'edit', 'view'];
            } else {
                $helper->actions = []; // Pas d'actions si la liste est vide
            }

            // Liste des champs à afficher dans le tableau
            $fields_list = [
                'id_serial' => ['title' => 'ID', 'width' => 50],
                'serial_number' => ['title' => 'Numéro de Série', 'width' => 200],
                'product_name' => ['title' => 'Produit', 'width' => 150],
                'attribute_reference' => ['title' => 'Déclinaison', 'width' => 150],
                'id_order_detail' => ['title' => 'Commande', 'width' => 100, 'callback' => 'renderOrderLink'],
                'customer' => ['title' => 'Client', 'width' => 150, 'callback' => 'renderCustomerName'],
                'active' => ['title' => 'Actif', 'width' => 50, 'active' => 'status', 'align' => 'center', 'type' => 'bool', 'orderby' => false, 'callback' => 'renderActiveIcon'],
                
            ];

            // Générer le tableau avec les données
            return $helper->generateList($serialNumbers, $fields_list);

        } catch (PrestaShopDatabaseException $e) {
            // Afficher l'erreur de base de données directement
            echo '<div style="color: red;">Erreur de base de données : ' . $e->getMessage() . '</div>';
        } catch (Exception $e) {
            // Afficher une erreur générale directement
            echo '<div style="color: red;">Une erreur est survenue (renderViewSerialNumbersPage) : ' . $e->getMessage() . '</div>';
        }
    }
    
    public function renderCustomActions($row)
    { 
        var_dump($row);
        return "LAmya";
    }
    


    

   

    public function renderActionsButtonsSN($row)
{
   return "LAmya";
}


public function renderOrderLink($id_order_detail)
{
    if ($id_order_detail && $id_order_detail != '-') {
        return '<a href="' . $this->context->link->getAdminLink('AdminOrders', true, [], ['vieworder' => 1, 'id_order' => $id_order_detail]) . '">' . $id_order_detail . '</a>';
    }
    return '-';
}
    public function renderCustomerName($customer)
    {
        if ($customer && $customer != '-') {
            return $customer;
        }
        return '-'; // Valeur par défaut si les données sont incorrectes
    }
    public function renderActiveIcon($active, $row)
    {
        $activeIcon = $active ? 'icon-check' : 'icon-remove';
        $toggleUrl = $this->context->link->getAdminLink('AdminSerialNumberProduct') . '&action=toggleStatus&id_serial=' . $row['id_serial'];
        return '<a href="' . $toggleUrl . '"><i class="' . $activeIcon . '"></i></a>';
    }


    // Fonction pour rechercher les produits dans la base de données

    private function searchProducts($query)
    {
        $id_lang = (int) $this->context->language->id;

        // Début de la requête commune
        $sql = '
        SELECT p.id_product, pa.id_product_attribute, p.reference AS product_reference, pa.reference AS attribute_reference, pl.name, pa.ean13, p.ean13 AS product_ean13
        FROM ' . _DB_PREFIX_ . 'product p
        LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON p.id_product = pa.id_product
        LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product
        WHERE pl.id_lang = ' . $id_lang;

        // Condition si une recherche est spécifiée
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
        } else {
            // Ajout du tri aléatoire pour les produits sans recherche
            $sql .= ' ORDER BY RAND() LIMIT 20';
        }

        return Db::getInstance()->executeS($sql);
    }

    // Fonction pour grouper les produits par ID de produit
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


        // Générer le HTML pour afficher les résultats
        echo $this->renderProductListWithHelper($groupedProducts);
    }

    public function processSaveSerialNumbers()
    {
        $response = ['success' => true, 'message' => 'Tous les numéros de série ont été enregistrés avec succès.'];

        try {
            $serialNumbersString = Tools::getValue('serial_numbers');
            $idProduct = (int) Tools::getValue('id_product');
            $idProductAttribute = (int) Tools::getValue('id_product_attribute');
            $isActive = Tools::getValue('active', 0);

            if (empty($serialNumbersString) || !$idProduct) {
                throw new Exception('Les numéros de série ou l\'ID produit sont manquants.');
            }

            $errors = [];
            $serialNumbers = preg_split('/\r\n|\r|\n/', $serialNumbersString);
            $quantityToAdd = 0; // Compteur pour la quantité à ajouter

            foreach ($serialNumbers as $serial) {
                $serial = trim($serial);
                if (empty($serial))
                    continue;

                $serialNumber = new SerialNumberHelper();
                $serialNumber->id_product = $idProduct;
                $serialNumber->id_product_attribute = $idProductAttribute ? $idProductAttribute : null;
                $serialNumber->serial_number = $serial;
                $serialNumber->status = 'available';
                $serialNumber->active = $isActive;
                $serialNumber->deleted = 0;
                $serialNumber->date_added = date('Y-m-d H:i:s');

                if ($serialNumber->add()) {
                    $quantityToAdd++; // Incrémente le compteur pour chaque numéro ajouté
                } else {
                    $errors[] = 'Impossible d\'enregistrer le numéro de série : ' . $serial;
                }
            }

            // Mettre à jour la quantité de la déclinaison
            if ($quantityToAdd > 0) {
                StockAvailable::updateQuantity(
                    $idProduct,
                    $idProductAttribute,
                    $quantityToAdd,
                    $this->context->shop->id
                );
            }

            if (!empty($errors)) {
                $response['success'] = false;
                $response['message'] = implode('<br>', $errors);
            }

        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        // Envoyer le JSON final
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }




    public function deleteSerialNumbers()
{
    die('deleteSerialNumbers called');
}

    

    public function displayCustomMessage($message, $type = 'confirmation')
    {
        if ($type === 'error') {
            $this->errors[] = $this->module->displayError($message);
        } else {
            $this->confirmations[] = $this->module->displayConfirmation($message);
        }
    }
    public function testLogging()
    {
        $serialNumber = new SerialNumberHelper();
        $serialNumber->createLog('Test de création de log : ceci est un test.', 'INFO');
        die('Test exécuté');
    }


}