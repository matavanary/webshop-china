<?php
/**
 * Order Model
 * 
 * Handles all order-related database operations
 */

namespace models;

class Order {
    private $db;
    private $table = 'orders';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        return $this->db->findOne($this->table, ['id' => $id]);
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        return $this->db->findOne($this->table, ['order_number' => $orderNumber]);
    }

    /**
     * Get orders by user ID
     */
    public function getByUserId($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $orders = $this->db->find(
            $this->table, 
            ['user_id' => $userId], 
            'created_at DESC', 
            "LIMIT {$perPage} OFFSET {$offset}"
        );

        // Get order items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        $total = $this->db->getTotalCount($this->table, ['user_id' => $userId]);

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId) {
        $sql = "
            SELECT oi.*, p.name, p.slug, p.image_url 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ";
        
        return $this->db->query($sql, [$orderId]);
    }

    /**
     * Create new order
     */
    public function create($orderData, $orderItems) {
        try {
            $this->db->beginTransaction();

            // Generate order number
            $orderData['order_number'] = $this->generateOrderNumber();
            $orderData['created_at'] = date('Y-m-d H:i:s');
            $orderData['updated_at'] = date('Y-m-d H:i:s');

            // Create order
            $orderId = $this->db->create($this->table, $orderData);

            // Create order items
            foreach ($orderItems as $item) {
                $item['order_id'] = $orderId;
                $item['total'] = $item['price'] * $item['quantity'];
                $this->db->create('order_items', $item);

                // Update product stock
                $this->updateProductStock($item['product_id'], $item['quantity']);
            }

            $this->db->commit();
            return $orderId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status) {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid order status');
        }

        return $this->db->update($this->table, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $orderId]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($paymentStatus, $validStatuses)) {
            throw new \Exception('Invalid payment status');
        }

        return $this->db->update($this->table, [
            'payment_status' => $paymentStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $orderId]);
    }

    /**
     * Cancel order
     */
    public function cancel($orderId, $reason = '') {
        $order = $this->getById($orderId);
        if (!$order) {
            throw new \Exception('Order not found');
        }

        if ($order['status'] === 'shipped' || $order['status'] === 'delivered') {
            throw new \Exception('Cannot cancel shipped or delivered order');
        }

        try {
            $this->db->beginTransaction();

            // Update order status
            $this->updateStatus($orderId, 'cancelled');

            // Restore product stock
            $orderItems = $this->getOrderItems($orderId);
            foreach ($orderItems as $item) {
                $this->updateProductStock($item['product_id'], -$item['quantity']);
            }

            // Add cancellation note
            if ($reason) {
                $notes = $order['notes'] ? $order['notes'] . "\n" : '';
                $notes .= "取消原因: {$reason}";
                $this->db->update($this->table, ['notes' => $notes], ['id' => $orderId]);
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Calculate order totals
     */
    public function calculateTotals($items, $shippingAmount = 0, $taxRate = 0, $discountAmount = 0) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber() {
        $prefix = 'WS';
        $timestamp = date('Ymd');
        
        do {
            $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $timestamp . $random;
        } while ($this->getByOrderNumber($orderNumber));

        return $orderNumber;
    }

    /**
     * Update product stock
     */
    private function updateProductStock($productId, $quantity) {
        $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND manage_stock = 1";
        return $this->db->execute($sql, [$quantity, $productId]);
    }

    /**
     * Get order statistics
     */
    public function getStatistics($userId = null, $dateFrom = null, $dateTo = null) {
        $conditions = [];
        $params = [];

        if ($userId) {
            $conditions[] = 'user_id = ?';
            $params[] = $userId;
        }

        if ($dateFrom) {
            $conditions[] = 'created_at >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = 'created_at <= ?';
            $params[] = $dateTo;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Total orders and revenue
        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value
            FROM {$this->table} 
            {$whereClause}
        ";
        
        $totals = $this->db->queryOne($sql, $params);

        // Orders by status
        $sql = "
            SELECT status, COUNT(*) as count 
            FROM {$this->table} 
            {$whereClause}
            GROUP BY status
        ";
        
        $statusCounts = $this->db->query($sql, $params);

        return [
            'total_orders' => $totals['total_orders'],
            'total_revenue' => $totals['total_revenue'],
            'average_order_value' => $totals['average_order_value'],
            'status_counts' => $statusCounts
        ];
    }

    /**
     * Get recent orders
     */
    public function getRecent($limit = 10) {
        $sql = "
            SELECT o.*, u.username, u.first_name, u.last_name 
            FROM {$this->table} o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT ?
        ";
        
        return $this->db->query($sql, [$limit]);
    }

    /**
     * Search orders
     */
    public function search($query, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $searchTerm = '%' . $query . '%';

        $sql = "
            SELECT o.*, u.username, u.first_name, u.last_name 
            FROM {$this->table} o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.order_number LIKE ? 
            OR u.username LIKE ? 
            OR u.email LIKE ? 
            OR u.first_name LIKE ? 
            OR u.last_name LIKE ?
            ORDER BY o.created_at DESC 
            LIMIT ? OFFSET ?
        ";

        $orders = $this->db->query($sql, [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $perPage, $offset
        ]);

        // Get total count for search
        $countSql = "
            SELECT COUNT(*) as total 
            FROM {$this->table} o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.order_number LIKE ? 
            OR u.username LIKE ? 
            OR u.email LIKE ? 
            OR u.first_name LIKE ? 
            OR u.last_name LIKE ?
        ";
        
        $countResult = $this->db->queryOne($countSql, [
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm
        ]);
        
        $total = $countResult['total'];

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Validate order data
     */
    public function validate($orderData, $orderItems) {
        $errors = [];

        // Validate order data
        if (empty($orderData['user_id'])) {
            $errors['user_id'] = '用户ID不能为空';
        }

        if (empty($orderData['shipping_address'])) {
            $errors['shipping_address'] = '收货地址不能为空';
        }

        if (empty($orderData['billing_address'])) {
            $errors['billing_address'] = '账单地址不能为空';
        }

        if (empty($orderItems)) {
            $errors['items'] = '订单商品不能为空';
        }

        // Validate order items
        foreach ($orderItems as $index => $item) {
            if (empty($item['product_id'])) {
                $errors["items.{$index}.product_id"] = '商品ID不能为空';
            }

            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                $errors["items.{$index}.quantity"] = '商品数量必须大于0';
            }

            if (empty($item['price']) || $item['price'] <= 0) {
                $errors["items.{$index}.price"] = '商品价格必须大于0';
            }

            // Check stock availability
            if (!empty($item['product_id']) && !empty($item['quantity'])) {
                $productModel = new Product($this->db);
                if (!$productModel->isInStock($item['product_id'], $item['quantity'])) {
                    $errors["items.{$index}.stock"] = '商品库存不足';
                }
            }
        }

        return $errors;
    }
}