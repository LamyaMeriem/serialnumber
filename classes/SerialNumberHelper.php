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
 */ class SerialNumberHelper extends ObjectModel
{
    public $id_product;
    public $id_product_attribute;
    public $id_order_detail;
    public $serial_number;
    public $status;
    public $active = 1;
    public $deleted = 0;
    public $added_by;
    public $modified_by;
    public $deleted_by;
    public $date_added;
    public $date_modified;
    public $date_deleted;
    public static $definition = [
        'table' => 'serial_numbers',
        'primary' => 'id_serial',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'serial_number' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'added_by' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'modified_by' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'deleted_by' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'date_added' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_modified' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_deleted' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    /**
     * Ajoute un nouveau numéro de série
     *
     * @return bool Retourne true si l'ajout a réussi, sinon false.
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->date_added = date('Y-m-d H:i:s');
        $this->deleted = 0;
        $result = parent::add($autoDate, $nullValues);

        if ($result) {
            $this->logHistory('added');
        }
        return $result;
    }
    /**
     * Met à jour un numéro de série
     *
     * @return bool Retourne true si la mise à jour a réussi, sinon false.
     */
    public function update($nullValues = false)
    {
        $this->date_modified = date('Y-m-d H:i:s');
        $result = parent::update($nullValues);

        if ($result) {
            $this->logHistory('modified');
        }
        return $result;
    }
    /**
     * Supprime un numéro de série (soft delete)
     *
     * @return bool Retourne true si la suppression a réussi, sinon false.
     */
    public function delete()
    {
        // Marquer le numéro de série comme supprimé
        $this->deleted = 1;
        $this->date_deleted = date('Y-m-d H:i:s');

        // Mettre à jour le statut dans la base de données
        $result = $this->update();

        if ($result) {
            // Mettre à jour la quantité disponible du produit
            StockAvailable::updateQuantity(
                $this->id_product,
                $this->id_product_attribute,
                -1, // Réduire de 1 seulement
                Context::getContext()->shop->id
            );

            // Ajouter un log de l'historique
            $this->logHistory('deleted');
        }

        return $result;
    }
    public static function synchronizeStock($id_product, $id_product_attribute = null)
    {
        // Compter les numéros de série non supprimés
        $sql = 'SELECT COUNT(*)
            FROM ' . _DB_PREFIX_ . 'serial_numbers
            WHERE id_product = ' . (int) $id_product . '
              AND deleted = 0';

        if ($id_product_attribute !== null) {
            $sql .= ' AND id_product_attribute = ' . (int) $id_product_attribute;
        }

        $quantity = (int) Db::getInstance()->getValue($sql);

        // Mettre à jour la table `stock_available`
        StockAvailable::setQuantity(
            $id_product,
            $id_product_attribute,
            $quantity,
            Context::getContext()->shop->id
        );
    }



    /**
     * Enregistre une action dans l'historique
     *
     * @param string $action L'action effectuée (added, modified, deleted, assigned, shipped)
     */
    private function logHistory($action)
    {
        $employeeId = Context::getContext()->employee ? (int) Context::getContext()->employee->id : null;
        $data = [
            'id_serial' => (int) $this->id,
            'action' => pSQL($action),
            'user_id' => $employeeId,
            'date_action' => date('Y-m-d H:i:s'),
            'details' => json_encode([
                'serial_number' => $this->serial_number,
                'status' => $this->status,
                'modified_by' => $employeeId,
            ])
        ];
        Db::getInstance()->insert('serial_numbers_history', $data);
    }
    /**
     * Récupère les numéros de série pour un produit spécifique
     *
     * @param int $id_product L'ID du produit
     * @param int|null $id_product_attribute L'ID de l'attribut de produit (facultatif)
     * @return array Tableau de numéros de série
     */
    public static function getSerialNumbersByProduct($id_product, $id_product_attribute = null)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'serial_numbers 
                WHERE id_product = ' . (int) $id_product;

        if ($id_product_attribute !== null) {
            $sql .= ' AND id_product_attribute = ' . (int) $id_product_attribute;
        }
        return Db::getInstance()->executeS($sql);
    }
    /**
     * Valide un numéro de série (exemple de validation simple)
     *
     * @param string $serial_number
     * @return bool Retourne true si le numéro de série est valide, sinon false.
     */
    public static function validateSerialNumber($serial_number)
    {
        // Exemple de validation : doit contenir exactement 10 caractères alphanumériques
        return preg_match('/^[A-Z0-9]{10}$/', $serial_number);
    }
    /**
     * Met à jour le statut d'un numéro de série
     *
     * @param int $id_serial L'ID du numéro de série
     * @param string $new_status Le nouveau statut à appliquer
     * @return bool Retourne true si la mise à jour a réussi, sinon false.
     */
    public static function updateStatus($id_serial, $new_status)
    {
        $serialNumber = new SerialNumberHelper($id_serial);
        if (!Validate::isLoadedObject($serialNumber)) {
            return false;
        }
        $serialNumber->status = $new_status;
        $result = $serialNumber->update();

        if ($result) {
            $serialNumber->logHistory('update_status');
        }
        return $result;
    }


    public static function assignSerialNumberToOrder($id_order_detail)
    {
        // Récupérer les informations sur le produit et sa déclinaison depuis la commande
        $sql = 'SELECT od.product_id, od.product_attribute_id, od.product_quantity
            FROM ' . _DB_PREFIX_ . 'order_detail od
            WHERE od.id_order_detail = ' . (int) $id_order_detail;
        $orderDetail = Db::getInstance()->getRow($sql);

        if (!$orderDetail) {
            return false; // Pas de produit trouvé
        }

        $assignedCount = 0; // Compteur des numéros de série attribués

        // Rechercher les numéros de série disponibles pour ce produit et cette déclinaison
        $sql = 'SELECT id_serial
            FROM ' . _DB_PREFIX_ . 'serial_numbers
            WHERE id_product = ' . (int) $orderDetail['product_id'] . '
            AND id_product_attribute = ' . (int) $orderDetail['product_attribute_id'] . '
            AND status = "available"
            AND deleted = 0
            ORDER BY date_added ASC
            LIMIT ' . (int) $orderDetail['product_quantity']; // Limite par quantité commandée
        $serialNumbers = Db::getInstance()->executeS($sql);

        if (empty($serialNumbers)) {
            return false; // Pas de numéros de série disponibles
        }

        // Boucler sur chaque numéro de série disponible et l'assigner
        foreach ($serialNumbers as $serialNumber) {
            $update = Db::getInstance()->update(
                'serial_numbers',
                [
                    'id_order_detail' => (int) $id_order_detail,
                    'status' => 'assigned',
                ],
                'id_serial = ' . (int) $serialNumber['id_serial']
            );

            if ($update) {
                $assignedCount++;
            }
        }

        // Mettre à jour le stock en fonction du nombre de numéros de série attribués
        if ($assignedCount > 0) {
            StockAvailable::updateQuantity(
                $orderDetail['product_id'],
                $orderDetail['product_attribute_id'],
                -$assignedCount // Réduction du stock
            );
        }

        return $assignedCount > 0;
    }

    public static function getOrdersWithoutSerialNumbers()
    {
        $sql = 'SELECT od.id_order, od.product_id, od.product_attribute_id, od.product_quantity
            FROM ' . _DB_PREFIX_ . 'order_detail od
            LEFT JOIN ' . _DB_PREFIX_ . 'serial_numbers sn
            ON od.id_order_detail = sn.id_order_detail
            WHERE sn.id_order_detail IS NULL
            AND od.product_quantity > 0';

        return Db::getInstance()->executeS($sql);
    }


    function createLog($message, $type = 'INFO')
    {
        $logFile = dirname(__FILE__) . '/log.txt'; // Chemin du fichier log
        $timestamp = date('Y-m-d H:i:s'); // Horodatage
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

        // Vérification de l'écriture
        try {
            $result = file_put_contents($logFile, $logMessage, FILE_APPEND);
            if ($result === false) {
                throw new Exception('Impossible d’écrire dans le fichier log.txt');
            }
        } catch (Exception $e) {
            // Envoyer l'erreur dans les logs système de PHP
            error_log('Erreur dans createLog : ' . $e->getMessage());
        }
    }



}
