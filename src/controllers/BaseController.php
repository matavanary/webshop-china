<?php
/**
 * Base Controller Class
 * All controllers should extend this class
 */

require_once __DIR__ . '/../helpers/Utils.php';

abstract class BaseController {
    protected $config;
    protected $user = null;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
        $this->startSession();
        $this->loadUser();
    }

    /**
     * Start session if not already started
     */
    protected function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Load current user from session
     */
    protected function loadUser() {
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $this->user = $userModel->find($_SESSION['user_id']);
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated() {
        return $this->user !== null;
    }

    /**
     * Require authentication
     */
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin() {
        return $this->user && isset($this->user['role']) && $this->user['role'] === 'admin';
    }

    /**
     * Require admin access
     */
    protected function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            $this->forbidden();
        }
    }

    /**
     * Get request data
     */
    protected function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                return $_GET;
            case 'POST':
                return $_POST;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $input = file_get_contents('php://input');
                return json_decode($input, true) ?: [];
            default:
                return [];
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRF() {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!Utils::validateCSRFToken($token)) {
            $this->forbidden('Invalid CSRF token');
        }
    }

    /**
     * Render view
     */
    protected function render($view, $data = []) {
        // Add common data
        $data['user'] = $this->user;
        $data['config'] = $this->config;
        $data['csrf_token'] = Utils::generateCSRFToken();
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: {$view}");
        }
        
        // Get the content
        $content = ob_get_clean();
        
        // Include layout if not an AJAX request
        if (!Utils::isAjax()) {
            $layoutFile = __DIR__ . '/../../views/layouts/app.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    /**
     * JSON response
     */
    protected function json($data, $statusCode = 200) {
        Utils::jsonResponse($data, $statusCode);
    }

    /**
     * Redirect
     */
    protected function redirect($url, $statusCode = 302) {
        Utils::redirect($url, $statusCode);
    }

    /**
     * Return 404 response
     */
    protected function notFound($message = 'Page not found') {
        http_response_code(404);
        $this->render('errors/404', ['message' => $message]);
        exit;
    }

    /**
     * Return 403 response
     */
    protected function forbidden($message = 'Access forbidden') {
        http_response_code(403);
        $this->render('errors/403', ['message' => $message]);
        exit;
    }

    /**
     * Return 500 response
     */
    protected function serverError($message = 'Internal server error') {
        http_response_code(500);
        $this->render('errors/500', ['message' => $message]);
        exit;
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     */
    protected function getFlash() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Validate input data
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRulesArray = explode('|', $fieldRules);
            
            foreach ($fieldRulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;
                    
                    case 'email':
                        if (!empty($value) && !Utils::validateEmail($value)) {
                            $errors[$field][] = "The {$field} field must be a valid email address.";
                        }
                        break;
                    
                    case 'min':
                        if (!empty($value) && strlen($value) < $ruleValue) {
                            $errors[$field][] = "The {$field} field must be at least {$ruleValue} characters.";
                        }
                        break;
                    
                    case 'max':
                        if (!empty($value) && strlen($value) > $ruleValue) {
                            $errors[$field][] = "The {$field} field must not exceed {$ruleValue} characters.";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }

    /**
     * Log activity
     */
    protected function log($level, $message, $context = []) {
        if ($this->user) {
            $context['user_id'] = $this->user['id'];
        }
        Utils::log($level, $message, $context);
    }
}