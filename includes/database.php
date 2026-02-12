<?php
/**
 * Database Layer - JSON Storage by default, MySQL optional
 */

class Database {
    private static $instance = null;
    private $driver = 'json'; // 'json', 'mysql', or 'pgsql'
    private $pdo = null;

    private function __construct() {
        // Check for DATABASE_URL environment variable first (Render PostgreSQL)
        $databaseUrl = getenv('DATABASE_URL');
        if ($databaseUrl) {
            $this->driver = 'pgsql';
            $this->connectFromUrl($databaseUrl);
            return;
        }

        // Check if database is configured via config file
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
