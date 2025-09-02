<?php
/**
 * Product Model
 * Handles product-related database operations
 */

require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';
    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'description', 'short_description',
        'base_price', 'sale_price', 'cost_price', 'weight', 'dimensions',
        'stock_quantity', 'min_stock_level', 'max_stock_level', 'is_digital',
        'is_featured', 'is_active', 'meta_title', 'meta_description', 'images',
        'tags', 'brand', 'origin_country'
    ];

    /**
     * Get featured products
     */
    public function getFeatured($limit = 12) {
        $sql = "SELECT * FROM {$this->table} WHERE is_featured = 1 AND is_active = 1 ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Get products by category
     */
    public function getByCategory($categoryId, $page = 1, $perPage = 24) {
        return $this->paginate($page, $perPage, ['category_id' => $categoryId, 'is_active' => 1], 'created_at DESC');
    }

    /**
     * Search products
     */
    public function search($query, $page = 1, $perPage = 24) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :query OR description LIKE :query OR tags LIKE :query) 
                AND is_active = 1 
                ORDER BY name ASC 
                LIMIT :limit OFFSET :offset";
        
        $searchQuery = "%{$query}%";
        $params = [
            'query' => $searchQuery,
            'limit' => $perPage,
            'offset' => $offset
        ];

        $products = $this->db->fetchAll($sql, $params);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} 
                     WHERE (name LIKE :query OR description LIKE :query OR tags LIKE :query) 
                     AND is_active = 1";
        $totalResult = $this->db->fetchOne($countSql, ['query' => $searchQuery]);
        $totalRecords = $totalResult['count'];
        $totalPages = ceil($totalRecords / $perPage);

        return [
            'data' => $products,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND is_active = 1 LIMIT 1";
        return $this->db->fetchOne($sql, ['slug' => $slug]);
    }

    /**
     * Get related products
     */
    public function getRelated($productId, $categoryId, $limit = 6) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE category_id = :category_id 
                AND id != :product_id 
                AND is_active = 1 
                ORDER BY RAND() 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            'category_id' => $categoryId,
            'product_id' => $productId,
            'limit' => $limit
        ]);
    }

    /**
     * Update stock quantity
     */
    public function updateStock($productId, $quantity) {
        $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity + :quantity WHERE id = :id";
        return $this->db->query($sql, ['quantity' => $quantity, 'id' => $productId]);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock($productId, $requiredQuantity = 1) {
        $product = $this->find($productId);
        return $product && $product['stock_quantity'] >= $requiredQuantity;
    }

    /**
     * Get low stock products
     */
    public function getLowStock() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE stock_quantity <= min_stock_level 
                AND is_active = 1 
                ORDER BY stock_quantity ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get products with variants
     */
    public function getWithVariants($productId) {
        $product = $this->find($productId);
        if (!$product) {
            return null;
        }

        // Get variants
        $variantsSql = "SELECT * FROM product_variants WHERE product_id = :product_id AND is_active = 1 ORDER BY name ASC";
        $variants = $this->db->fetchAll($variantsSql, ['product_id' => $productId]);

        $product['variants'] = $variants;
        return $product;
    }

    /**
     * Generate unique SKU
     */
    public function generateSKU($prefix = 'PRD') {
        do {
            $sku = $prefix . date('Ymd') . mt_rand(1000, 9999);
            $existing = $this->db->fetchOne("SELECT id FROM {$this->table} WHERE sku = :sku", ['sku' => $sku]);
        } while ($existing);

        return $sku;
    }

    /**
     * Calculate final price (considering sale price)
     */
    public function getFinalPrice($product) {
        if (isset($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['base_price']) {
            return $product['sale_price'];
        }
        return $product['base_price'];
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage($product) {
        if (isset($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $product['base_price']) {
            return round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100);
        }
        return 0;
    }
}