<?php
require_once _PS_MODULE_DIR_ . 'serialnumber/classes/SerialNumberHelper.php';
class AdminSerialNumberProductsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->table = 'serial_numbers';
        $this->className = 'SerialNumberHelper';
        $this->identifier = 'id_serial';
        // Paramètres de pagination
        $this->list_no_link = true;
        $this->list_simple_header = false;
        $this->_defaultOrderBy = 'date_added';
        $this->_defaultOrderWay = 'DESC';
        // Configuration de la pagination
        $this->setPaginationOptions();
        // Ajout des actions
        $this->addRowActions();
        // Configuration des champs
        $this->initFieldsList();
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
    private function setPaginationOptions()
    {
        $perPageOptions = [20, 50, 100, 300];
        $defaultPerPage = 50;
        $this->context->cookie->pagination = (int) Tools::getValue('pagination', $defaultPerPage);
        if (!in_array($this->context->cookie->pagination, $perPageOptions)) {
            $this->context->cookie->pagination = $defaultPerPage;
        }
        $this->pagination = [$this->context->cookie->pagination];
    }
    private function addRowActions()
    {
        $this->actions = ['edit', 'delete'];
        $this->bulk_actions = [
            'liberate' => [
                'text' => $this->trans('Liberer', [], 'Modules.Serialnumber.Admin'),
                'confirm' => $this->trans('Are you sure you want to liberate selected items?', [], 'Modules.Serialnumber.Admin')
            ]
        ];
    }
    private function initFieldsList()
    {
        $this->fields_list = [
            'id_serial' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'width' => 50,
            ],
            'id_order' => [
                'title' => $this->trans('ID cmd', [], 'Modules.Serialnumber.Admin'),
                'width' => 100,
            ],
            'product_name' => [
                'title' => $this->trans('Produit', [], 'Modules.Serialnumber.Admin'),
                'width' => 150,
            ],
            'attribute_reference' => [
                'title' => $this->trans('Déclinaison', [], 'Modules.Serialnumber.Admin'),
                'width' => 100,
            ],
            'customer_name' => [
                'title' => $this->trans('Client', [], 'Modules.Serialnumber.Admin'),
                'width' => 150,
            ],
            'serial_number' => [
                'title' => $this->trans('Numéro de série', [], 'Modules.Serialnumber.Admin'),
                'width' => 200,
            ],
            'active' => [
                'title' => $this->trans('Actif', [], 'Admin.Global'),
                'width' => 50,
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
            ],
        ];
    }
    public function renderList()
    {
        // Récupérer le terme de recherche
        $searchQuery = trim(Tools::getValue('search_query', ''));
        // Assigner les variables nécessaires pour l'interface PrestaShop
        $this->context->smarty->assign([
            'current_tab' => $this->controller_name,
            'link' => $this->context->link,
            'search_query' => $searchQuery, // Assigner le terme de recherche au template
        ]);
        // Construire la requête SQL
        $sql = 'SELECT 
            sn.id_serial, 
            sn.serial_number, 
            sn.active, 
            o.id_order,
            CONCAT(c.firstname, " ", c.lastname) AS customer_name,
            pl.name AS product_name, 
            pa.reference AS attribute_reference
        FROM 
            ' . _DB_PREFIX_ . 'serial_numbers sn
        LEFT JOIN 
            ' . _DB_PREFIX_ . 'product_lang pl ON sn.id_product = pl.id_product AND pl.id_lang = ' . (int) $this->context->language->id . '
        LEFT JOIN 
            ' . _DB_PREFIX_ . 'product_attribute pa ON sn.id_product_attribute = pa.id_product_attribute
        LEFT JOIN 
            ' . _DB_PREFIX_ . 'order_detail od ON sn.id_order_detail = od.id_order_detail
        LEFT JOIN 
            ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
        LEFT JOIN 
            ' . _DB_PREFIX_ . 'customer c ON o.id_customer = c.id_customer
        WHERE 
            sn.deleted = 0';

        // Ajouter la condition de recherche si un terme est spécifié
        if (!empty($searchQuery)) {
            $safeQuery = pSQL($searchQuery);
            $sql .= ' WHERE (
                        sn.serial_number LIKE "%' . $safeQuery . '%" 
                        OR pl.name LIKE "%' . $safeQuery . '%"
                        OR pa.reference LIKE "%' . $safeQuery . '%"
                        OR CONCAT(c.firstname, " ", c.lastname) LIKE "%' . $safeQuery . '%"
                    )';
        }
        $sql .= ' ORDER BY sn.date_added DESC LIMIT 50';
       
        $result = Db::getInstance()->executeS($sql);
        // Assigner le résultat pour le rendu
        $this->context->smarty->assign('serial_numbers', $result);
        // Affichage de la liste des numéros de série avec champ de recherche
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/list_serial_numbers.tpl');
    }
    public function initToolbar()
    {
        parent::initToolbar();
        $this->toolbar_btn['pagination'] = [
            'href' => '#',
            'desc' => $this->trans('Items per page', [], 'Modules.Serialnumber.Admin')
        ];
    }
    public function processBulkLiberate()
    {
        foreach (Tools::getValue('serialBox') as $idSerial) {
            $serial = new SerialNumberHelper((int) $idSerial);
            $serial->active = false;
            $serial->update();
        }
        $this->confirmations[] = $this->trans('Selected items have been liberated.', [], 'Modules.Serialnumber.Admin');
    }
}
