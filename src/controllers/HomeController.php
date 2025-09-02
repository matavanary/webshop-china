<?php
/**
 * Home Controller
 * Handles homepage and general pages
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class HomeController extends BaseController {

    /**
     * Display homepage
     */
    public function index() {
        try {
            $productModel = new Product();
            
            // Get featured products
            $featuredProducts = $productModel->getFeatured(12);
            
            // Get latest products
            $latestProducts = $productModel->findAll(['is_active' => 1], 'created_at DESC', 8);
            
            $this->render('home/index', [
                'featuredProducts' => $featuredProducts,
                'latestProducts' => $latestProducts,
                'title' => 'Homepage - Chinese E-commerce Website'
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Homepage error: ' . $e->getMessage());
            $this->serverError('Unable to load homepage');
        }
    }

    /**
     * Display about page
     */
    public function about() {
        $this->render('home/about', [
            'title' => 'About Us - Chinese E-commerce Website'
        ]);
    }

    /**
     * Display contact page
     */
    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleContactForm();
        } else {
            $this->render('home/contact', [
                'title' => 'Contact Us - Chinese E-commerce Website'
            ]);
        }
    }

    /**
     * Handle contact form submission
     */
    private function handleContactForm() {
        $this->validateCSRF();
        
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->validate($data, [
            'name' => 'required|max:100',
            'email' => 'required|email',
            'subject' => 'required|max:200',
            'message' => 'required|max:1000'
        ]);
        
        if (!empty($errors)) {
            $this->render('home/contact', [
                'title' => 'Contact Us - Chinese E-commerce Website',
                'errors' => $errors,
                'old' => $data
            ]);
            return;
        }
        
        // Sanitize data
        $sanitizedData = [
            'name' => Utils::sanitize($data['name']),
            'email' => Utils::sanitize($data['email']),
            'subject' => Utils::sanitize($data['subject']),
            'message' => Utils::sanitize($data['message'])
        ];
        
        try {
            // Here you would typically send an email or save to database
            // For now, we'll just log it
            $this->log('info', 'Contact form submission', $sanitizedData);
            
            $this->setFlash('success', 'Thank you for your message. We will get back to you soon!');
            $this->redirect('/contact');
            
        } catch (Exception $e) {
            $this->log('error', 'Contact form error: ' . $e->getMessage());
            $this->setFlash('error', 'There was an error sending your message. Please try again.');
            $this->redirect('/contact');
        }
    }

    /**
     * Display privacy policy
     */
    public function privacy() {
        $this->render('home/privacy', [
            'title' => 'Privacy Policy - Chinese E-commerce Website'
        ]);
    }

    /**
     * Display terms of service
     */
    public function terms() {
        $this->render('home/terms', [
            'title' => 'Terms of Service - Chinese E-commerce Website'
        ]);
    }

    /**
     * Display FAQ page
     */
    public function faq() {
        $faqs = [
            [
                'question' => 'How long does shipping from China take?',
                'answer' => 'Shipping from China typically takes 7-14 business days depending on the shipping method and destination. Express shipping options are available for faster delivery.'
            ],
            [
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept various payment methods including credit cards, PromptPay, bank transfers, and digital wallets. All payments are processed securely.'
            ],
            [
                'question' => 'Do you offer refunds?',
                'answer' => 'Yes, we offer refunds within 30 days of purchase for unused items in original condition. Please see our refund policy for detailed terms.'
            ],
            [
                'question' => 'How can I track my order?',
                'answer' => 'Once your order is shipped, you will receive a tracking number via email. You can use this to track your package on our website or the carrier\'s website.'
            ],
            [
                'question' => 'Are there any import duties or taxes?',
                'answer' => 'Import duties and taxes may apply depending on your location and the value of your order. These charges are the responsibility of the buyer.'
            ]
        ];
        
        $this->render('home/faq', [
            'title' => 'Frequently Asked Questions - Chinese E-commerce Website',
            'faqs' => $faqs
        ]);
    }

    /**
     * Handle 404 errors
     */
    public function notFound() {
        $this->notFound('The page you are looking for could not be found.');
    }

    /**
     * Search functionality
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        
        if (empty($query)) {
            $this->redirect('/');
        }
        
        try {
            $productModel = new Product();
            $results = $productModel->search($query, $page, 24);
            
            $this->render('home/search', [
                'title' => "Search Results for '{$query}' - Chinese E-commerce Website",
                'query' => $query,
                'results' => $results,
                'pagination' => $this->generatePagination($results, '/search?q=' . urlencode($query))
            ]);
            
        } catch (Exception $e) {
            $this->log('error', 'Search error: ' . $e->getMessage());
            $this->serverError('Unable to perform search');
        }
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
            $pagination .= "<a href='{$baseUrl}&page={$prevPage}' class='px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300'>Previous</a>";
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $currentPage ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300';
            $pagination .= "<a href='{$baseUrl}&page={$i}' class='px-3 py-2 {$active} rounded'>{$i}</a>";
        }
        
        // Next button
        if ($results['has_next']) {
            $nextPage = $currentPage + 1;
            $pagination .= "<a href='{$baseUrl}&page={$nextPage}' class='px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300'>Next</a>";
        }
        
        $pagination .= '</div>';
        $pagination .= '</nav>';
        
        return $pagination;
    }
}