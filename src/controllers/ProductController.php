<?php
/**
 * Product Controller
 * 
 * Handles product-related HTTP requests
 */

namespace controllers;

use models\Product;

class ProductController {
    private $productModel;

    public function __construct($database) {
        $this->productModel = new Product($database);
    }

    /**
     * Display product listing page
     */
    public function index() {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $categoryId = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;

        $filters = [];
        if ($categoryId) $filters['category_id'] = $categoryId;
        if ($search) $filters['search'] = $search;

        $result = $this->productModel->getAll($page, $perPage, $filters);
        
        // Set page variables for template
        $GLOBALS['pageTitle'] = $search ? "搜索结果: {$search}" : "所有商品";
        $GLOBALS['products'] = $result['products'];
        $GLOBALS['pagination'] = getPaginationInfo(
            $result['total'], 
            $page, 
            $perPage
        );

        return $result;
    }

    /**
     * Display single product page
     */
    public function show() {
        $id = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;

        if (!$id && !$slug) {
            $this->notFound();
            return;
        }

        $product = $id ? 
            $this->productModel->getById($id) : 
            $this->productModel->getBySlug($slug);

        if (!$product) {
            $this->notFound();
            return;
        }

        // Get related products
        $relatedProducts = $this->productModel->getRelated(
            $product['id'], 
            $product['category_id']
        );

        // Get product reviews and rating
        $reviews = $this->productModel->getReviews($product['id'], 1, 5);
        $rating = $this->productModel->getAverageRating($product['id']);

        // Get product attributes
        $attributes = $this->productModel->getAttributes($product['id']);

        // Set page variables
        $GLOBALS['pageTitle'] = $product['name'];
        $GLOBALS['pageDescription'] = $product['short_description'] ?? $product['description'];
        $GLOBALS['product'] = $product;
        $GLOBALS['relatedProducts'] = $relatedProducts;
        $GLOBALS['productReviews'] = $reviews;
        $GLOBALS['productRating'] = $rating;
        $GLOBALS['productAttributes'] = $attributes;

        return $product;
    }

    /**
     * Search products via AJAX
     */
    public function search() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        $query = $_GET['q'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min(50, (int)($_GET['per_page'] ?? 20));

        if (strlen($query) < 2) {
            return ['error' => 'Query too short'];
        }

        $result = $this->productModel->search($query, $page, $perPage);
        
        header('Content-Type: application/json');
        return json_encode($result);
    }

    /**
     * Get featured products
     */
    public function featured() {
        $limit = min(20, (int)($_GET['limit'] ?? 8));
        $products = $this->productModel->getFeatured($limit);
        
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            header('Content-Type: application/json');
            return json_encode(['products' => $products]);
        }

        $GLOBALS['featuredProducts'] = $products;
        return $products;
    }

    /**
     * Check product availability
     */
    public function checkAvailability() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        $productId = $_POST['product_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);

        if (!$productId) {
            return ['error' => 'Product ID required'];
        }

        $product = $this->productModel->getById($productId);
        if (!$product) {
            return ['error' => 'Product not found'];
        }

        $available = $this->productModel->isInStock($productId, $quantity);
        
        header('Content-Type: application/json');
        return json_encode([
            'available' => $available,
            'stock_quantity' => $product['stock_quantity'],
            'manage_stock' => (bool)$product['manage_stock']
        ]);
    }

    /**
     * Add product review
     */
    public function addReview() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        if (!isLoggedIn()) {
            http_response_code(401);
            return ['error' => 'Login required'];
        }

        $productId = $_POST['product_id'] ?? null;
        $rating = (int)($_POST['rating'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $review = sanitize($_POST['review'] ?? '');

        // Validation
        $errors = [];
        if (!$productId) $errors['product_id'] = 'Product ID required';
        if ($rating < 1 || $rating > 5) $errors['rating'] = 'Rating must be between 1 and 5';
        if (strlen($title) < 3) $errors['title'] = 'Title must be at least 3 characters';
        if (strlen($review) < 10) $errors['review'] = 'Review must be at least 10 characters';

        if (!empty($errors)) {
            return ['error' => 'Validation failed', 'errors' => $errors];
        }

        // Check if product exists
        $product = $this->productModel->getById($productId);
        if (!$product) {
            return ['error' => 'Product not found'];
        }

        // Check if user already reviewed this product
        global $db;
        $existingReview = $db->findOne('product_reviews', [
            'product_id' => $productId,
            'user_id' => $_SESSION['user_id']
        ]);

        if ($existingReview) {
            return ['error' => 'You have already reviewed this product'];
        }

        // Create review
        $reviewId = $db->create('product_reviews', [
            'product_id' => $productId,
            'user_id' => $_SESSION['user_id'],
            'rating' => $rating,
            'title' => $title,
            'review' => $review,
            'is_verified_purchase' => $this->isVerifiedPurchase($productId, $_SESSION['user_id']),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        header('Content-Type: application/json');
        return json_encode(['success' => true, 'review_id' => $reviewId]);
    }

    /**
     * Get product by category API
     */
    public function byCategory() {
        $categoryId = $_GET['category_id'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min(50, (int)($_GET['per_page'] ?? 20));

        if (!$categoryId) {
            return ['error' => 'Category ID required'];
        }

        $result = $this->productModel->getByCategory($categoryId, $page, $perPage);
        
        header('Content-Type: application/json');
        return json_encode($result);
    }

    /**
     * Handle 404 error
     */
    private function notFound() {
        http_response_code(404);
        $GLOBALS['pageTitle'] = '商品未找到';
        include __DIR__ . '/../../public/pages/404.php';
    }

    /**
     * Check if purchase is verified
     */
    private function isVerifiedPurchase($productId, $userId) {
        global $db;
        
        $sql = "
            SELECT COUNT(*) as count 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.product_id = ? 
            AND o.user_id = ? 
            AND o.status IN ('delivered', 'completed')
        ";
        
        $result = $db->queryOne($sql, [$productId, $userId]);
        return $result['count'] > 0;
    }

    /**
     * Validate product data
     */
    private function validateProductData($data) {
        return $this->productModel->validate($data);
    }
}