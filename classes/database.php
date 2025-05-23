<?php
/**
 * Database Management Class
 * TechSavvyGenLtd Project
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->pdo->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO instance
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single column
     */
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }
        $fieldsString = implode(', ', $fields);
        
        $sql = "UPDATE {$table} SET {$fieldsString} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete record
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Execute multiple queries in transaction
     */
    public function transaction($callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($table) {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Get all tables in database
     */
    public function getTables() {
        $sql = "SHOW TABLES";
        $tables = $this->fetchAll($sql);
        return array_column($tables, 'Tables_in_' . DB_NAME);
    }
    
    /**
     * Backup database
     */
    public function backup($filename = null) {
        if (!$filename) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $backupPath = ROOT_PATH . '/backups/';
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $command = "mysqldump --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backupPath . $filename;
        exec($command, $output, $return_var);
        
        return $return_var === 0 ? $backupPath . $filename : false;
    }
    
    /**
     * Optimize database tables
     */
    public function optimize() {
        $tables = $this->getTables();
        $optimized = [];
        
        foreach ($tables as $table) {
            $sql = "OPTIMIZE TABLE {$table}";
            $result = $this->query($sql);
            $optimized[$table] = $result->fetch();
        }
        
        return $optimized;
    }
    
    /**
     * Get database size
     */
    public function getDatabaseSize() {
        $sql = "SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = ?";
        
        return $this->fetchColumn($sql, [DB_NAME]);
    }
    
    /**
     * Get table sizes
     */
    public function getTableSizes() {
        $sql = "SELECT 
                    table_name AS 'table',
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                FROM information_schema.TABLES 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC";
        
        return $this->fetchAll($sql, [DB_NAME]);
    }
    
    /**
     * Search across multiple tables
     */
    public function search($tables, $searchTerm, $fields = []) {
        $results = [];
        
        foreach ($tables as $table) {
            $tableFields = $fields[$table] ?? ['*'];
            $fieldList = implode(', ', $tableFields);
            
            // Build search conditions
            $conditions = [];
            $params = [];
            
            if ($tableFields === ['*']) {
                // Get all text fields for the table
                $structure = $this->getTableStructure($table);
                $textFields = [];
                
                foreach ($structure as $field) {
                    if (in_array($field['Type'], ['text', 'varchar', 'longtext', 'mediumtext']) ||
                        strpos($field['Type'], 'varchar') !== false ||
                        strpos($field['Type'], 'text') !== false) {
                        $textFields[] = $field['Field'];
                    }
                }
                
                foreach ($textFields as $field) {
                    $conditions[] = "{$field} LIKE ?";
                    $params[] = "%{$searchTerm}%";
                }
            } else {
                foreach ($tableFields as $field) {
                    if ($field !== '*') {
                        $conditions[] = "{$field} LIKE ?";
                        $params[] = "%{$searchTerm}%";
                    }
                }
            }
            
            if (!empty($conditions)) {
                $whereClause = implode(' OR ', $conditions);
                $sql = "SELECT {$fieldList} FROM {$table} WHERE {$whereClause}";
                
                $tableResults = $this->fetchAll($sql, $params);
                if (!empty($tableResults)) {
                    $results[$table] = $tableResults;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Clean up old records
     */
    public function cleanup($table, $dateField, $olderThan) {
        $sql = "DELETE FROM {$table} WHERE {$dateField} < ?";
        $stmt = $this->query($sql, [$olderThan]);
        return $stmt->rowCount();
    }
    
    /**
     * Get connection status
     */
    public function getConnectionStatus() {
        try {
            $this->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database version
     */
    public function getVersion() {
        return $this->fetchColumn("SELECT VERSION()");
    }
    
    /**
     * Get database statistics
     */
    public function getStatistics() {
        return [
            'version' => $this->getVersion(),
            'size_mb' => $this->getDatabaseSize(),
            'tables' => count($this->getTables()),
            'connection_status' => $this->getConnectionStatus(),
            'charset' => DB_CHARSET,
            'host' => DB_HOST,
            'database' => DB_NAME
        ];
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>