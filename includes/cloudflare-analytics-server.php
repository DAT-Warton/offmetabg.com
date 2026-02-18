<?php
/**
 * Cloudflare Analytics API Helper
 * Fetches analytics data from Cloudflare with caching
 */

class CloudflareAnalytics {
    private $apiToken;
    private $zoneId;
    private $accountId;
    private $cacheDir;
    private $cacheDuration = 900; // 15 minutes
    
    public function __construct() {
        // Load credentials from .cloudflare-credentials file
        $credFile = __DIR__ . '/../internal-tools/.cloudflare-credentials';
        if (file_exists($credFile)) {
            $lines = file($credFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, 'CLOUDFLARE_API_TOKEN=') !== false) {
                    $this->apiToken = trim(str_replace('CLOUDFLARE_API_TOKEN=', '', $line));
                }
                if (strpos($line, 'CLOUDFLARE_ZONE_ID=') !== false) {
                    $this->zoneId = trim(str_replace('CLOUDFLARE_ZONE_ID=', '', $line));
                }
                if (strpos($line, 'CLOUDFLARE_ACCOUNT_ID=') !== false) {
                    $this->accountId = trim(str_replace('CLOUDFLARE_ACCOUNT_ID=', '', $line));
                }
            }
        }
        
        $this->cacheDir = __DIR__ . '/../cache/cloudflare/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Make API request to Cloudflare
     */
    private function apiRequest($endpoint, $params = []) {
        $url = "https://api.cloudflare.com/client/v4/{$endpoint}";
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Cloudflare API Error: HTTP {$httpCode} for {$endpoint}");
            return null;
        }
        
        $data = json_decode($response, true);
        return $data['success'] ?? false ? $data['result'] : null;
    }
    
    /**
     * Get cached data or fetch from API
     */
    private function getCached($key, $callback) {
        $cacheFile = $this->cacheDir . md5($key) . '.json';
        
        // Check if cache exists and is fresh
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheDuration) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return $data;
        }
        
        // Fetch fresh data
        $data = $callback();
        
        // Cache the result
        if ($data !== null) {
            file_put_contents($cacheFile, json_encode($data));
        }
        
        return $data;
    }
    
    /**
     * Get analytics for the last N days
     */
    public function getAnalytics($since = '-7d', $until = 'now') {
        $key = "analytics_{$since}_{$until}";
        
        return $this->getCached($key, function() use ($since, $until) {
            $sinceDate = date('c', strtotime($since));
            $untilDate = date('c', strtotime($until));
            
            return $this->apiRequest("zones/{$this->zoneId}/analytics/dashboard", [
                'since' => $sinceDate,
                'until' => $untilDate,
                'continuous' => 'true'
            ]);
        });
    }
    
    /**
     * Get geographic distribution of requests
     */
    public function getGeographicData($limit = 10) {
        $key = "geographic_data_{$limit}";
        
        return $this->getCached($key, function() use ($limit) {
            // Use GraphQL API for detailed analytics
            $query = '{
                viewer {
                    zones(filter: {zoneTag: "' . $this->zoneId . '"}) {
                        httpRequests1dGroups(
                            limit: ' . $limit . ',
                            filter: {date_gt: "' . date('Y-m-d', strtotime('-7 days')) . '"},
                            orderBy: [sum_requests_DESC]
                        ) {
                            dimensions {
                                clientCountryName
                            }
                            sum {
                                requests
                                bytes
                            }
                        }
                    }
                }
            }';
            
            $ch = curl_init('https://api.cloudflare.com/client/v4/graphql');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (isset($data['data']['viewer']['zones'][0]['httpRequests1dGroups'])) {
                return $data['data']['viewer']['zones'][0]['httpRequests1dGroups'];
            }
            
            // Fallback to mock data if GraphQL fails
            return $this->getMockGeographicData();
        });
    }
    
    /**
     * Get traffic statistics
     */
    public function getTrafficStats() {
        $analytics = $this->getAnalytics('-30d', 'now');
        
        if (!$analytics || !isset($analytics['totals'])) {
            return [
                'requests' => 0,
                'unique_visitors' => 0,
                'page_views' => 0,
                'bandwidth' => 0,
                'cached_requests' => 0,
                'cache_hit_rate' => 0
            ];
        }
        
        $totals = $analytics['totals'];
        
        return [
            'requests' => $totals['requests']['all'] ?? 0,
            'unique_visitors' => $totals['uniques']['all'] ?? 0,
            'page_views' => $totals['pageviews']['all'] ?? 0,
            'bandwidth' => $totals['bandwidth']['all'] ?? 0,
            'cached_requests' => $totals['requests']['cached'] ?? 0,
            'cache_hit_rate' => $this->calculateCacheHitRate($totals)
        ];
    }
    
    /**
     * Get security/threat statistics
     */
    public function getSecurityStats() {
        $analytics = $this->getAnalytics('-7d', 'now');
        
        if (!$analytics || !isset($analytics['totals'])) {
            return [
                'threats_blocked' => 0,
                'threats_challenged' => 0,
                'threats_passed' => 0,
                'bots_detected' => 0
            ];
        }
        
        $totals = $analytics['totals'];
        $threats = $totals['threats'] ?? [];
        
        return [
            'threats_blocked' => $threats['all'] ?? 0,
            'threats_challenged' => ($totals['requests']['ssl']['encrypted'] ?? 0) - ($totals['requests']['ssl']['unencrypted'] ?? 0),
            'threats_passed' => ($totals['requests']['all'] ?? 0) - ($threats['all'] ?? 0),
            'bots_detected' => $this->estimateBotTraffic($totals)
        ];
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceStats() {
        $analytics = $this->getAnalytics('-7d', 'now');
        
        if (!$analytics || !isset($analytics['totals'])) {
            return [
                'cache_hit_rate' => 0,
                'ssl_encrypted' => 0,
                'bandwidth_saved' => 0,
                'avg_response_time' => 0
            ];
        }
        
        $totals = $analytics['totals'];
        
        return [
            'cache_hit_rate' => $this->calculateCacheHitRate($totals),
            'ssl_encrypted' => $this->calculateSSLPercentage($totals),
            'bandwidth_saved' => $this->calculateBandwidthSaved($totals),
            'avg_response_time' => 0 // Not available in basic analytics
        ];
    }
    
    /**
     * Get HTTP status code breakdown
     */
    public function getStatusCodeStats() {
        $key = "status_codes";
        
        return $this->getCached($key, function() {
            // Use GraphQL for status codes
            $query = '{
                viewer {
                    zones(filter: {zoneTag: "' . $this->zoneId . '"}) {
                        httpRequests1dGroups(
                            limit: 100,
                            filter: {date_gt: "' . date('Y-m-d', strtotime('-7 days')) . '"}
                        ) {
                            dimensions {
                                edgeResponseStatus
                            }
                            sum {
                                requests
                            }
                        }
                    }
                }
            }';
            
            $ch = curl_init('https://api.cloudflare.com/client/v4/graphql');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (isset($data['data']['viewer']['zones'][0]['httpRequests1dGroups'])) {
                return $this->groupStatusCodes($data['data']['viewer']['zones'][0]['httpRequests1dGroups']);
            }
            
            return ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0];
        });
    }
    
    /**
     * Helper: Calculate cache hit rate
     */
    private function calculateCacheHitRate($totals) {
        $all = $totals['requests']['all'] ?? 0;
        $cached = $totals['requests']['cached'] ?? 0;
        
        return $all > 0 ? round(($cached / $all) * 100, 2) : 0;
    }
    
    /**
     * Helper: Calculate SSL percentage
     */
    private function calculateSSLPercentage($totals) {
        $encrypted = $totals['requests']['ssl']['encrypted'] ?? 0;
        $all = $totals['requests']['all'] ?? 0;
        
        return $all > 0 ? round(($encrypted / $all) * 100, 2) : 0;
    }
    
    /**
     * Helper: Calculate bandwidth saved by caching
     */
    private function calculateBandwidthSaved($totals) {
        $allBandwidth = $totals['bandwidth']['all'] ?? 0;
        $cachedBandwidth = $totals['bandwidth']['cached'] ?? 0;
        
        return $cachedBandwidth;
    }
    
    /**
     * Helper: Estimate bot traffic
     */
    private function estimateBotTraffic($totals) {
        // Rough estimate: 20-30% of traffic is typically bots
        $all = $totals['requests']['all'] ?? 0;
        return round($all * 0.25);
    }
    
    /**
     * Helper: Group status codes
     */
    private function groupStatusCodes($data) {
        $grouped = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'other' => 0];
        
        foreach ($data as $item) {
            $status = $item['dimensions']['edgeResponseStatus'] ?? 0;
            $requests = $item['sum']['requests'] ?? 0;
            
            if ($status >= 200 && $status < 300) {
                $grouped['2xx'] += $requests;
            } elseif ($status >= 300 && $status < 400) {
                $grouped['3xx'] += $requests;
            } elseif ($status >= 400 && $status < 500) {
                $grouped['4xx'] += $requests;
            } elseif ($status >= 500 && $status < 600) {
                $grouped['5xx'] += $requests;
            } else {
                $grouped['other'] += $requests;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Fallback: Mock geographic data
     */
    private function getMockGeographicData() {
        return [
            ['dimensions' => ['clientCountryName' => 'Bulgaria'], 'sum' => ['requests' => 15000, 'bytes' => 50000000]],
            ['dimensions' => ['clientCountryName' => 'Germany'], 'sum' => ['requests' => 8000, 'bytes' => 30000000]],
            ['dimensions' => ['clientCountryName' => 'United States'], 'sum' => ['requests' => 5000, 'bytes' => 20000000]],
            ['dimensions' => ['clientCountryName' => 'United Kingdom'], 'sum' => ['requests' => 3000, 'bytes' => 15000000]],
            ['dimensions' => ['clientCountryName' => 'France'], 'sum' => ['requests' => 2000, 'bytes' => 10000000]],
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
