<?php
/**
 * Database Browser - View and manage database tables
 */

require_admin();

if (!db_enabled()) {
    echo '<div class="card card-warning">';
    echo '<h2>⚠️ Database Not Enabled</h2>';
    echo '<p>Currently using JSON file storage. To use database browser:</p>';
    echo '<ol>';
    echo '<li>Go to <a href="?section=database">Database Configuration</a></li>';
    echo '<li>Configure MySQL or PostgreSQL connection</li>';
    echo '<li>Save and return here</li>';
    echo '</ol>';
    echo '</div>';
    return;
}

$pdo = Database::getInstance()->getPDO();
$driver = Database::getInstance()->getDriver();
$selectedTable = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'tables';
$query = $_POST['query'] ?? '';
$queryResult = null;
$queryError = null;

// Get all tables
$tables = [];
try {
    if ($driver === 'pgsql') {
        $stmt = $pdo->query("
            SELECT table_name, 
                   pg_size_pretty(pg_total_relation_size('\"' || table_name || '\"')) as size
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            ORDER BY table_name
        ");
    } else {
        $stmt = $pdo->query("
            SELECT table_name, 
                   ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY table_name
        ");
    }
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $queryError = "Error fetching tables: " . $e->getMessage();
}

// Execute custom SQL query
if (!empty($query) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->query($query);
        if (stripos(trim($query), 'SELECT') === 0) {
            $queryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $affectedRows = $stmt->rowCount();
            $queryResult = "✅ Query executed successfully. Affected rows: {$affectedRows}";
        }
    } catch (PDOException $e) {
        $queryError = "Query error: " . $e->getMessage();
    }
}

// Get table data
$tableData = [];
$tableStructure = [];
$tableName = htmlspecialchars($selectedTable);

if ($selectedTable && $action === 'browse') {
    try {
        // Get row count
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$selectedTable}");
        $rowCount = $stmt->fetchColumn();
        
        // Get table data with limit
        $limit = 100;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->query("SELECT * FROM {$selectedTable} LIMIT {$limit} OFFSET {$offset}");
        $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get column info
        if ($driver === 'pgsql') {
            $stmt = $pdo->query("
                SELECT column_name, data_type, character_maximum_length, is_nullable
                FROM information_schema.columns 
                WHERE table_name = '{$selectedTable}'
                ORDER BY ordinal_position
            ");
        } else {
            $stmt = $pdo->query("DESCRIBE {$selectedTable}");
        }
        $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $queryError = "Error browsing table: " . $e->getMessage();
    }
}
?>

<style>
.db-browser { display: flex; gap: 20px; min-height: 600px; }
.db-sidebar { width: 250px; background: #f8f9fa; padding: 15px; border-radius: 8px; }
.db-content { flex: 1; }
.table-list { list-style: none; padding: 0; margin: 0; }
.table-list li { margin-bottom: 5px; }
.table-list a {
    display: block;
    padding: 8px 12px;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s;
}
.table-list a:hover { background: #e9ecef; }
.table-list a.active { background: #667eea; color: white; }
.table-icon { margin-right: 8px; }
.sql-editor {
    width: 100%;
    min-height: 150px;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 14px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
}
.result-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    font-size: 13px;
}
.result-table th {
    background: #f8f9fa;
    padding: 10px;
    text-align: left;
    border: 1px solid #dee2e6;
    font-weight: 600;
    position: sticky;
    top: 0;
}
.result-table td {
    padding: 8px 10px;
    border: 1px solid #dee2e6;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.result-table tbody tr:hover { background: #f8f9fa; }
.query-success {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.query-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-family: monospace;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}
.stat-value { font-size: 32px; font-weight: bold; margin: 10px 0; }
.stat-label { font-size: 14px; opacity: 0.9; }
.quick-queries {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.quick-query-btn {
    padding: 8px 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}
.quick-query-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.table-wrapper {
    max-height: 600px;
    overflow: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-top: 15px;
}
</style>

<div>
    <div class="section-header">
        <h2 class="section-title">🗄️ Database Browser</h2>
        <div>
            <span class="badge" style="background: #667eea;">
                <?php echo strtoupper($driver); ?>
            </span>
            <span class="badge" style="background: #28a745;">
                <?php echo count($tables); ?> Tables
            </span>
        </div>
    </div>

    <?php if ($queryError): ?>
        <div class="query-error">
            <strong>❌ Error:</strong><br>
            <?php echo htmlspecialchars($queryError); ?>
        </div>
    <?php endif; ?>

    <div class="db-browser">
        <!-- Sidebar with table list -->
        <div class="db-sidebar">
            <h3 style="margin-top: 0; font-size: 16px;">📊 Tables</h3>
            <ul class="table-list">
                <li>
                    <a href="?section=database-browser&action=query" 
                       class="<?php echo $action === 'query' ? 'active' : ''; ?>">
                        <span class="table-icon">⚡</span> SQL Query
                    </a>
                </li>
                <li style="border-bottom: 1px solid #dee2e6; margin: 10px 0;"></li>
                <?php foreach ($tables as $table): ?>
                    <li>
                        <a href="?section=database-browser&action=browse&table=<?php echo urlencode($table['table_name']); ?>"
                           class="<?php echo ($selectedTable === $table['table_name']) ? 'active' : ''; ?>">
                            <span class="table-icon">📋</span>
                            <?php echo htmlspecialchars($table['table_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main content -->
        <div class="db-content">
            <?php if ($action === 'query' || empty($action) || $action === 'tables'): ?>
                <!-- SQL Query Interface -->
                <div class="card">
                    <h3>⚡ Execute SQL Query</h3>
                    <p class="text-muted">Run SELECT, INSERT, UPDATE, DELETE or other SQL commands</p>

                    <div class="quick-queries">
                        <button class="quick-query-btn" onclick="insertQuery('SELECT * FROM products LIMIT 10')">
                            📦 Products
                        </button>
                        <button class="quick-query-btn" onclick="insertQuery('SELECT * FROM categories')">
                            📁 Categories
                        </button>
                        <button class="quick-query-btn" onclick="insertQuery('SELECT * FROM customers LIMIT 10')">
                            👥 Customers
                        </button>
                        <button class="quick-query-btn" onclick="insertQuery('SELECT * FROM orders ORDER BY created_at DESC LIMIT 20')">
                            🛒 Orders
                        </button>
                    </div>

                    <form method="POST">
                        <textarea name="query" class="sql-editor" placeholder="Enter SQL query..."><?php echo htmlspecialchars($query); ?></textarea>
                        <button type="submit" class="btn">▶️ Execute Query</button>
                        <button type="button" class="btn btn-secondary" onclick="document.querySelector('.sql-editor').value=''">🗑️ Clear</button>
                    </form>

                    <?php if (is_array($queryResult)): ?>
                        <div class="query-success">
                            ✅ Query returned <strong><?php echo count($queryResult); ?></strong> rows
                        </div>
                        <?php if (!empty($queryResult)): ?>
                            <div class="table-wrapper">
                                <table class="result-table">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($queryResult[0]) as $column): ?>
                                                <th><?php echo htmlspecialchars($column); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($queryResult as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $value): ?>
                                                    <td>
                                                        <?php 
                                                        if (is_null($value)) {
                                                            echo '<em style="color: #999;">NULL</em>';
                                                        } elseif (is_bool($value)) {
                                                            echo $value ? 'true' : 'false';
                                                        } else {
                                                            echo htmlspecialchars($value); 
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php elseif (is_string($queryResult)): ?>
                        <div class="query-success"><?php echo $queryResult; ?></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($action === 'browse' && $selectedTable): ?>
                <!-- Table Browser -->
                <div class="card">
                    <h3>📋 Table: <?php echo $tableName; ?></h3>
                    
                    <?php if (!empty($tableStructure)): ?>
                        <details style="margin-bottom: 20px;">
                            <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                📐 Table Structure (<?php echo count($tableStructure); ?> columns)
                            </summary>
                            <div style="margin-top: 10px;">
                                <table class="result-table">
                                    <thead>
                                        <tr>
                                            <?php if ($driver === 'pgsql'): ?>
                                                <th>Column</th>
                                                <th>Type</th>
                                                <th>Length</th>
                                                <th>Nullable</th>
                                            <?php else: ?>
                                                <th>Field</th>
                                                <th>Type</th>
                                                <th>Null</th>
                                                <th>Key</th>
                                                <th>Default</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tableStructure as $col): ?>
                                            <tr>
                                                <?php if ($driver === 'pgsql'): ?>
                                                    <td><strong><?php echo htmlspecialchars($col['column_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($col['data_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($col['character_maximum_length'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($col['is_nullable']); ?></td>
                                                <?php else: ?>
                                                    <td><strong><?php echo htmlspecialchars($col['Field']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($col['Type']); ?></td>
                                                    <td><?php echo htmlspecialchars($col['Null']); ?></td>
                                                    <td><?php echo htmlspecialchars($col['Key']); ?></td>
                                                    <td><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    <?php endif; ?>

                    <p class="text-muted">
                        Showing <?php echo count($tableData); ?> rows 
                        <?php if (isset($rowCount)): ?>
                            of <?php echo $rowCount; ?> total
                        <?php endif; ?>
                    </p>

                    <?php if (!empty($tableData)): ?>
                        <div class="table-wrapper">
                            <table class="result-table">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($tableData[0]) as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableData as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td title="<?php echo htmlspecialchars($value); ?>">
                                                    <?php 
                                                    if (is_null($value)) {
                                                        echo '<em style="color: #999;">NULL</em>';
                                                    } elseif (is_bool($value)) {
                                                        echo $value ? '✓ true' : '✗ false';
                                                    } else {
                                                        $display = htmlspecialchars($value);
                                                        echo strlen($display) > 100 ? substr($display, 0, 100) . '...' : $display;
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px; color: #999;">
                            📭 No data in this table
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function insertQuery(sql) {
    document.querySelector('.sql-editor').value = sql;
}
</script>
