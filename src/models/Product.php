<?php
/**
 * Product Model
 * 
 * Handles all product-related database operations
 */

namespace models;

class Product {
    private $db;
    private $table = 'products';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all products with pagination
     */
    public function getAll($page = 1, $perPage = 20, $filters = []) {
        $conditions = ['is_active' => 1];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $conditions['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            // For search, we need to use custom SQL
            return $this->search($filters['search'], $page, $perPage);
        }

        $offset = ($page - 1) * $perPage;
        $products = $this->db->find(
            $this->table, 
            $conditions, 
            'created_at DESC', 
            "LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = $this->db->getTotalCount($this->table, $conditions);

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Search products
     */
    public function search($query, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $searchTerm = '%' . $query . '%';

        $sql = "
            SELECT * FROM {$this->table} 
            WHERE is_active = 1 
            AND (name LIKE ? OR description LIKE ? OR short_description LIKE ?)
            ORDER BY 
                CASE 
                    WHEN name LIKE ? THEN 1 
                    WHEN short_description LIKE ? THEN 2 
                    ELSE 3 
                END,
                created_at DESC
            LIMIT ? OFFSET ?
        ";

        $products = $this->db->query($sql, [
            $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm,
            $perPage, $offset
        ]);

        // Get total count for search
        $countSql = "
            SELECT COUNT(*) as total FROM {$this->table} 
            WHERE is_active = 1 
            AND (name LIKE ? OR description LIKE ? OR short_description LIKE ?)
        ";
        $countResult = $this->db->queryOne($countSql, [$searchTerm, $searchTerm, $searchTerm]);
        $total = $countResult['total'];

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get product by ID
     */
    public function getById($id) {
        return $this->db->findOne($this->table, ['id' => $id, 'is_active' => 1]);
    }

    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        return $this->db->findOne($this->table, ['slug' => $slug, 'is_active' => 1]);
    }

    /**
     * Get featured products
     */
    public function getFeatured($limit = 8) {
        return $this->db->find(
            $this->table, 
            ['is_active' => 1, 'is_featured' => 1], 
            'created_at DESC', 
            "LIMIT {$limit}"
        );
    }

    /**
     * Get products by category
     */
    public function getByCategory($categoryId, $page = 1, $perPage = 20) {
        return $this->getAll($page, $perPage, ['category_id' => $categoryId]);
    }

    /**
     * Get related products
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE id != ? AND category_id = ? AND is_active = 1 
            ORDER BY RAND() 
            LIMIT ?
        ";
        return $this->db->query($sql, [$productId, $categoryId, $limit]);
    }

    /**
     * Create new product
     */
    public function create($data) {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->create($this->table, $data);
    }

    /**
     * Update product
     */
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Delete product (soft delete)
     */
    public function delete($id) {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Update stock quantity
     */
    public function updateStock($id, $quantity) {
        return $this->db->update($this->table, ['stock_quantity' => $quantity], ['id' => $id]);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock($id, $quantity = 1) {
        $product = $this->getById($id);
        if (!$product) return false;

        if (!$product['manage_stock']) return true;
        
        return $product['stock_quantity'] >= $quantity;
    }

    /**
     * Get product attributes
     */
    public function getAttributes($productId) {
        return $this->db->find('product_attributes', ['product_id' => $productId]);
    }

    /**
     * Add product attribute
     */
    public function addAttribute($productId, $name, $value) {
        return $this->db->create('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => $name,
            'attribute_value' => $value,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($name) {
        $slug = generateSlug($name);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->db->findOne($this->table, ['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get product reviews
     */
    public function getReviews($productId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT r.*, u.username, u.first_name, u.last_name 
            FROM product_reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.is_approved = 1 
            ORDER BY r.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $reviews = $this->db->query($sql, [$productId, $perPage, $offset]);
        
        $total = $this->db->getTotalCount('product_reviews', [
            'product_id' => $productId,
            'is_approved' => 1
        ]);

        return [
            'reviews' => $reviews,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get average rating
     */
    public function getAverageRating($productId) {
        $sql = "
            SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
            FROM product_reviews 
            WHERE product_id = ? AND is_approved = 1
        ";
        
        $result = $this->db->queryOne($sql, [$productId]);
        
        return [
            'average_rating' => round($result['average_rating'], 1),
            'total_reviews' => $result['total_reviews']
        ];
    }

    /**
     * Validate product data
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = '商品名称不能为空';
        }

        if (empty($data['price']) || $data['price'] <= 0) {
            $errors['price'] = '商品价格必须大于0';
        }

        if (empty($data['sku'])) {
            $errors['sku'] = '商品SKU不能为空';
        } elseif ($this->skuExists($data['sku'], $data['id'] ?? null)) {
            $errors['sku'] = 'SKU已存在';
        }

        if (!empty($data['category_id'])) {
            $category = $this->db->findOne('categories', ['id' => $data['category_id']]);
            if (!$category) {
                $errors['category_id'] = '分类不存在';
            }
        }

        return $errors;
    }

    /**
     * Check if SKU exists
     */
    private function skuExists($sku, $excludeId = null) {
        $conditions = ['sku' => $sku];
        if ($excludeId) {
            $sql = "SELECT id FROM {$this->table} WHERE sku = ? AND id != ?";
            $result = $this->db->queryOne($sql, [$sku, $excludeId]);
        } else {
            $result = $this->db->findOne($this->table, $conditions);
        }
        return !empty($result);
    }
}