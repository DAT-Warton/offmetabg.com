<?php
/**
 * Image Optimization Functions
 * Automatically compresses images on upload to reduce bandwidth
 */

/**
 * Optimize image file to reduce size while maintaining quality
 * 
 * @param string $source_path Path to source image
 * @param string $output_path Path to save optimized image (optional, defaults to source)
 * @param int $quality JPEG quality (1-100), default 85
 * @param int $max_width Maximum width (optional, maintains aspect ratio)
 * @param int $max_height Maximum height (optional, maintains aspect ratio)
 * @return array ['success' => bool, 'original_size' => int, 'optimized_size' => int, 'savings' => string]
 */
function optimize_image($source_path, $output_path = null, $quality = 85, $max_width = null, $max_height = null) {
    // Check if GD extension is loaded
    if (!extension_loaded('gd')) {
        return ['success' => false, 'error' => 'GD extension not loaded'];
    }
    
    if (!file_exists($source_path)) {
        return ['success' => false, 'error' => 'File not found'];
    }
    
    $output_path = $output_path ?? $source_path;
    $original_size = filesize($source_path);
    
    // Get image info
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        return ['success' => false, 'error' => 'Invalid image'];
    }
    
    list($width, $height, $type) = $image_info;
    
    // Calculate new dimensions if max size specified
    if ($max_width || $max_height) {
        $ratio = $width / $height;
        
        if ($max_width && $width > $max_width) {
            $width = $max_width;
            $height = $width / $ratio;
        }
        
        if ($max_height && $height > $max_height) {
            $height = $max_height;
            $width = $height * $ratio;
        }
        
        $width = round($width);
        $height = round($height);
    }
    
    // Create image resource from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return ['success' => false, 'error' => 'Unsupported image type'];
    }
    
    if (!$source_image) {
        return ['success' => false, 'error' => 'Failed to create image resource'];
    }
    
    // Create new image if resizing
    if (($max_width || $max_height) && ($width != $image_info[0] || $height != $image_info[1])) {
        $output_image = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG and GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($output_image, false);
            imagesavealpha($output_image, true);
            $transparent = imagecolorallocatealpha($output_image, 255, 255, 255, 127);
            imagefilledrectangle($output_image, 0, 0, $width, $height, $transparent);
        }
        
        imagecopyresampled($output_image, $source_image, 0, 0, 0, 0, $width, $height, $image_info[0], $image_info[1]);
        imagedestroy($source_image);
    } else {
        $output_image = $source_image;
    }
    
    // Save optimized image
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($output_image, $output_path, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9, convert from JPEG quality scale
            $png_quality = round((100 - $quality) / 11.11);
            $success = imagepng($output_image, $output_path, $png_quality);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($output_image, $output_path);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($output_image, $output_path, $quality);
            break;
    }
    
    imagedestroy($output_image);
    
    if (!$success) {
        return ['success' => false, 'error' => 'Failed to save optimized image'];
    }
    
    $optimized_size = filesize($output_path);
    $savings_bytes = $original_size - $optimized_size;
    $savings_percent = $original_size > 0 ? round(($savings_bytes / $original_size) * 100, 1) : 0;
    
    return [
        'success' => true,
        'original_size' => $original_size,
        'optimized_size' => $optimized_size,
        'savings_bytes' => $savings_bytes,
        'savings_percent' => $savings_percent,
        'savings' => format_bytes($savings_bytes) . "($savings_percent%)"
    ];
}

/**
 * Format bytes to human readable string
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Optimize all images in a directory
 * 
 * @param string $directory Directory to scan
 * @param int $quality JPEG quality (1-100)
 * @param int $min_size Minimum file size to optimize (in bytes)
 * @return array Statistics about optimization
 */
function optimize_directory_images($directory, $quality = 85, $min_size = 500000) {
    $stats = [
        'total_files' => 0,
        'optimized_files' => 0,
        'skipped_files' => 0,
        'failed_files' => 0,
        'original_size' => 0,
        'optimized_size' => 0,
        'savings_bytes' => 0,
        'errors' => []
    ];
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = glob($directory . '/*');
    
    foreach ($files as $file) {
        if (!is_file($file)) continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) continue;
        
        $stats['total_files']++;
        $file_size = filesize($file);
        
        // Skip small files
        if ($file_size < $min_size) {
            $stats['skipped_files']++;
            continue;
        }
        
        $result = optimize_image($file, null, $quality);
        
        if ($result['success']) {
            $stats['optimized_files']++;
            $stats['original_size'] += $result['original_size'];
            $stats['optimized_size'] += $result['optimized_size'];
            $stats['savings_bytes'] += $result['savings_bytes'];
        } else {
            $stats['failed_files']++;
            $stats['errors'][] = basename($file) . ': ' . $result['error'];
        }
    }
    
    $stats['savings'] = format_bytes($stats['savings_bytes']);
    $stats['savings_percent'] = $stats['original_size'] > 0 ? 
        round(($stats['savings_bytes'] / $stats['original_size']) * 100, 1) : 0;
    
    return $stats;
}
