<?php
/**
 * Logger Helper Class
 * 
 * Provides logging functionality with different levels
 */

namespace helpers;

class Logger {
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    private $logFile;
    private $db;

    public function __construct($logFile = null, $database = null) {
        $this->logFile = $logFile ?: ROOT_PATH . '/logs/app.log';
        $this->db = $database;
        $this->ensureLogDirectory();
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log emergency message
     */
    public function emergency($message, $context = []) {
        return $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     */
    public function alert($message, $context = []) {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical($message, $context = []) {
        return $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = []) {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     */
    public function notice($message, $context = []) {
        return $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     */
    public function info($message, $context = []) {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log message with specified level
     */
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        // Write to file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Write to database if available
        if ($this->db) {
            $this->logToDatabase($level, $message, $context);
        }

        return true;
    }

    /**
     * Log to database
     */
    private function logToDatabase($level, $message, $context = []) {
        try {
            $data = [
                'level' => $level,
                'message' => $message,
                'context' => json_encode($context),
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->create('system_logs', $data);
        } catch (\Exception $e) {
            // If database logging fails, write to file
            $this->log(self::ERROR, 'Failed to log to database: ' . $e->getMessage());
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get log entries from database
     */
    public function getLogEntries($level = null, $limit = 100, $offset = 0) {
        if (!$this->db) {
            return [];
        }

        $conditions = [];
        if ($level) {
            $conditions['level'] = $level;
        }

        return $this->db->find(
            'system_logs', 
            $conditions, 
            'created_at DESC', 
            "LIMIT {$limit} OFFSET {$offset}"
        );
    }

    /**
     * Get log statistics
     */
    public function getLogStats($dateFrom = null, $dateTo = null) {
        if (!$this->db) {
            return [];
        }

        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = 'created_at >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $conditions[] = 'created_at <= ?';
            $params[] = $dateTo;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT 
                level, 
                COUNT(*) as count 
            FROM system_logs 
            {$whereClause}
            GROUP BY level 
            ORDER BY count DESC
        ";

        return $this->db->query($sql, $params);
    }

    /**
     * Clear old log entries
     */
    public function clearOldLogs($days = 30) {
        if (!$this->db) {
            return false;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->delete('system_logs', [
            'created_at' => ['<', $cutoffDate]
        ]);
    }

    /**
     * Rotate log files
     */
    public function rotateLogFile($maxSize = 10485760) { // 10MB default
        if (!file_exists($this->logFile)) {
            return false;
        }

        if (filesize($this->logFile) < $maxSize) {
            return false;
        }

        $rotatedFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
        
        if (rename($this->logFile, $rotatedFile)) {
            $this->log(self::INFO, 'Log file rotated', ['old_file' => $rotatedFile]);
            return true;
        }

        return false;
    }

    /**
     * Get log file contents
     */
    public function getLogFileContents($lines = 100) {
        if (!file_exists($this->logFile)) {
            return '';
        }

        $file = file($this->logFile);
        $totalLines = count($file);
        $startLine = max(0, $totalLines - $lines);
        
        return implode('', array_slice($file, $startLine));
    }

    /**
     * Static method to create logger instance
     */
    public static function getInstance($logFile = null, $database = null) {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self($logFile, $database);
        }

        return $instance;
    }

    /**
     * Static logging methods
     */
    public static function logError($message, $context = []) {
        self::getInstance()->error($message, $context);
    }

    public static function logInfo($message, $context = []) {
        self::getInstance()->info($message, $context);
    }

    public static function logWarning($message, $context = []) {
        self::getInstance()->warning($message, $context);
    }

    public static function logDebug($message, $context = []) {
        self::getInstance()->debug($message, $context);
    }
}