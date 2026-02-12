<?php
/**
 * Bulgarian Courier API Integration
 * Econt Express and Speedy Courier Services
 */

class CourierAPI {
    private $econt_username = '';
    private $econt_password = '';
    private $speedy_username = '';
    private $speedy_password = '';
    
    public function __construct() {
        // Load API credentials from config if available
        $config = file_exists(__DIR__ . '/../config/courier-config.php') 
            ? include(__DIR__ . '/../config/courier-config.php')
            : [];
        
        $this->econt_username = $config['econt_username'] ?? '';
        $this->econt_password = $config['econt_password'] ?? '';
        $this->speedy_username = $config['speedy_username'] ?? '';
        $this->speedy_password = $config['speedy_password'] ?? '';
    }
    
    /**
     * Create Econt Shipment
     * @param array $order_data Order details
     * @return array Shipment tracking number and label URL
     */
    public function createEcontShipment($order_data) {
        // Econt Express API Documentation: https://www.econt.com/services/web-services
        // This is a placeholder - implement actual API calls when credentials are ready
        
        $shipment_data = [
            'sender' => [
                'name' => 'OffMeta Store',
                'phone' => '+359 888 000 000',
                'city' => 'Sofia',
                'address' => 'Your business address'
            ],
            'receiver' => [
                'name' => $order_data['shipping']['receiver_name'],
                'phone' => $order_data['shipping']['receiver_phone'],
                'city' => $order_data['shipping']['city'],
                'address' => $order_data['shipping']['address'],
                'postal_code' => $order_data['shipping']['postal_code'] ?? ''
            ],
            'shipment' => [
                'weight' => $this->calculateWeight($order_data['items']),
                'declared_value' => $order_data['total'],
                'cod_amount' => $order_data['payment']['cod'] ? $order_data['total'] : 0,
                'delivery_instructions' => $order_data['shipping']['delivery_notes'] ?? ''
            ]
        ];
        
        // TODO: Implement actual Econt API call
        // $response = $this->callEcontAPI('/shipments/create', $shipment_data);
        
        // Placeholder response
        return [
            'success' => true,
            'tracking_number' => 'ECONT-' . strtoupper(uniqid()),
            'label_url' => '',
            'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
            'message' => 'Shipment created successfully (placeholder)'
        ];
    }
    
    /**
     * Create Speedy Shipment
     * @param array $order_data Order details
     * @return array Shipment tracking number and label URL
     */
    public function createSpeedyShipment($order_data) {
        // Speedy API Documentation: https://www.speedy.bg/bg/services-integrations
        // This is a placeholder - implement actual API calls when credentials are ready
        
        $shipment_data = [
            'sender' => [
                'name' => 'OffMeta Store',
                'phone' => '+359 888 000 000',
                'city' => 'Sofia',
                'address' => 'Your business address'
            ],
            'receiver' => [
                'name' => $order_data['shipping']['receiver_name'],
                'phone' => $order_data['shipping']['receiver_phone'],
                'city' => $order_data['shipping']['city'],
                'address' => $order_data['shipping']['address'],
                'postal_code' => $order_data['shipping']['postal_code'] ?? ''
            ],
            'shipment' => [
                'weight' => $this->calculateWeight($order_data['items']),
                'declared_value' => $order_data['total'],
                'cod_amount' => $order_data['payment']['cod'] ? $order_data['total'] : 0,
                'delivery_instructions' => $order_data['shipping']['delivery_notes'] ?? ''
            ]
        ];
        
        // TODO: Implement actual Speedy API call
        // $response = $this->callSpeedyAPI('/shipments/create', $shipment_data);
        
        // Placeholder response
        return [
            'success' => true,
            'tracking_number' => 'SPD-' . strtoupper(uniqid()),
            'label_url' => '',
            'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
            'message' => 'Shipment created successfully (placeholder)'
        ];
    }
    
    /**
     * Track Econt Shipment
     * @param string $tracking_number Tracking number
     * @return array Tracking status and history
     */
    public function trackEcontShipment($tracking_number) {
        // TODO: Implement actual Econt tracking API call
        
        return [
            'success' => true,
            'status' => 'in_transit',
            'status_text' => 'In Transit',
            'current_location' => 'Sofia sorting center',
            'estimated_delivery' => date('Y-m-d', strtotime('+1 day')),
            'history' => [
                ['date' => date('Y-m-d H:i:s'), 'status' => 'Picked up', 'location' => 'Sofia'],
                ['date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'status' => 'Label created', 'location' => 'Sofia']
            ]
        ];
    }
    
    /**
     * Track Speedy Shipment
     * @param string $tracking_number Tracking number
     * @return array Tracking status and history
     */
    public function trackSpeedyShipment($tracking_number) {
        // TODO: Implement actual Speedy tracking API call
        
        return [
            'success' => true,
            'status' => 'in_transit',
            'status_text' => 'In Transit',
            'current_location' => 'Sofia sorting center',
            'estimated_delivery' => date('Y-m-d', strtotime('+1 day')),
            'history' => [
                ['date' => date('Y-m-d H:i:s'), 'status' => 'Picked up', 'location' => 'Sofia'],
                ['date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'status' => 'Label created', 'location' => 'Sofia']
            ]
        ];
    }
    
    /**
     * Calculate total weight from order items
     * @param array $items Order items
     * @return float Weight in kg
     */
    private function calculateWeight($items) {
        $total_weight = 0;
        foreach ($items as $item) {
            // Assume default weight of 0.5kg per item if not specified
            $weight = $item['weight'] ?? 0.5;
            $quantity = $item['quantity'] ?? 1;
            $total_weight += $weight * $quantity;
        }
        return max($total_weight, 0.1); // Minimum 0.1kg
    }
    
    /**
     * Get shipping cost estimate for Econt
     * @param array $order_data Order details
     * @return array Cost breakdown
     */
    public function getEcontShippingCost($order_data) {
        $weight = $this->calculateWeight($order_data['items']);
        $cod_fee = $order_data['payment']['cod'] ? ($order_data['total'] * 0.01) : 0; // 1% COD fee
        $base_cost = 5.50; // Base shipping cost in BGN
        $weight_cost = $weight * 0.50; // 0.50 BGN per kg
        
        return [
            'success' => true,
            'base_cost' => $base_cost,
            'weight_cost' => $weight_cost,
            'cod_fee' => $cod_fee,
            'total' => $base_cost + $weight_cost + $cod_fee,
            'currency' => 'BGN',
            'estimated_days' => '1-2'
        ];
    }
    
    /**
     * Get shipping cost estimate for Speedy
     * @param array $order_data Order details
     * @return array Cost breakdown
     */
    public function getSpeedyShippingCost($order_data) {
        $weight = $this->calculateWeight($order_data['items']);
        $cod_fee = $order_data['payment']['cod'] ? ($order_data['total'] * 0.01) : 0; // 1% COD fee
        $base_cost = 6.00; // Base shipping cost in BGN
        $weight_cost = $weight * 0.45; // 0.45 BGN per kg
        
        return [
            'success' => true,
            'base_cost' => $base_cost,
            'weight_cost' => $weight_cost,
            'cod_fee' => $cod_fee,
            'total' => $base_cost + $weight_cost + $cod_fee,
            'currency' => 'BGN',
            'estimated_days' => '1-2'
        ];
    }
    
    /**
     * Call Econt API (placeholder)
     */
    private function callEcontAPI($endpoint, $data) {
        // TODO: Implement actual API call with authentication
        // $api_url = 'https://ee.econt.com/services/' . $endpoint;
        // Use SOAP or REST API as per Econt documentation
        return null;
    }
    
    /**
     * Call Speedy API (placeholder)
     */
    private function callSpeedyAPI($endpoint, $data) {
        // TODO: Implement actual API call with authentication
        // $api_url = 'https://api.speedy.bg/v1/' . $endpoint;
        // Use REST API as per Speedy documentation
        return null;
    }
}
