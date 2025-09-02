<?php
/**
 * Order Model
 * Handles order-related database operations
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/Utils.php';

class Order extends BaseModel {
    protected $table = 'orders';
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'tax_amount',
        'shipping_amount', 'discount_amount', 'total_amount', 'currency',
        'coupon_code', 'coupon_discount', 'shipping_address', 'billing_address',
        'notes', 'internal_notes', 'shipped_at', 'delivered_at'
    ];

    /**
     * Create new order
     */
    public function createOrder($data) {
        // Generate order number if not provided
        if (empty($data['order_number'])) {
            $data['order_number'] = Utils::generateOrderNumber();
        }

        // Ensure required fields have default values
        $data['status'] = $data['status'] ?? 'pending';
        $data['currency'] = $data['currency'] ?? 'THB';

        return $this->create($data);
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE order_number = :order_number LIMIT 1";
        return $this->db->fetchOne($sql, ['order_number' => $orderNumber]);
    }

    /**
     * Get orders by user
     */
    public function getByUser($userId, $page = 1, $perPage = 10) {
        return $this->paginate($page, $perPage, ['user_id' => $userId], 'created_at DESC');
    }

    /**
     * Get order with items
     */
    public function getWithItems($orderId) {
        $order = $this->find($orderId);
        if (!$order) {
            return null;
        }

        // Get order items
        $itemsSql = "SELECT oi.*, p.name as product_name, p.images 
                     FROM order_items oi 
                     LEFT JOIN products p ON oi.product_id = p.id 
                     WHERE oi.order_id = :order_id 
                     ORDER BY oi.id ASC";
        $items = $this->db->fetchAll($itemsSql, ['order_id' => $orderId]);

        $order['items'] = $items;
        return $order;
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $notes = null) {
        $data = ['status' => $status];
        
        if ($notes) {
            $data['internal_notes'] = $notes;
        }

        // Set timestamp for specific statuses
        switch ($status) {
            case 'shipped':
                $data['shipped_at'] = date('Y-m-d H:i:s');
                break;
            case 'delivered':
                $data['delivered_at'] = date('Y-m-d H:i:s');
                break;
        }

        return $this->update($orderId, $data);
    }

    /**
     * Get order statistics
     */
    public function getStatistics($startDate = null, $endDate = null) {
        $whereClause = '';
        $params = [];

        if ($startDate && $endDate) {
            $whereClause = "WHERE created_at BETWEEN :start_date AND :end_date";
            $params = [
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
        }

        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    AVG(total_amount) as average_order_value
                FROM {$this->table} {$whereClause}";

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Get recent orders
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Search orders
     */
    public function search($query, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE (o.order_number LIKE :query 
                       OR u.first_name LIKE :query 
                       OR u.last_name LIKE :query 
                       OR u.email LIKE :query) 
                ORDER BY o.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $searchQuery = "%{$query}%";
        $params = [
            'query' => $searchQuery,
            'limit' => $perPage,
            'offset' => $offset
        ];

        $orders = $this->db->fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count 
                     FROM {$this->table} o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     WHERE (o.order_number LIKE :query 
                            OR u.first_name LIKE :query 
                            OR u.last_name LIKE :query 
                            OR u.email LIKE :query)";
        $totalResult = $this->db->fetchOne($countSql, ['query' => $searchQuery]);
        $totalRecords = $totalResult['count'];
        $totalPages = ceil($totalRecords / $perPage);

        return [
            'data' => $orders,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Get orders by status
     */
    public function getByStatus($status, $page = 1, $perPage = 20) {
        return $this->paginate($page, $perPage, ['status' => $status], 'created_at DESC');
    }

    /**
     * Calculate order totals
     */
    public function calculateTotals($items, $shippingAmount = 0, $taxRate = 0.07, $discountAmount = 0) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $total
        ];
    }

    /**
     * Add item to order
     */
    public function addItem($orderId, $itemData) {
        $itemData['order_id'] = $orderId;
        return $this->db->insert('order_items', $itemData);
    }

    /**
     * Get order payments
     */
    public function getPayments($orderId) {
        $sql = "SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['order_id' => $orderId]);
    }

    /**
     * Get order shipments
     */
    public function getShipments($orderId) {
        $sql = "SELECT * FROM shipments WHERE order_id = :order_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['order_id' => $orderId]);
    }

    /**
     * Cancel order
     */
    public function cancel($orderId, $reason = null) {
        $data = [
            'status' => 'cancelled',
            'internal_notes' => $reason ? "Cancelled: {$reason}" : 'Order cancelled'
        ];

        return $this->update($orderId, $data);
    }

    /**
     * Get orders requiring attention (pending, processing)
     */
    public function getRequiringAttention() {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.status IN ('pending', 'processing') 
                ORDER BY o.created_at ASC";
        return $this->db->fetchAll($sql);
    }
}