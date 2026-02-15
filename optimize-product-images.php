<?php
/**
 * Download and Optimize External Product Images
 * 
 * This script downloads product images from external sources (zaeshkatadupka.eu)
 * and optimizes them locally to reduce page load times.
 * 
 * Run: php optimize-product-images.php
 */

require_once __DIR__ . '/includes/database.php';

// Configuration
$max_width = 800;  // Maximum image width
$max_height = 800; // Maximum image height
$quality = 85;     // JPEG quality (1-100)
$upload_dir = __DIR__ . '/uploads/products';

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

echo "=== Product Image Optimization Tool ===\n\n";

try {
    // Get database connection
    $pdo = get_db_connection();
    
    // Fetch all products with images
    $stmt = $pdo->query("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != ''");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($products) . " products with images\n\n";
    
    $optimized_count = 0;
    $skipped_count = 0;
    $error_count = 0;
    
    foreach ($products as $product) {
        $product_id = $product['id'];
        $product_name = $product['name'];
        $image_url = $product['image'];
        
        // Skip if already a local image
        if (strpos($image_url, 'offmetabg.com') !== false || strpos($image_url, '/uploads/') === 0) {
            echo "[$product_id] SKIP: Already local - $product_name\n";
            $skipped_count++;
            continue;
        }
        
        echo "[$product_id] Processing: $product_name\n";
        echo "  Original URL: $image_url\n";
        
        // Download the image
        $image_data = @file_get_contents($image_url);
        
        if ($image_data === false) {
            echo "  ERROR: Failed to download image\n\n";
            $error_count++;
            continue;
        }
        
        $original_size = strlen($image_data);
        echo "  Original size: " . number_format($original_size / 1024, 2) . " KB\n";
        
        // Create a temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($temp_file, $image_data);
        
        // Get image info
        $image_info = @getimagesize($temp_file);
        
        if ($image_info === false) {
            echo "  ERROR: Invalid image format\n\n";
            unlink($temp_file);
            $error_count++;
            continue;
        }
        
        list($width, $height, $type) = $image_info;
        echo "  Original dimensions: {$width}x{$height}\n";
        
        // Load the image based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($temp_file);
                $extension = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($temp_file);
                $extension = 'png';
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($temp_file);
                $extension = 'gif';
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($temp_file);
                $extension = 'webp';
                break;
            default:
                echo "  ERROR: Unsupported image type\n\n";
                unlink($temp_file);
                $error_count++;
                continue 2;
        }
        
        if ($source === false) {
            echo "  ERROR: Failed to create image resource\n\n";
            unlink($temp_file);
            $error_count++;
            continue;
        }
        
        // Calculate new dimensions
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        
        // Create new image
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
        }
        
        // Resize
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Generate filename
        $filename = 'product_' . $product_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . '/' . $filename;
        
        // Save optimized image
        $save_success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $save_success = imagejpeg($resized, $filepath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG compression level: 0 (no compression) to 9 (max compression)
                $png_quality = floor((100 - $quality) / 10);
                $save_success = imagepng($resized, $filepath, $png_quality);
                break;
            case IMAGETYPE_GIF:
                $save_success = imagegif($resized, $filepath);
                break;
            case IMAGETYPE_WEBP:
                $save_success = imagewebp($resized, $filepath, $quality);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($resized);
        unlink($temp_file);
        
        if (!$save_success) {
            echo "  ERROR: Failed to save optimized image\n\n";
            $error_count++;
            continue;
        }
        
        $new_size = filesize($filepath);
        $reduction = round((1 - ($new_size / $original_size)) * 100, 1);
        
        echo "  New dimensions: {$new_width}x{$new_height}\n";
        echo "  New size: " . number_format($new_size / 1024, 2) . " KB\n";
        echo "  Reduction: {$reduction}%\n";
        
        // Update database with new local path
        $new_url = '/uploads/products/' . $filename;
        $update_stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
        $update_stmt->execute([$new_url, $product_id]);
        
        echo "  ✓ Updated database: $new_url\n\n";
        $optimized_count++;
        
        // Small delay to avoid overwhelming servers
        usleep(500000); // 0.5 seconds
    }
    
    echo "\n=== Summary ===\n";
    echo "Total products: " . count($products) . "\n";
    echo "Optimized: $optimized_count\n";
    echo "Skipped (already local): $skipped_count\n";
    echo "Errors: $error_count\n";
    
    if ($optimized_count > 0) {
        echo "\n✓ Images optimized successfully!\n";
        echo "⚠ IMPORTANT: Test the website to ensure images display correctly.\n";
        echo "⚠ Keep backup of old image URLs in case you need to revert.\n";
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
