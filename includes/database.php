<?php
/**
 * Database Layer - JSON Storage by default, MySQL/PostgreSQL optional
 * Now with environment variable support
 */

// Load environment variables
require_once __DIR__ . '/env-loader.php';

class Database {
    private static $instance = null;
    private $driver = 'json'; // 'json', 'mysql', 'pgsql', or 'sqlsrv'
    private $pdo = null;

    private function __construct() {
        // Priority 1: Environment variable DATABASE_URL (Render/Cloud platforms)
        $databaseUrl = env('DATABASE_URL', getenv('DATABASE_URL'));
        if ($databaseUrl) {
            $this->driver = 'pgsql';
            $this->connectFromUrl($databaseUrl);
            return;
        }

        // Priority 2: Individual environment variables
        $dbType = env('DB_TYPE');
        if ($dbType) {
            $config = [
                'driver' => $dbType,
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', $dbType === 'pgsql' ? 5432 : ($dbType === 'sqlsrv' ? 1433 : 3306)),
                'database' => env('DB_NAME'),
                'user' => env('DB_USER'),
                'password' => env('DB_PASSWORD'),
                'integrated_security' => env('DB_INTEGRATED_SECURITY', false)
            ];

            if ($dbType === 'mysql') {
                $this->driver = 'mysql';
                $this->connectMySQL($config);
                return;
            } elseif ($dbType === 'pgsql' || $dbType === 'postgresql') {
                $this->driver = 'pgsql';
                $this->connectPostgreSQL($config);
                return;
            } elseif ($dbType === 'sqlsrv' || $dbType === 'mssql') {
                $this->driver = 'sqlsrv';
                $this->connectMSSQL($config);
                return;
            }
        }

        // Priority 3: Config file (legacy/fallback)
        $configPath = __DIR__ . '/../config/database.json';
        $config = [];
        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $config = json_decode($content, true) ?? [];
        }

        if (!empty($config['driver'])) {
            if ($config['driver'] === 'mysql') {
                $this->driver = 'mysql';
                $this->connectMySQL($config);
            } elseif ($config['driver'] === 'pgsql' || $config['driver'] === 'postgresql') {
                $this->driver = 'pgsql';
                $this->connectPostgreSQL($config);
            } elseif ($config['driver'] === 'sqlsrv' || $config['driver'] === 'mssql') {
                $this->driver = 'sqlsrv';
                $this->connectMSSQL($config);
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connectMySQL($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['user'], $config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log('MySQL connection failed: ' . $e->getMessage());
            die('Database connection error. Please check your database configuration.');
        }
    }

    private function connectPostgreSQL($config) {
        try {
            $dsn = "pgsql:host={$config['host']};dbname={$config['database']}";
            if (!empty($config['port'])) {
                $dsn .= ";port={$config['port']}";
            }
            $this->pdo = new PDO($dsn, $config['user'], $config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PostgreSQL connection failed: ' . $e->getMessage());
            die('Database connection error. Please check your PostgreSQL configuration.');
        }
    }

    private function connectMSSQL($config) {
        try {
            $host = $config['host'] ?? 'localhost';
            $port = $config['port'] ?? 1433;
            $database = $config['database'] ?? '';
            
            // Build DSN for SQL Server
            $dsn = "sqlsrv:Server={$host},{$port};Database={$database}";
            
            // Check if using integrated security (Windows Authentication)
            if (!empty($config['integrated_security']) && $config['integrated_security']) {
                $this->pdo = new PDO($dsn);
            } else {
                $user = $config['user'] ?? '';
                $password = $config['password'] ?? '';
                $this->pdo = new PDO($dsn, $user, $password);
            }
            
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('MSSQL connection failed: ' . $e->getMessage());
            die('Database connection error. Please check your SQL Server configuration. Error: ' . $e->getMessage());
        }
    }

    private function connectFromUrl($url) {
        try {
            // Parse DATABASE_URL (format: postgres://user:pass@host:port/database)
            $parts = parse_url($url);
            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 5432;
            $database = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? '';
            $password = $parts['pass'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('DATABASE_URL connection failed: ' . $e->getMessage());
            die('Database connection error. DATABASE_URL is invalid or database is unreachable.');
        }
    }

    public function getDriver() {
        return $this->driver;
    }

    public function getPDO() {
        return $this->pdo;
    }

    // Table operations
    public function table($name) {
        if (!$this->pdo) {
            die('Database not initialized. Please check your database configuration.');
        }
        return new DatabaseTable($name, $this->pdo, $this->driver);
    }

    // Options operations
    public function getOption($key, $default = null) {
        if (!$this->pdo) {
            return $default;
        }
        $stmt = $this->pdo->prepare("SELECT option_value FROM options WHERE option_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['option_value'] : $default;
    }

    public function setOption($key, $value) {
        if (!$this->pdo) {
            return false;
        }
        $stmt = $this->pdo->prepare("
            INSERT INTO options (option_key, option_value, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (option_key) 
            DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value]);
    }

    // Convenience methods for direct operations
    public function insert($table, $data) {
        return $this->table($table)->insert($data);
    }

    public function delete($table, $id) {
        return $this->table($table)->delete($id);
    }
}

class DatabaseTable {
    private $name;
    private $pdo;
    private $driver;
    private $query;
    private $params = [];
    private $wheres = [];

    public function __construct($name, $pdo, $driver = 'mysql') {
        $this->name = $name;
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public function where($column, $operator, $value = null) {
        // If only 2 parameters, assume = operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }

    public function all() {
        if (empty($this->wheres)) {
            $stmt = $this->pdo->query("SELECT * FROM {$this->name}");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Build WHERE clause
        $where_clauses = [];
        $params = [];
        
        foreach ($this->wheres as $where) {
            $where_clauses[] = "{$where['column']} {$where['operator']} ?";
            $params[] = $where['value'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $sql = "SELECT * FROM {$this->name} WHERE {$where_sql}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        // Reset wheres for next query
        $this->wheres = [];
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first() {
        if (empty($this->wheres)) {
            $stmt = $this->pdo->query("SELECT * FROM {$this->name} LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Build WHERE clause
        $where_clauses = [];
        $params = [];
        
        foreach ($this->wheres as $where) {
            $where_clauses[] = "{$where['column']} {$where['operator']} ?";
            $params[] = $where['value'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $sql = "SELECT * FROM {$this->name} WHERE {$where_sql} LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        // Reset wheres for next query
        $this->wheres = [];
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($key, $value) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->name} WHERE {$key} = ?");
        $stmt->execute([$value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO {$this->name} ({$keys}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        if ($this->driver === 'pgsql') {
            if (isset($data['id']) && $data['id'] !== '') {
                return $data['id'];
            }
            try {
                return $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                return null;
            }
        }
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $set = implode(', ', array_map(function($k) { return "$k = ?"; }, array_keys($data)));
        $stmt = $this->pdo->prepare("UPDATE {$this->name} SET {$set} WHERE id = ?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->name} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->name}");
        return $stmt->fetchColumn();
    }
}

/**
 * Helper function to get PDO database connection
 * Used by site-settings and other modules
 * @return PDO|null
 */
function get_database() {
    $db = Database::getInstance();
    return $db->getPDO();
}
