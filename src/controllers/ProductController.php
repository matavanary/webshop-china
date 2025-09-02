<?php
/**
 * Product Controller
 * Handles product listing, details, and catalog operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController extends BaseController {

    /**
     * Display all products
     */
    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $categoryId = $_GET['category'] ?? null;
        $sortBy = $_GET['sort'] ?? 'created_at';
        $sortOrder = $_GET['order'] ?? 'DESC';
        
        try {
            $productModel = new Product();
            
            if ($categoryId) {
                $products = $productModel->getByCategory($categoryId, $page, 24);
            } else {
                $conditions = ['is_active' => 1];
                $orderBy = "{$sortBy} {$sortOrder}";
                $products = $productModel->paginate($page, 24, $conditions, $orderBy);
            }
            
            // Get categories for filter
            $db = Database::getInstance();
            $categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
            
            $this->render('products/index', [
                'title' => 'Products - Chinese E-commerce Website',
                'products' => $products,
                'categories' => $categories,
                'currentCategory' => $categoryId,
                'currentSort' => $sortBy,
                'currentOrder' => $sortOrder,
                'pagination' => $this->generatePagination($products, $this->buildFilterUrl())
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Product listing error: ' . $e->getMessage());
            $this->serverError('Unable to load products');
        }
    }

    /**
     * Display single product
     */
    public function show($slug) {
        try {
            $productModel = new Product();
            
            // Get product by slug
            $product = $productModel->getBySlug($slug);
            
            if (!$product) {
                $this->notFound('Product not found');
            }
            
            // Get product with variants
            $productWithVariants = $productModel->getWithVariants($product['id']);
            
            // Get related products
            $relatedProducts = $productModel->getRelated(
                $product['id'], 
                $product['category_id'], 
                6
            );
            
            // Get category name
            $db = Database::getInstance();
            $category = $db->fetchOne("SELECT name FROM categories WHERE id = :id", ['id' => $product['category_id']]);
            
            // Parse images
            $images = json_decode($product['images'] ?? '[]', true);
            
            $this->render('products/show', [
                'title' => $product['name'] . ' - Chinese E-commerce Website',
                'product' => $productWithVariants,
                'relatedProducts' => $relatedProducts,
                'category' => $category,
                'images' => $images,
                'finalPrice' => $productModel->getFinalPrice($product),
                'discountPercentage' => $productModel->getDiscountPercentage($product)
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Product show error: ' . $e->getMessage());
            $this->serverError('Unable to load product');
        }
    }

    /**
     * Display products by category
     */
    public function category($categorySlug) {
        try {
            $db = Database::getInstance();
            
            // Get category
            $category = $db->fetchOne("SELECT * FROM categories WHERE slug = :slug AND is_active = 1", ['slug' => $categorySlug]);
            
            if (!$category) {
                $this->notFound('Category not found');
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $sortBy = $_GET['sort'] ?? 'created_at';
            $sortOrder = $_GET['order'] ?? 'DESC';
            
            $productModel = new Product();
            $products = $productModel->getByCategory($category['id'], $page, 24);
            
            // Get subcategories
            $subcategories = $db->fetchAll(
                "SELECT * FROM categories WHERE parent_id = :parent_id AND is_active = 1 ORDER BY sort_order ASC",
                ['parent_id' => $category['id']]
            );
            
            $this->render('products/category', [
                'title' => $category['name'] . ' - Chinese E-commerce Website',
                'category' => $category,
                'subcategories' => $subcategories,
                'products' => $products,
                'currentSort' => $sortBy,
                'currentOrder' => $sortOrder,
                'pagination' => $this->generatePagination($products, "/category/{$categorySlug}")
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Category page error: ' . $e->getMessage());
            $this->serverError('Unable to load category');
        }
    }

    /**
     * Quick view product (AJAX)
     */
    public function quickView($productId) {
        if (!Utils::isAjax()) {
            $this->forbidden();
        }
        
        try {
            $productModel = new Product();
            $product = $productModel->getWithVariants($productId);
            
            if (!$product || !$product['is_active']) {
                $this->json(['error' => 'Product not found'], 404);
            }
            
            // Parse images
            $images = json_decode($product['images'] ?? '[]', true);
            
            $this->json([
                'success' => true,
                'product' => $product,
                'images' => $images,
                'finalPrice' => $productModel->getFinalPrice($product),
                'discountPercentage' => $productModel->getDiscountPercentage($product)
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Quick view error: ' . $e->getMessage());
            $this->json(['error' => 'Unable to load product'], 500);
        }
    }

    /**
     * Get product variants (AJAX)
     */
    public function getVariants($productId) {
        if (!Utils::isAjax()) {
            $this->forbidden();
        }
        
        try {
            $db = Database::getInstance();
            $variants = $db->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = :product_id AND is_active = 1 ORDER BY name ASC",
                ['product_id' => $productId]
            );
            
            $this->json([
                'success' => true,
                'variants' => $variants
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Get variants error: ' . $e->getMessage());
            $this->json(['error' => 'Unable to load variants'], 500);
        }
    }

    /**
     * Check product stock (AJAX)
     */
    public function checkStock() {
        if (!Utils::isAjax()) {
            $this->forbidden();
        }
        
        $data = $this->getRequestData();
        $productId = $data['product_id'] ?? null;
        $variantId = $data['variant_id'] ?? null;
        $quantity = max(1, (int)($data['quantity'] ?? 1));
        
        try {
            $db = Database::getInstance();
            
            if ($variantId) {
                // Check variant stock
                $variant = $db->fetchOne(
                    "SELECT stock_quantity FROM product_variants WHERE id = :id AND is_active = 1",
                    ['id' => $variantId]
                );
                $stock = $variant['stock_quantity'] ?? 0;
            } else {
                // Check product stock
                $product = $db->fetchOne(
                    "SELECT stock_quantity FROM products WHERE id = :id AND is_active = 1",
                    ['id' => $productId]
                );
                $stock = $product['stock_quantity'] ?? 0;
            }
            
            $this->json([
                'success' => true,
                'in_stock' => $stock >= $quantity,
                'available_quantity' => $stock
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Stock check error: ' . $e->getMessage());
            $this->json(['error' => 'Unable to check stock'], 500);
        }
    }

    /**
     * Build filter URL
     */
    private function buildFilterUrl() {
        $params = [];
        
        if (isset($_GET['category'])) {
            $params[] = 'category=' . urlencode($_GET['category']);
        }
        
        if (isset($_GET['sort'])) {
            $params[] = 'sort=' . urlencode($_GET['sort']);
        }
        
        if (isset($_GET['order'])) {
            $params[] = 'order=' . urlencode($_GET['order']);
        }
        
        $queryString = !empty($params) ? '?' . implode('&', $params) : '';
        return '/products' . $queryString;
    }

    /**
     * Generate pagination HTML
     */
    private function generatePagination($results, $baseUrl) {
        $pagination = '';
        $currentPage = $results['current_page'];
        $totalPages = $results['total_pages'];
        
        if ($totalPages <= 1) {
            return $pagination;
        }
        
        $pagination .= '<nav class="flex justify-center mt-8">';
        $pagination .= '<div class="flex space-x-2">';
        
        // Previous button
        if ($results['has_prev']) {
            $prevPage = $currentPage - 1;
            $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
            $pagination .= "<a href='{$baseUrl}{$separator}page={$prevPage}' class='px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors'>ก่อนหน้า</a>";
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
            $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
            $pagination .= "<a href='{$baseUrl}{$separator}page={$i}' class='px-4 py-2 {$active} rounded-lg transition-colors'>{$i}</a>";
        }
        
        // Next button
        if ($results['has_next']) {
            $nextPage = $currentPage + 1;
            $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
            $pagination .= "<a href='{$baseUrl}{$separator}page={$nextPage}' class='px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors'>ถัดไป</a>";
        }
        
        $pagination .= '</div>';
        $pagination .= '</nav>';
        
        return $pagination;
    }
}