<?php
/**
 * Base Model Class
 * All models should extend this class
 */

require_once __DIR__ . '/../helpers/Database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Find all records
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Create new record
     */
    public function create($data) {
        $fillableData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $fillableData['created_at'] = date('Y-m-d H:i:s');
            $fillableData['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert($this->table, $fillableData);
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        $fillableData = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $fillableData['updated_at'] = date('Y-m-d H:i:s');
        }

        $where = "{$this->primaryKey} = :id";
        return $this->db->update($this->table, $fillableData, $where, ['id' => $id]);
    }

    /**
     * Delete record
     */
    public function delete($id) {
        $where = "{$this->primaryKey} = :id";
        return $this->db->delete($this->table, $where, ['id' => $id]);
    }

    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }

    /**
     * Paginate records
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        $totalRecords = $this->count($conditions);
        $totalPages = ceil($totalRecords / $perPage);

        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                $where[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $records = $this->db->fetchAll($sql, $params);

        return [
            'data' => $records,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Filter data based on fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Execute custom query
     */
    protected function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }

    /**
     * Execute custom select query
     */
    protected function select($sql, $params = []) {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Execute custom select one query
     */
    protected function selectOne($sql, $params = []) {
        return $this->db->fetchOne($sql, $params);
    }
}