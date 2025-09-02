<?php
/**
 * Database Helper Class
 * 
 * Provides database connection and common database operations
 */

namespace helpers;

class Database {
    private $connection;
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $this->connection = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options'] ?? []
            );
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO connection instance
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a query and return all results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return first result
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Execute an insert query and return last insert ID
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("Insert failed: " . $e->getMessage());
        }
    }

    /**
     * Execute an update or delete query and return affected rows
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception("Execute failed: " . $e->getMessage());
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->queryOne($sql, [$tableName]);
        return !empty($result);
    }

    /**
     * Get table columns
     */
    public function getTableColumns($tableName) {
        $sql = "DESCRIBE {$tableName}";
        return $this->query($sql);
    }

    /**
     * Build WHERE clause from array conditions
     */
    public function buildWhereClause($conditions) {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $clauses[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $clauses[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        $whereClause = 'WHERE ' . implode(' AND ', $clauses);
        return [$whereClause, $params];
    }

    /**
     * Build pagination SQL
     */
    public function buildPagination($page = 1, $perPage = 20) {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        
        return "LIMIT {$perPage} OFFSET {$offset}";
    }

    /**
     * Get total count for pagination
     */
    public function getTotalCount($table, $conditions = []) {
        list($whereClause, $params) = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as total FROM {$table} {$whereClause}";
        $result = $this->queryOne($sql, $params);
        return (int)$result['total'];
    }

    /**
     * Generic find method
     */
    public function find($table, $conditions = [], $orderBy = '', $limit = '') {
        list($whereClause, $params) = $this->buildWhereClause($conditions);
        
        $sql = "SELECT * FROM {$table} {$whereClause}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " {$limit}";
        }
        
        return $this->query($sql, $params);
    }

    /**
     * Generic findOne method
     */
    public function findOne($table, $conditions = []) {
        list($whereClause, $params) = $this->buildWhereClause($conditions);
        $sql = "SELECT * FROM {$table} {$whereClause} LIMIT 1";
        return $this->queryOne($sql, $params);
    }

    /**
     * Generic save method (insert or update)
     */
    public function save($table, $data, $id = null) {
        if ($id) {
            return $this->update($table, $data, ['id' => $id]);
        } else {
            return $this->create($table, $data);
        }
    }

    /**
     * Generic create method
     */
    public function create($table, $data) {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        return $this->insert($sql, array_values($data));
    }

    /**
     * Generic update method
     */
    public function update($table, $data, $conditions) {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';
        list($whereClause, $whereParams) = $this->buildWhereClause($conditions);
        
        $sql = "UPDATE {$table} SET {$setClause} {$whereClause}";
        $params = array_merge(array_values($data), $whereParams);
        
        return $this->execute($sql, $params);
    }

    /**
     * Generic delete method
     */
    public function delete($table, $conditions) {
        list($whereClause, $params) = $this->buildWhereClause($conditions);
        $sql = "DELETE FROM {$table} {$whereClause}";
        return $this->execute($sql, $params);
    }

    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}