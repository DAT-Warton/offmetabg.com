<?php
/**
 * Shop Homepage - Product Listing
 */

// Load language system
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/../includes/icons.php';

// Get products
$products = load_json('storage/products.json');
$published_products = array_filter($products, function($p) {
    return ($p['status'] ?? 'published') === 'published';
});

// Sort by created date (newest first) and limit to 6
usort($published_products, function($a, $b) {
    $dateA = $a['created'] ?? '2000-01-01';
    $dateB = $b['created'] ?? '2000-01-01';
    return strtotime($dateB) - strtotime($dateA);
});
$published_products = array_slice($published_products, 0, 6);

// Get customer info if logged in
$is_logged_in = isset($_SESSION['customer_user']) || isset($_SESSION['admin_user']);
$user_name = $_SESSION['customer_user'] ?? $_SESSION['admin_user'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';

// Get cart count
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'quantity'));

// Get categories
$categories = load_json('storage/categories.json');
$activeCategories = array_filter($categories, function($cat) {
    return $cat['active'] ?? true;
});
usort($activeCategories, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_option('site_title', __('site_name'))); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f7ff;
            color: #2d1b4e;
        }

        /* Header */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 22px;
            color: #6b46c1;
            font-weight: 700;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6b46c1 0%, #7c3aed 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 70, 193, 0.4);
        }

        .btn-cart {
            background: #c084fc;
            color: white;
            position: relative;
        }

        .btn-cart:hover {
            background: #a855f7;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #6b46c1 0%, #4338ca 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .hero h2 {
            font-size: 36px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .hero p {
            font-size: 16px;
            opacity: 0.95;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 30px;
        }

        .section-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
            color: #2c3e50;
        }

        /* Categories */
        .categories-section {
            background: white;
            padding: 30px 0;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .category-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px 25px;
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }

        .category-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .category-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .category-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .category-icon {
            font-size: 52px;
            margin-bottom: 15px;
        }

        .category-name {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .category-desc {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
        }

        /* Products */
        .products-section {
            background: #f5f7fa;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.4s;
            border: 3px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }

        .product-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .product-info {
            padding: 30px;
        }

        .product-category {
            display: inline-block;
            padding: 6px 14px;
            background: #e0e7ff;
            color: #667eea;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .product-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2c3e50;
        }

        .product-description {
            color: #6b7280;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }

        .product-price {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .product-price .currency {
            font-size: 22px;
        }

        .btn-buy {
            padding: 14px 28px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-buy:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-buy:disabled {
            background: #d1d5db;
            cursor: not-allowed;
        }

        .stock-indicator {
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
            color: #10b981;
        }

        .stock-indicator.low {
            color: #f59e0b;
        }

        .stock-indicator.out {
            color: #ef4444;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        footer p {
            opacity: 0.8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 30px;
        }

        .empty-state h3 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .hero h2 {
                font-size: 32px;
            }

            .products-grid, .categories-grid {
                grid-template-columns: 1fr;
            }
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f3f4f6;
        }

        .btn-cart {
            position: relative;
            background: #10b981;
            color: white;
            padding: 8px 12px;
            flex-shrink: 0;
        }

        .btn-cart:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .theme-toggle,
        .lang-toggle {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            white-space: nowrap;
            min-width: auto;
            flex-shrink: 0;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .theme-toggle:hover,
        .lang-toggle:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .user-info {
            color: #667eea;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,122.7C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom center no-repeat;
            background-size: cover;
            opacity: 0.3;
        }

        .hero > * {
            position: relative;
            z-index: 1;
        }

        .hero h2 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: fadeInDown 0.6s ease;
        }

        .hero p {
            font-size: 16px;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
            animation: fadeInUp 0.6s ease 0.2s both;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Products */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .section-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 40px;
            color: #1f2937;
            text-align: center;
            position: relative;
            padding-bottom: 20px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f3f4f6;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: #667eea;
        }

        .product-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            z-index: 10;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .badge-video {
            background: rgba(102, 126, 234, 0.95);
            color: white;
        }

        .badge-stock {
            background: rgba(239, 68, 68, 0.95);
            color: white;
            top: 48px;
        }

        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .product-card:hover .product-overlay {
            opacity: 1;
        }

        .btn-quick-view {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            transform: translateY(10px);
        }

        .product-card:hover .btn-quick-view {
            transform: translateY(0);
        }

        .btn-quick-view:hover {
            background: #667eea;
            color: white;
        }

        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-category-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stock-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .stock-indicator.in-stock {
            color: #10b981;
        }

        .stock-indicator.out-of-stock {
            color: #ef4444;
        }

        .category-header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .category-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 8px;
            text-align: center;
        }

        .view-all-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.2s;
            background: #f0f7ff;
        }

        .view-all-link:hover {
            background: #667eea;
            color: white;
            transform: translateX(4px);
        }

        .category-products-section {
            margin-bottom: 60px;
        }

        .product-info {
            padding: 24px;
        }

        .product-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1f2937;
            line-height: 1.3;
        }

        .product-description {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 16px;
            border-top: 1px solid #f3f4f6;
        }

        .product-price {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-add-cart {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6b46c1 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(107, 70, 193, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-add-cart:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(107, 70, 193, 0.4);
        }

        .btn-add-cart:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            box-shadow: none;
        }

        .product-videos {
            margin-top: 15px;
        }

        .product-videos summary {
            cursor: pointer;
            color: #667eea;
            font-weight: 600;
            padding: 10px;
            background: #f0f7ff;
            border-radius: 6px;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .product-videos summary:hover {
            background: #dbeafe;
        }

        .product-videos summary::-webkit-details-marker {
            display: none;
        }

        .video-container {
            position: relative;
            margin-bottom: 20px;
        }

        .video-container iframe {
            border-radius: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .empty-state h3 {
            font-size: 28px;
            margin-bottom: 16px;
            color: #1f2937;
            font-weight: 700;
        }

        .empty-state p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 24px;
        }

        .empty-state-icon {
            font-size: 72px;
            margin-bottom: 24px;
            opacity: 0.5;
        }

        /* Footer */
        footer {
            background: #1f2937;
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-section p {
            color: #9ca3af;
            line-height: 1.6;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #374151;
            color: #9ca3af;
        }

        /* Categories Section */
        .categories-section {
            background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
            padding: 25px 0;
        }

        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .categories-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .categories-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary, #1f2937);
            margin-bottom: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .categories-header p {
            color: var(--text-secondary, #6b7280);
            font-size: 13px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .category-card {
            position: relative;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(107, 70, 193, 0.15);
            border-color: #6b46c1;
        }

        .category-preview-image {
            width: 100%;
            height: 160px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: transform 0.3s;
        }

        .category-card:hover .category-preview-image {
            transform: scale(1.05);
        }

        .category-info {
            padding: 16px;
            background: white;
        }

        .category-name {
            font-weight: 700;
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .category-desc {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }

        .category-count {
            font-size: 11px;
            color: #667eea;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
        }

        .no-categories {
            text-align: center;
            padding: 60px 30px;
            color: var(--text-secondary, #6b7280);
        }

        .no-categories-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
                gap: 12px;
                padding: 12px 15px;
            }
            
            .logo {
                flex: 0 0 100%;
                text-align: center;
            }
            
            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
                gap: 6px;
                width: 100%;
            }
            
            .btn {
                padding: 7px 10px;
                font-size: 11px;
                gap: 4px;
            }
            
            .theme-toggle,
            .lang-toggle {
                padding: 7px 10px;
                font-size: 11px;
            }
            
            .user-info {
                font-size: 11px;
                width: 100%;
                justify-content: center;
                padding: 6px;
            }
            
            .hero {
                padding: 30px 20px;
            }
            
            .hero h2 {
                font-size: 24px;
            }

            .hero p {
                font-size: 14px;
            }

            .categories-header h3 {
                font-size: 18px;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .category-preview-image {
                height: 140px;
            }

            .category-info {
                padding: 12px;
            }

            .category-name {
                font-size: 14px;
            }

            .category-desc {
                font-size: 12px;
            }

            .section-title {
                font-size: 28px;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-image {
                height: 300px;
            }

            .category-header-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .view-all-link {
                width: 100%;
                justify-content: center;
            }

            .product-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .btn-add-cart {
                width: 100%;
                justify-content: center;
            }
        }

        /* Dark Theme Styles */
        body[data-theme="dark"] {
            background: #0f0a1a !important;
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] header {
            background: #1a1625 !important;
            border-bottom: 1px solid #2d1b4e !important;
        }

        body[data-theme="dark"] .logo h1 {
            color: #c084fc !important;
        }

        body[data-theme="dark"] .hero {
            background: linear-gradient(135deg, #2d1b4e 0%, #1e1b4b 100%) !important;
        }

        body[data-theme="dark"] .container {
            background: transparent !important;
        }

        body[data-theme="dark"] .section-title {
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] .product-card {
            background: #1a1625 !important;
            border-color: #2d1b4e !important;
        }

        body[data-theme="dark"] .product-title {
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] .product-description {
            color: #c8c0d8 !important;
        }

        body[data-theme="dark"] .empty-state {
            background: #1a1625 !important;
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] .empty-state h3 {
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] .empty-state p {
            color: #c8c0d8 !important;
        }

        body[data-theme="dark"] .categories-section {
            background: linear-gradient(180deg, #0f0a1a 0%, #1a1625 100%) !important;
        }

        body[data-theme="dark"] .category-card {
            background: #1a1625 !important;
            border-color: #2d1b4e !important;
        }

        body[data-theme="dark"] .category-info {
            background: #1a1625 !important;
        }

        body[data-theme="dark"] .category-name {
            color: #e8e4f0 !important;
        }

        body[data-theme="dark"] .category-desc {
            color: #c8c0d8 !important;
        }

        body[data-theme="dark"] .category-card:hover {
            border-color: #6b46c1 !important;
            box-shadow: 0 12px 24px rgba(192, 132, 252, 0.15) !important;
        }

        body[data-theme="dark"] .btn-secondary {
            background: #1a1625 !important;
            color: #c084fc !important;
            border-color: #6b46c1 !important;
        }

        body[data-theme="dark"] .btn-primary {
            background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%) !important;
        }

        body[data-theme="dark"] .btn-cart {
            background: #fbbf24 !important;
            color: #0f0a1a !important;
        }

        body[data-theme="dark"] .btn-cart:hover {
            background: #f59e0b !important;
        }

        body[data-theme="dark"] .btn-add-cart {
            background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%) !important;
        }

        body[data-theme="dark"] .theme-toggle,
        body[data-theme="dark"] .lang-toggle {
            background: #1a1625 !important;
            color: #c084fc !important;
            border-color: #6b46c1 !important;
        }

        body[data-theme="dark"] .user-info {
            color: #c084fc !important;
        }

        body[data-theme="dark"] .view-all-link {
            background: #1a1625 !important;
            color: #c084fc !important;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1><?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?></h1>
            </div>
            <div class="nav-buttons">
                <button type="button" onclick="toggleTheme()" class="theme-toggle" title="<?php echo __('theme.switch'); ?>">
                    <span id="theme-icon">🌙</span>
                </button>
                <a href="?lang=<?php echo opposite_lang(); ?>" class="lang-toggle" title="Switch Language">
                    <?php echo strtoupper(opposite_lang()); ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <span class="user-info"><?php echo htmlspecialchars($user_name); ?></span>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="admin/index.php" class="btn btn-primary">Админ</a>
                    <?php endif; ?>
                    <a href="cart.php" class="btn btn-cart">
                        Количка
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="btn btn-primary">Вход</a>
                    <a href="cart.php" class="btn btn-cart">
                        Количка
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="hero">
        <h2>Добре дошли в <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>!</h2>
        <p>Открийте нашата уникална колекция от ръчно изработени продукти</p>
    </div>

    <?php if (!empty($activeCategories)): ?>
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Категории</h2>
            <div class="categories-grid">
                <?php foreach ($activeCategories as $category): 
                    // Get the latest product from this category (from all products, not just the 6 shown)
                    $categoryProducts = array_filter($products, function($product) use ($category) {
                        return ($product['category'] ?? '') === $category['slug'] && 
                               ($product['status'] ?? 'published') === 'published';
                    });
                    
                    // Sort by created date (newest first)
                    usort($categoryProducts, function($a, $b) {
                        $dateA = $a['created'] ?? '2000-01-01';
                        $dateB = $b['created'] ?? '2000-01-01';
                        return strtotime($dateB) - strtotime($dateA);
                    });
                    
                    $sampleProduct = !empty($categoryProducts) ? reset($categoryProducts) : null;
                ?>
                    <a href="/category/<?php echo urlencode($category['slug']); ?>" class="category-card">
                        <?php if ($sampleProduct && !empty($sampleProduct['image'])): ?>
                            <div class="category-preview-image" style="background-image: url('<?php echo htmlspecialchars($sampleProduct['image']); ?>');"></div>
                        <?php else: ?>
                            <div class="category-preview-image" style="background: linear-gradient(135deg, rgba(107, 70, 193, 0.2) 0%, rgba(124, 58, 237, 0.2) 100%);"></div>
                        <?php endif; ?>
                        <div class="category-info">
                            <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                            <?php if (!empty($category['description'])): ?>
                                <div class="category-desc"><?php echo htmlspecialchars(mb_substr($category['description'], 0, 50)) . (mb_strlen($category['description']) > 50 ? '...' : ''); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="products-section">
        <div class="container">
            <h2 class="section-title">Нови Продукти</h2>
            
            <?php if (empty($published_products)): ?>
                <div class="empty-state">
                    <h3>Все още няма продукти</h3>
                    <p>Скоро ще добавим красиви изделия!</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($published_products as $product): 
                        $stock = $product['stock'] ?? 0;
                        $price_eur = $product['price'] ?? 0;
                    ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div style="font-size: 80px; color: #d1d5db;"></div>
                                <?php endif; ?>
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-badge"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <?php if (!empty($product['category'])): ?>
                                    <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                <?php endif; ?>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description">
                                    <?php 
                                    $desc = $product['description'] ?? 'Красив продукт';
                                    echo htmlspecialchars(mb_substr($desc, 0, 120));
                                    if (mb_strlen($desc) > 120) echo '...';
                                    ?>
                                </p>
                                
                                <div class="product-footer">
                                    <div>
                                        <div class="product-price">
                                            <?php echo number_format($price_eur, 2); ?> <span class="currency">€</span>
                                        </div>
                                        <?php if ($stock > 0): ?>
                                            <div class="stock-indicator <?php echo $stock <= 5 ? 'low' : ''; ?>">
                                                ✓ <?php echo $stock <= 5 ? "Само $stock бр." : "В наличност"; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="stock-indicator out">✗ Изчерпан</div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <button type="submit" class="btn-buy" <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                            🛒 Купи
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_option('site_title', 'OffMeta')); ?>. Всички права запазени.</p>
    </footer>
    
    <script src="assets/js/theme.js"></script>
    <script>
        // Initialize theme from localStorage or system preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.body.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.setAttribute('data-theme', 'dark');
            updateThemeIcon('dark');
        }
        
        function toggleTheme() {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }
        
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.innerHTML = theme === 'dark' ? '☀️' : '🌙';
            }
        }
    </script>
</body>
</html>

