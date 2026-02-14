<?php
define('CMS_ROOT', __DIR__);
require_once CMS_ROOT . '/includes/database.php';

$db = Database::getInstance();
$pdo = $db->getPDO();
$stmt = $pdo->query("SELECT description FROM product_descriptions WHERE description IS NOT NULL LIMIT 3");

if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "=== DESCRIPTION ===\n";
        echo $row['description'];
        echo "\n\n=== RAW (json_encode) ===\n";
        echo json_encode($row['description'], JSON_UNESCAPED_UNICODE);
        echo "\n\n---\n\n";
    }
} else {
    echo "No descriptions found\n";
}
