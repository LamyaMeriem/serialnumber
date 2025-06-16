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

class SerialNumberHelper extends ObjectModel
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
     * @param bool $autoDate
     * @param bool $nullValues
     * @return bool
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->date_added = date('Y-m-d H:i:s');
        $this->deleted = 0;
        
        // Définir l'utilisateur qui ajoute
        if (Context::getContext()->employee) {
            $this->added_by = (int) Context::getContext()->employee->id;
        }

        $result = parent::add($autoDate, $nullValues);

        if ($result) {
            $this->logHistory('added');
        }
        
        return $result;
    }

    /**
     * Met à jour un numéro de série
     *
     * @param bool $nullValues
     * @return bool
     */
    public function update($nullValues = false)
    {
        $this->date_modified = date('Y-m-d H:i:s');
        
        // Définir l'utilisateur qui modifie
        if (Context::getContext()->employee) {
            $this->modified_by = (int) Context::getContext()->employee->id;
        }

        $result = parent::update($nullValues);

        if ($result) {
            $this->logHistory('modified');
        }
        
        return $result;
    }

    /**
     * Supprime un numéro de série (soft delete)
     *
     * @return bool
     */
    public function delete()
    {
        // Marquer le numéro de série comme supprimé
        $this->deleted = 1;
        $this->date_deleted = date('Y-m-d H:i:s');
        $this->status = 'deleted';
        
        // Définir l'utilisateur qui supprime
        if (Context::getContext()->employee) {
            $this->deleted_by = (int) Context::getContext()->employee->id;
        }

        $result = $this->update();

        if ($result) {
            $this->logHistory('deleted');
        }

        return $result;
    }

    /**
     * Synchronise le stock avec les numéros de série disponibles
     *
     * @param int $id_product
     * @param int|null $id_product_attribute
     * @return bool
     */
    public static function synchronizeStock($id_product, $id_product_attribute = null)
    {
        try {
            // Compter les numéros de série disponibles
            $sql = 'SELECT COUNT(*)
                FROM ' . _DB_PREFIX_ . 'serial_numbers
                WHERE id_product = ' . (int) $id_product . '
                  AND status = "available"
                  AND deleted = 0';

            if ($id_product_attribute !== null && $id_product_attribute > 0) {
                $sql .= ' AND id_product_attribute = ' . (int) $id_product_attribute;
            } else {
                $sql .= ' AND (id_product_attribute = 0 OR id_product_attribute IS NULL)';
            }

            $quantity = (int) Db::getInstance()->getValue($sql);

            // Mettre à jour la table stock_available
            StockAvailable::setQuantity(
                $id_product,
                $id_product_attribute,
                $quantity,
                Context::getContext()->shop->id
            );

            self::createLog(
                'Stock synchronisé pour le produit ID: ' . $id_product . 
                ' (Déclinaison: ' . ($id_product_attribute ?: 'aucune') . ') - Quantité: ' . $quantity,
                'INFO'
            );

            return true;

        } catch (Exception $e) {
            self::createLog('Erreur lors de la synchronisation du stock: ' . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Enregistre une action dans l'historique
     *
     * @param string $action
     */
    private function logHistory($action)
    {
        try {
            $employeeId = Context::getContext()->employee ? (int) Context::getContext()->employee->id : null;
            
            $data = [
                'id_serial' => (int) $this->id,
                'action' => pSQL($action),
                'user_id' => $employeeId,
                'date_action' => date('Y-m-d H:i:s'),
                'details' => json_encode([
                    'serial_number' => $this->serial_number,
                    'status' => $this->status,
                    'product_id' => $this->id_product,
                    'product_attribute_id' => $this->id_product_attribute,
                ])
            ];
            
            Db::getInstance()->insert('serial_numbers_history', $data);
            
        } catch (Exception $e) {
            self::createLog('Erreur lors de l\'enregistrement de l\'historique: ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Récupère les numéros de série pour un produit spécifique
     *
     * @param int $id_product
     * @param int|null $id_product_attribute
     * @param bool $includeDeleted
     * @return array
     */
    public static function getSerialNumbersByProduct($id_product, $id_product_attribute = null, $includeDeleted = false)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'serial_numbers 
                WHERE id_product = ' . (int) $id_product;

        if ($id_product_attribute !== null) {
            $sql .= ' AND id_product_attribute = ' . (int) $id_product_attribute;
        }

        if (!$includeDeleted) {
            $sql .= ' AND deleted = 0';
        }

        $sql .= ' ORDER BY date_added DESC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Valide un numéro de série
     *
     * @param string $serial_number
     * @return bool
     */
    public static function validateSerialNumber($serial_number)
    {
        // Récupérer le format configuré ou utiliser le format par défaut
        $format = Configuration::get('SERIALNUMBER_FORMAT', '/^[A-Z0-9]{10}$/');
        
        if (empty($format)) {
            $format = '/^[A-Z0-9]{10}$/';
        }

        return preg_match($format, $serial_number);
    }

    /**
     * Met à jour le statut d'un numéro de série
     *
     * @param int $id_serial
     * @param string $new_status
     * @return bool
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
            $serialNumber->logHistory('status_changed');
        }
        
        return $result;
    }

    /**
     * Assigne des numéros de série à une commande
     *
     * @param int $id_order_detail
     * @return int|false Nombre de numéros assignés ou false en cas d'erreur
     */
    public static function assignSerialNumberToOrder($id_order_detail)
    {
        try {
            // Récupérer les informations de la commande
            $sql = 'SELECT od.product_id, od.product_attribute_id, od.product_quantity
                FROM ' . _DB_PREFIX_ . 'order_detail od
                WHERE od.id_order_detail = ' . (int) $id_order_detail;
            
            $orderDetail = Db::getInstance()->getRow($sql);

            if (!$orderDetail) {
                self::createLog('Détail de commande introuvable pour ID: ' . $id_order_detail, 'ERROR');
                return false;
            }

            $id_product = (int) $orderDetail['product_id'];
            $id_product_attribute = (int) $orderDetail['product_attribute_id'];
            $quantity_needed = (int) $orderDetail['product_quantity'];

            self::createLog('Début du traitement pour la commande ID: ' . $id_order_detail, 'INFO');
            self::createLog('Produit ID: ' . $id_product . ' - Quantité : ' . $quantity_needed, 'DEBUG');

            // Rechercher les numéros de série disponibles
            $sql = 'SELECT id_serial
                FROM ' . _DB_PREFIX_ . 'serial_numbers
                WHERE id_product = ' . $id_product . '
                  AND id_product_attribute = ' . $id_product_attribute . '
                  AND status = "available"
                  AND deleted = 0
                  AND active = 1
                ORDER BY date_added ASC
                LIMIT ' . $quantity_needed;

            $availableSerials = Db::getInstance()->executeS($sql);

            if (empty($availableSerials)) {
                self::createLog('Pas de numéros de série disponibles pour le produit ID: ' . $id_product, 'ERROR');
                return false;
            }

            $assignedCount = 0;

            // Assigner chaque numéro de série
            foreach ($availableSerials as $serial) {
                $update = Db::getInstance()->update(
                    'serial_numbers',
                    [
                        'id_order_detail' => (int) $id_order_detail,
                        'status' => 'assigned',
                        'date_modified' => date('Y-m-d H:i:s'),
                    ],
                    'id_serial = ' . (int) $serial['id_serial']
                );

                if ($update) {
                    $assignedCount++;
                }
            }

            if ($assignedCount > 0) {
                self::createLog('Numéros de série assignés pour le produit ID: ' . $id_product, 'INFO');
                
                // Synchroniser le stock
                self::synchronizeStock($id_product, $id_product_attribute);
            }

            self::createLog('Fin du traitement pour la commande ID: ' . $id_order_detail, 'INFO');

            return $assignedCount;

        } catch (Exception $e) {
            self::createLog('Erreur lors de l\'assignation: ' . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Récupère les commandes sans numéros de série assignés
     *
     * @return array
     */
    public static function getOrdersWithoutSerialNumbers()
    {
        $sql = 'SELECT od.id_order_detail, od.id_order, od.product_id, od.product_attribute_id, od.product_quantity
            FROM ' . _DB_PREFIX_ . 'order_detail od
            LEFT JOIN ' . _DB_PREFIX_ . 'serial_numbers sn ON od.id_order_detail = sn.id_order_detail
            WHERE sn.id_order_detail IS NULL
              AND od.product_quantity > 0';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Crée une entrée de log
     *
     * @param string $message
     * @param string $type
     */
    public static function createLog($message, $type = 'INFO')
    {
        $logFile = dirname(__FILE__) . '/log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

        try {
            $result = file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            if ($result === false) {
                throw new Exception('Impossible d\'écrire dans le fichier log.txt');
            }
        } catch (Exception $e) {
            error_log('Erreur dans createLog : ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si un numéro de série existe déjà
     *
     * @param string $serial_number
     * @param int|null $exclude_id
     * @return bool
     */
    public static function serialNumberExists($serial_number, $exclude_id = null)
    {
        $sql = 'SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'serial_numbers 
                WHERE serial_number = "' . pSQL($serial_number) . '" 
                  AND deleted = 0';

        if ($exclude_id) {
            $sql .= ' AND id_serial != ' . (int) $exclude_id;
        }

        return (bool) Db::getInstance()->getValue($sql);
    }

    /**
     * Libère un numéro de série (le remet en statut available)
     *
     * @param int $id_serial
     * @return bool
     */
    public static function liberateSerialNumber($id_serial)
    {
        $serialNumber = new SerialNumberHelper($id_serial);
        if (!Validate::isLoadedObject($serialNumber)) {
            return false;
        }

        $serialNumber->status = 'available';
        $serialNumber->id_order_detail = null;
        
        $result = $serialNumber->update();

        if ($result) {
            // Resynchroniser le stock
            self::synchronizeStock($serialNumber->id_product, $serialNumber->id_product_attribute);
        }

        return $result;
    }
}