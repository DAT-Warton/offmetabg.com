<?php
/**
 * Database Layer - JSON Storage by default, MySQL/PostgreSQL optional
 * Now with environment variable support
 */

// Load environment variables
require_once __DIR__ . '/env-loader.php';

class Database {
    private static $instance = null;
    private $driver = 'json'; // 'json', 'mysql', or 'pgsql'
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
                'port' => env('DB_PORT', $dbType === 'pgsql' ? 5432 : 3306),
                'database' => env('DB_NAME'),
                'user' => env('DB_USER'),
                'password' => env('DB_PASSWORD')
            ];

            if ($dbType === 'mysql') {
                $this->driver = 'mysql';
                $this->connectMySQL($config);
                return;
            } elseif ($dbType === 'pgsql' || $dbType === 'postgresql') {
                $this->driver = 'pgsql';
                $this->connectPostgreSQL($config);
                return;
            }
        }

        // Priority 3: Config file (legacy/fallback)
        $config = load_json('config/database.json');

        if (!empty($config['driver'])) {
            if ($config['driver'] === 'mysql') {
                $this->driver = 'mysql';
                $this->connectMySQL($config);
            } elseif ($config['driver'] === 'pgsql' || $config['driver'] === 'postgresql') {
                $this->driver = 'pgsql';
                $this->connectPostgreSQL($config);
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
            error_log('Database connection failed: ' . $e->getMessage());
            $this->driver = 'json'; // Fallback to JSON
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
            $this->driver = 'json'; // Fallback to JSON
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
            $this->driver = 'json'; // Fallback to JSON
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
        if (($this->driver === 'mysql' || $this->driver === 'pgsql') && $this->pdo) {
            return new DatabaseTable($name, $this->pdo, $this->driver);
        } else {
            return new JSONTable($name);
        }
    }

    // Options operations
    public function getOption($key, $default = null) {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("SELECT option_value FROM options WHERE option_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['option_value'] : $default;
        } else {
            $options = load_json('storage/options.json');
            return $options[$key] ?? $default;
        }
    }

    public function setOption($key, $value) {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare("
                INSERT INTO options (option_key, option_value, updated_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT (option_key) 
                DO UPDATE SET option_value = EXCLUDED.option_value, updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$key, $value]);
        } else {
            $options = load_json('storage/options.json');
            $options[$key] = $value;
            save_json('storage/options.json', $options);
            return true;
        }
    }

    // Convenience methods for direct operations
    public function insert($table, $data) {
        return $this->table($table)->insert($data);
    }

    public function delete($table, $id) {
        return $this->table($table)->delete($id);
    }
}

class JSONTable {
    private $name;
    private $data = [];

    public function __construct($name) {
        $this->name = $name;
        $this->load();
    }

    private function load() {
        $this->data = load_json("storage/{$this->name}.json");
    }

    private function save() {
        save_json("storage/{$this->name}.json", $this->data);
    }

    public function all() {
        return array_values($this->data);
    }

    public function find($key, $value) {
        foreach ($this->data as $row) {
            if (isset($row[$key]) && $row[$key] == $value) {
                return $row;
            }
        }
        return null;
    }

    public function where($key, $value) {
        $results = [];
        foreach ($this->data as $row) {
            if (isset($row[$key]) && $row[$key] == $value) {
                $results[] = $row;
            }
        }
        return $results;
    }

    public function insert($data) {
        $id = uniqid();
        $this->data[$id] = array_merge($data, ['id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
        $this->save();
        return $id;
    }

    public function update($id, $data) {
        if (isset($this->data[$id])) {
            $this->data[$id] = array_merge($this->data[$id], $data, ['updated_at' => date('Y-m-d H:i:s')]);
            $this->save();
            return true;
        }
        return false;
    }

    public function delete($id) {
        if (isset($this->data[$id])) {
            unset($this->data[$id]);
            $this->save();
            return true;
        }
        return false;
    }

    public function count() {
        return count($this->data);
    }

    public function truncate() {
        $this->data = [];
        $this->save();
        return true;
    }
}

class DatabaseTable {
    private $name;
    private $pdo;
    private $driver;
    private $query;
    private $params = [];

    public function __construct($name, $pdo, $driver = 'mysql') {
        $this->name = $name;
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT * FROM {$this->name}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
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
