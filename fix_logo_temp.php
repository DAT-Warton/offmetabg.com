<?php
require "includes/database.php";
$db = Database::getInstance();

echo "Clearing logo_url setting to prevent 404...\n";
$stmt = $db->prepare("UPDATE site_settings SET value = '' WHERE key = 'logo_url'");
$stmt->execute();
echo "Logo URL cleared successfully.\n";

// Verify
$stmt = $db->query("SELECT key, value FROM site_settings WHERE key = 'logo_url'");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['key'] . " = '" . $row['value'] . "'\n";
}
