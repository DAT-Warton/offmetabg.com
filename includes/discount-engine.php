<?php
/**
 * Professional Discount Engine
 * Advanced discount calculation with stacking, priority, and complex rules
 */

defined('CMS_ROOT') or die('Direct access not permitted');

class DiscountEngine {
    private $cart_items = [];
    private $subtotal = 0;
    private $customer = null;
    private $applied_discounts = [];
    
    public function __construct($cart_items, $customer = null) {
        $this->cart_items = $cart_items;
        $this->customer = $customer;
        $this->calculateSubtotal();
    }
    
    private function calculateSubtotal() {
        $this->subtotal = 0;
        foreach ($this->cart_items as $item) {
            $this->subtotal += ((float)$item['price']) * (int)$item['quantity'];
        }
    }
    
    /**
     * Apply all eligible discounts
     * @param string|null $discount_code Manual discount code entered by user
     * @return array ['total_discount' => float, 'applied_discounts' => array, 'final_total' => float]
     */
    public function applyDiscounts($discount_code = null) {
        $all_discounts = get_discounts_data();
        $eligible_discounts = [];
        
        // Check manual discount code
        if ($discount_code) {
            foreach ($all_discounts as $discount) {
                if (strtoupper($discount['code']) === strtoupper($discount_code)) {
                    if ($this->isDiscountEligible($discount)) {
                        $eligible_discounts[] = $discount;
                    }
                    break; // Only one manual code
                }
            }
        }
        
        // Check auto-apply discounts
        foreach ($all_discounts as $discount) {
            if ($discount['auto_apply'] ?? false) {
                if ($this->isDiscountEligible($discount)) {
                    $eligible_discounts[] = $discount;
                }
            }
        }
        
        // Sort by priority (highest first)
        usort($eligible_discounts, function($a, $b) {
            $priorityA = $a['priority'] ?? 0;
            $priorityB = $b['priority'] ?? 0;
            return $priorityB - $priorityA;
        });
        
        // Apply discounts based on combinability
        $total_discount = 0;
        $applied = [];
        
        foreach ($eligible_discounts as $discount) {
            $can_apply = empty($applied) || ($discount['combinable'] ?? false);
            
            // Check if any previous non-combinable discount was applied
            if (!empty($applied)) {
                foreach ($applied as $prev_discount) {
                    if (!($prev_discount['combinable'] ?? false)) {
                        $can_apply = false;
                        break;
                    }
                }
            }
            
            if ($can_apply) {
                $discount_amount = $this->calculateDiscountAmount($discount, $this->subtotal - $total_discount);
                if ($discount_amount > 0) {
                    $total_discount += $discount_amount;
                    $discount['calculated_amount'] = $discount_amount;
                    $applied[] = $discount;
                    
                    // Update usage count
                    $this->incrementDiscountUsage($discount['id']);
                }
            }
        }
        
        $this->applied_discounts = $applied;
        
        return [
            'total_discount' => $total_discount,
            'applied_discounts' => $applied,
            'final_total' => max(0, $this->subtotal - $total_discount),
            'subtotal' => $this->subtotal
        ];
    }
    
    /**
     * Check if discount is eligible
     */
    private function isDiscountEligible($discount) {
        // Check if active
        if (!($discount['active'] ?? false)) {
            return false;
        }
        
        // Check dates
        $now = time();
        if (!empty($discount['start_date']) && strtotime($discount['start_date']) > $now) {
            return false;
        }
        if (!empty($discount['end_date']) && strtotime($discount['end_date']) < $now) {
            return false;
        }
        
        // Check usage limits
        $max_uses = $discount['max_uses'] ?? 0;
        $used_count = $discount['used_count'] ?? 0;
        if ($max_uses > 0 && $used_count >= $max_uses) {
            return false;
        }
        
        // Check minimum purchase
        $min_purchase = $discount['min_purchase'] ?? 0;
        if ($min_purchase > 0 && $this->subtotal < $min_purchase) {
            return false;
        }
        
        // Check maximum purchase
        $max_purchase = $discount['max_purchase'] ?? 0;
        if ($max_purchase > 0 && $this->subtotal > $max_purchase) {
            return false;
        }
        
        // Check minimum items
        $min_items = $discount['min_items'] ?? 0;
        if ($min_items > 0) {
            $total_qty = 0;
            foreach ($this->cart_items as $item) {
                $total_qty += (int)$item['quantity'];
            }
            if ($total_qty < $min_items) {
                return false;
            }
        }
        
        // Check customer eligibility
        $eligibility = $discount['customer_eligibility'] ?? 'all';
        if ($eligibility !== 'all') {
            if (!$this->checkCustomerEligibility($eligibility)) {
                return false;
            }
        }
        
        // Check first purchase only
        if ($discount['first_purchase_only'] ?? false) {
            if (!$this->isFirstPurchase()) {
                return false;
            }
        }
        
        // Check product/category restrictions
        $applies_to = $discount['applies_to'] ?? 'all';
        if ($applies_to !== 'all') {
            if (!$this->checkProductEligibility($discount)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate discount amount
     */
    private function calculateDiscountAmount($discount, $base_amount) {
        $type = $discount['type'] ?? 'percentage';
        $value = (float)($discount['value'] ?? 0);
        $max_discount = $discount['max_discount'] ?? 0;
        
        $amount = 0;
        
        switch ($type) {
            case 'percentage':
                $amount = $base_amount * ($value / 100);
                break;
                
            case 'fixed':
                $amount = $value;
                break;
                
            case 'free_shipping':
                // Free shipping discount removes shipping cost
                $shipping_cost = $_SESSION['shipping_cost'] ?? 0;
                $amount = (float)$shipping_cost;
                break;
                
            case 'buy_x_get_y':
                $amount = $this->calculateBuyXGetY($discount);
                break;
                
            default:
                $amount = 0;
        }
        
        // Apply max discount limit
        if ($max_discount > 0 && $amount > $max_discount) {
            $amount = $max_discount;
        }
        
        // Don't exceed base amount
        if ($amount > $base_amount) {
            $amount = $base_amount;
        }
        
        return $amount;
    }
    
    /**
     * Calculate Buy X Get Y discount
     */
    private function calculateBuyXGetY($discount) {
        $buy_qty = $discount['buy_quantity'] ?? 2;
        $get_qty = $discount['get_quantity'] ?? 1;
        
        // Find cheapest products that match
        $eligible_items = $this->getEligibleItems($discount);
        if (empty($eligible_items)) {
            return 0;
        }
        
        // Sort by price ascending
        usort($eligible_items, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        $total_items = 0;
        foreach ($eligible_items as $item) {
            $total_items += $item['quantity'];
        }
        
        // Calculate how many free items
        $sets = floor($total_items / ($buy_qty + $get_qty));
        if ($sets == 0) {
            // Not enough items for a full set
            return 0;
        }
        
        // Free items are the cheapest ones
        $free_items_count = $sets * $get_qty;
        $discount_amount = 0;
        
        foreach ($eligible_items as $item) {
            if ($free_items_count <= 0) {
                break;
            }
            
            $free_from_this = min($free_items_count, $item['quantity']);
            $discount_amount += $free_from_this * $item['price'];
            $free_items_count -= $free_from_this;
        }
        
        return $discount_amount;
    }
    
    /**
     * Get items eligible for discount based on product/category rules
     */
    private function getEligibleItems($discount) {
        $applies_to = $discount['applies_to'] ?? 'all';
        
        if ($applies_to === 'all') {
            return $this->cart_items;
        }
        
        $eligible = [];
        
        if ($applies_to === 'products' || $applies_to === 'except_products') {
            $product_ids = $discount['product_ids'] ?? [];
            foreach ($this->cart_items as $item) {
                $is_in_list = in_array($item['id'], $product_ids);
                if (($applies_to === 'products' && $is_in_list) ||
                    ($applies_to === 'except_products' && !$is_in_list)) {
                    $eligible[] = $item;
                }
            }
        } elseif ($applies_to === 'categories') {
            $category_ids = $discount['category_ids'] ?? [];
            foreach ($this->cart_items as $item) {
                $product = $this->getProductDetails($item['id']);
                if ($product && in_array($product['category'] ?? '', $category_ids)) {
                    $eligible[] = $item;
                }
            }
        }
        
        return $eligible;
    }
    
    /**
     * Check product eligibility
     */
    private function checkProductEligibility($discount) {
        $applies_to = $discount['applies_to'] ?? 'all';
        
        if ($applies_to === 'all') {
            return true;
        }
        
        $eligible_items = $this->getEligibleItems($discount);
        
        // At least one cart item must be eligible
        return !empty($eligible_items);
    }
    
    /**
     * Check customer eligibility
     */
    private function checkCustomerEligibility($eligibility) {
        if (!$this->customer) {
            return $eligibility === 'new' || $eligibility === 'guests';
        }
        
        switch ($eligibility) {
            case 'new':
                return $this->isFirstPurchase();
                
            case 'returning':
                return !$this->isFirstPurchase();
                
            case 'registered':
                return $this->customer !== null;
                
            case 'guests':
                return $this->customer === null;
                
            case 'vip':
                // Check if customer has VIP status
                if (!$this->customer) {
                    return false;
                }
                $customer_tier = $this->customer['customer_tier'] ?? $this->customer['tier'] ?? 'regular';
                return strtolower($customer_tier) === 'vip';
                
            default:
                return true;
        }
    }
    
    /**
     * Check if this is customer's first purchase
     */
    private function isFirstPurchase() {
        if (!$this->customer) {
            return true; // Guest customer
        }
        
        $orders = db_table('orders')->all();
        foreach ($orders as $order) {
            $customer_id = $order['customer_id'] ?? null;
            if ($customer_id === $this->customer['id']) {
                return false; // Has previous orders
            }
        }
        
        return true; // No previous orders
    }
    
    /**
     * Get product details
     */
    private function getProductDetails($product_id) {
        $products = get_products_data();
        return $products[$product_id] ?? null;
    }
    
    /**
     * Increment discount usage count
     */
    private function incrementDiscountUsage($discount_id) {
        update_discount_usage($discount_id);
    }
    
    /**
     * Get applied discounts summary
     */
    public function getAppliedDiscounts() {
        return $this->applied_discounts;
    }
    
    /**
     * Get discount summary for display
     */
    public function getDiscountSummary() {
        $summary = [];
        foreach ($this->applied_discounts as $discount) {
            $summary[] = [
                'code' => $discount['code'],
                'name' => $discount['name'] ?? $discount['code'],
                'amount' => $discount['calculated_amount'] ?? 0,
                'type' => $discount['type']
            ];
        }
        return $summary;
    }
}

/**
 * Helper function to apply discounts in cart
 * @param array $cart_items Cart items
 * @param string|null $discount_code Manual discount code
 * @param array|null $customer Customer data
 * @return array Discount result
 */
function calculate_cart_discounts($cart_items, $discount_code = null, $customer = null) {
    $engine = new DiscountEngine($cart_items, $customer);
    return $engine->applyDiscounts($discount_code);
}

/**
 * Validate discount code
 * @param string $code Discount code
 * @param array $cart_items Cart items
 * @param array|null $customer Customer data
 * @return array ['valid' => bool, 'message' => string, 'discount' => array|null]
 */
function validate_discount_code($code, $cart_items, $customer = null) {
    $discounts = get_discounts_data();
    $discount = null;
    
    foreach ($discounts as $d) {
        if (strtoupper($d['code']) === strtoupper($code)) {
            $discount = $d;
            break;
        }
    }
    
    if (!$discount) {
        return [
            'valid' => false,
            'message' => 'Невалиден промо код',
            'discount' => null
        ];
    }
    
    $engine = new DiscountEngine($cart_items, $customer);
    
    // Use reflection to call private method (for validation only)
    $reflection = new ReflectionClass($engine);
    $method = $reflection->getMethod('isDiscountEligible');
    $method->setAccessible(true);
    
    $is_eligible = $method->invoke($engine, $discount);
    
    if (!$is_eligible) {
        return [
            'valid' => false,
            'message' => 'Този промо код не може да бъде приложен за текущата кошница',
            'discount' => null
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Промо кодът е валиден',
        'discount' => $discount
    ];
}
