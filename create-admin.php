<?php
// Create admin user  
define('CMS_ROOT', __DIR__);
require_once 'includes/functions.php';
require_once 'includes/database.php';

$db = Database::getInstance();

// Check if admin already exists
$existing = $db->table('admins')->find('username', 'Warton');
if ($existing) {
    echo "Admin user 'Warton' already exists.\n";
    exit;
}

// Create new admin
$adminId = uniqid('admin_', true);
$passwordHash = password_hash('Warton2026', PASSWORD_BCRYPT);

$data = [
    'id' => $adminId,
    'username' => 'Warton',
    'email' => 'admin@offmetabg.com',
    'password' => $passwordHash,
    'role' => 'admin'
];

try {
    $db->table('admins')->insert($data);
    echo "Admin user 'Warton' created successfully!\n";
    echo "Username: Warton\n";
    echo "Password: Warton2026\n";
} catch (Exception $e) {
    echo "Error creating admin: " . $e->getMessage() . "\n";
}
