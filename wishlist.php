<?php
/**
 * Wishlist Management
 * Handle adding/removing products to/from wishlist
 */

define('CMS_ROOT', __DIR__);

session_start();

require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$user_id = $_SESSION['customer_user']['id'] ?? $_SESSION['admin_user']['id'] ?? null;

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$product_id = $_POST['product_id'] ?? $_GET['product_id'] ?? null;

// Initialize wishlist from session if not logged in
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Response helper
function json_response($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Add to wishlist
if ($action === 'add' && $product_id) {
    if ($is_logged_in && $user_id) {
        // Save to database
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            // Check if already in wishlist
            $stmt = $pdo->prepare("
                SELECT id FROM customer_wishlist 
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->execute([$user_id, $product_id]);
            
            if (!$stmt->fetch()) {
                // Add to wishlist
                $stmt = $pdo->prepare("
                    INSERT INTO customer_wishlist (customer_id, product_id, created_at)
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$user_id, $product_id]);
                json_response(true, 'Добавено в любими!', ['count' => (int)$pdo->query("SELECT COUNT(*) FROM customer_wishlist WHERE customer_id = $user_id")->fetchColumn()]);
            } else {
                json_response(false, 'Вече е в любими');
            }
        } catch (PDOException $e) {
            // Table might not exist, use session fallback
            if (!in_array($product_id, $_SESSION['wishlist'])) {
                $_SESSION['wishlist'][] = $product_id;
            }
            json_response(true, 'Добавено в любими!', ['count' => count($_SESSION['wishlist'])]);
        }
    } else {
        // Use session for guests
        if (!in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'][] = $product_id;
        }
        json_response(true, 'Добавено в любими!', ['count' => count($_SESSION['wishlist'])]);
    }
}

// Remove from wishlist
if ($action === 'remove' && $product_id) {
    if ($is_logged_in && $user_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM customer_wishlist 
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->execute([$user_id, $product_id]);
            json_response(true, 'Премахнато от любими', ['count' => (int)$pdo->query("SELECT COUNT(*) FROM customer_wishlist WHERE customer_id = $user_id")->fetchColumn()]);
        } catch (PDOException $e) {
            // Use session fallback
            $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'], fn($id) => $id !== $product_id));
            json_response(true, 'Премахнато от любими', ['count' => count($_SESSION['wishlist'])]);
        }
    } else {
        // Use session for guests
        $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'], fn($id) => $id !== $product_id));
        json_response(true, 'Премахнато от любими', ['count' => count($_SESSION['wishlist'])]);
    }
}

// Toggle wishlist
if ($action === 'toggle' && $product_id) {
    if ($is_logged_in && $user_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM customer_wishlist 
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->execute([$user_id, $product_id]);
            
            if ($stmt->fetch()) {
                // Remove
                $stmt = $pdo->prepare("
                    DELETE FROM customer_wishlist 
                    WHERE customer_id = ? AND product_id = ?
                ");
                $stmt->execute([$user_id, $product_id]);
                json_response(true, 'Премахнато от любими', [
                    'in_wishlist' => false,
                    'count' => (int)$pdo->query("SELECT COUNT(*) FROM customer_wishlist WHERE customer_id = $user_id")->fetchColumn()
                ]);
            } else {
                // Add
                $stmt = $pdo->prepare("
                    INSERT INTO customer_wishlist (customer_id, product_id, created_at)
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$user_id, $product_id]);
                json_response(true, 'Добавено в любими!', [
                    'in_wishlist' => true,
                    'count' => (int)$pdo->query("SELECT COUNT(*) FROM customer_wishlist WHERE customer_id = $user_id")->fetchColumn()
                ]);
            }
        } catch (PDOException $e) {
            // Use session fallback
            if (in_array($product_id, $_SESSION['wishlist'])) {
                $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'], fn($id) => $id !== $product_id));
                json_response(true, 'Премахнато от любими', [
                    'in_wishlist' => false,
                    'count' => count($_SESSION['wishlist'])
                ]);
            } else {
                $_SESSION['wishlist'][] = $product_id;
                json_response(true, 'Добавено в любими!', [
                    'in_wishlist' => true,
                    'count' => count($_SESSION['wishlist'])
                ]);
            }
        }
    } else {
        // Use session for guests
        if (in_array($product_id, $_SESSION['wishlist'])) {
            $_SESSION['wishlist'] = array_values(array_filter($_SESSION['wishlist'], fn($id) => $id !== $product_id));
            json_response(true, 'Премахнато от любими', [
                'in_wishlist' => false,
                'count' => count($_SESSION['wishlist'])
            ]);
        } else {
            $_SESSION['wishlist'][] = $product_id;
            json_response(true, 'Добавено в любими!', [
                'in_wishlist' => true,
                'count' => count($_SESSION['wishlist'])
            ]);
        }
    }
}

// Get wishlist count
if ($action === 'count') {
    if ($is_logged_in && $user_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM customer_wishlist WHERE customer_id = $user_id")->fetchColumn();
            json_response(true, '', ['count' => $count]);
        } catch (PDOException $e) {
            json_response(true, '', ['count' => count($_SESSION['wishlist'])]);
        }
    } else {
        json_response(true, '', ['count' => count($_SESSION['wishlist'])]);
    }
}

// Check if product is in wishlist
if ($action === 'check' && $product_id) {
    if ($is_logged_in && $user_id) {
        $db = Database::getInstance();
        $pdo = $db->getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM customer_wishlist 
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->execute([$user_id, $product_id]);
            json_response(true, '', ['in_wishlist' => (bool)$stmt->fetch()]);
        } catch (PDOException $e) {
            json_response(true, '', ['in_wishlist' => in_array($product_id, $_SESSION['wishlist'])]);
        }
    } else {
        json_response(true, '', ['in_wishlist' => in_array($product_id, $_SESSION['wishlist'])]);
    }
}

// List wishlist
json_response(true, '', [
    'wishlist' => $is_logged_in ? [] : $_SESSION['wishlist'],
    'count' => count($_SESSION['wishlist'])
]);
