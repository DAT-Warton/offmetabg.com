<?php
/**
 * Dashboard Statistics Caching System
 * Caches expensive dashboard queries to improve performance
 */

class DashboardCache {
    private const CACHE_DIR = __DIR__ . '/../cache/dashboard/';
    private const CACHE_TTL = 300; // 5 minutes
    
    public function __construct() {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }
    
    /**
     * Get cached data or execute callback and cache result
     */
    public function remember($key, $callback, $ttl = null) {
        $ttl = $ttl ?? self::CACHE_TTL;
        $cache_file = self::CACHE_DIR . md5($key) . '.json';
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            if ($cache_data && (time() - $cache_data['timestamp']) < $ttl) {
                return $cache_data['data'];
            }
        }
        
        // Execute callback and cache result
        $data = $callback();
        $cache_content = json_encode([
            'timestamp' => time(),
            'data' => $data
        ]);
        file_put_contents($cache_file, $cache_content);
        
        return $data;
    }
    
    /**
     * Clear all dashboard cache
     */
    public function clear() {
        $files = glob(self::CACHE_DIR . '*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Clear specific cache key
     */
    public function forget($key) {
        $cache_file = self::CACHE_DIR . md5($key) . '.json';
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    }
}

/**
 * Optimized dashboard statistics using COUNT queries
 */
function get_dashboard_stats_optimized($date_range = null) {
    $cache = new DashboardCache();
    $cache_key = 'dashboard_stats_' . ($date_range['start'] ?? 'all') . '_' . ($date_range['end'] ?? 'all');
    
    return $cache->remember($cache_key, function() use ($date_range) {
        $stats = [];
        
        try {
            // Count orders efficiently
            $orders_query = db_table('orders');
            if ($date_range) {
                $orders_query = $orders_query->where('created', '>=', $date_range['start'])
                                             ->where('created', '<=', $date_range['end']);
            }
            $stats['orders_count'] = count($orders_query->all());
            
            // Calculate revenue from filtered orders
            $orders = $orders_query->all();
            $stats['revenue'] = array_sum(array_column($orders, 'total'));
            $stats['avg_order_value'] = $stats['orders_count'] > 0 ? 
                ($stats['revenue'] / $stats['orders_count']) : 0;
            
            // Count customers
            $customers_query = db_table('customers');
            if ($date_range) {
                $customers_query = $customers_query->where('created', '>=', $date_range['start'])
                                                   ->where('created', '<=', $date_range['end']);
            }
            $stats['customers_count'] = count($customers_query->all());
            
            // Count products (typically doesn't need date filtering)
            $stats['products_count'] = count(db_table('products')->all());
            
            // Count inquiries
            $inquiries_query = db_table('inquiries');
            if ($date_range) {
                $inquiries_query = $inquiries_query->where('created', '>=', $date_range['start'])
                                                   ->where('created', '<=', $date_range['end']);
            }
            $stats['inquiries_count'] = count($inquiries_query->all());
            
            // Order status breakdown
            $stats['order_status'] = [
                'pending' => 0,
                'confirmed' => 0,
                'processing' => 0,
                'shipped' => 0,
                'delivered' => 0,
                'cancelled' => 0
            ];
            
            foreach ($orders as $order) {
                $status = $order['status'] ?? 'pending';
                if (isset($stats['order_status'][$status])) {
                    $stats['order_status'][$status]++;
                }
            }
            
        } catch (Exception $e) {
            error_log("Dashboard stats error: ". $e->getMessage());
            $stats = [
                'orders_count' => 0,
                'revenue' => 0,
                'customers_count' => 0,
                'products_count' => 0,
                'inquiries_count' => 0,
                'avg_order_value' => 0,
                'order_status' => []
            ];
        }
        
        return $stats;
    }, 300); // Cache for 5 minutes
}

/**
 * Get Cloudflare analytics with caching (15 minutes TTL)
 */
function get_cloudflare_analytics_cached() {
    $cache = new DashboardCache();
    
    return $cache->remember('cloudflare_analytics', function() {
        require_once __DIR__ . '/cloudflare-analytics.php';
        $cloudflare = new CloudflareAnalytics();
        
        return [
            'traffic' => $cloudflare->getTrafficStats(),
            'security' => $cloudflare->getSecurityStats(),
            'performance' => $cloudflare->getPerformanceStats(),
            'geo' => $cloudflare->getGeographicData(10),
            'status_codes' => $cloudflare->getStatusCodeStats()
        ];
    }, 900); // Cache for 15 minutes
}
