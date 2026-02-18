<?php
/**
 * Comprehensive Admin Dashboard with E-Commerce Statistics + Cloudflare Analytics
 * OPTIMIZED: Uses caching to prevent 9-second load times
 */

// Get cached Cloudflare Analytics (15-minute cache)
$cf_data = get_cloudflare_analytics_cached();
$cfTraffic = $cf_data['traffic'];
$cfSecurity = $cf_data['security'];
$cfPerformance = $cf_data['performance'];
$cfGeo = $cf_data['geo'];
$cfStatusCodes = $cf_data['status_codes'];

// Get currency settings from database
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

// Get time range from request
$time_range = $_GET['range'] ?? '30d';
$allowed_ranges = ['7d', '30d', '90d', '1y'];
if (!in_array($time_range, $allowed_ranges)) {
    $time_range = '30d';
}

// Calculate date ranges
function getDateRange($range) {
    $end_date = new DateTime();
    $start_date = clone $end_date;
    $days = 30; // default
    
    switch ($range) {
        case '7d':
            $start_date->modify('-7 days');
            $days = 7;
            break;
        case '30d':
        case '90d':
            if ($range === '30d') {
                $start_date->modify('-30 days');
                $days = 30;
            } else {
                $start_date->modify('-90 days');
                $days = 90;
            }
            break;
        case '1y':
            $start_date->modify('-1 year');
            $days = 365;
            break;
        default:
            $start_date->modify('-30 days');
    }
    
    return [
        'start' => $start_date->format('Y-m-d'),
        'end' => $end_date->format('Y-m-d'),
        'days' => $days
    ];
}

// Get current and previous period ranges
$current_period = getDateRange($time_range);
$previous_start = new DateTime($current_period['start']);
$previous_start->modify('-' . $current_period['days'] . ' days');
$previous_end = new DateTime($current_period['start']);
$previous_end->modify('-1 day');
$previous_period = [
    'start' => $previous_start->format('Y-m-d'),
    'end' => $previous_end->format('Y-m-d'),
    'days' => $current_period['days']
];

// Helper function to filter data by date range
/**
 * @param array<int|string, mixed> $items
 * @param string $start_date
 * @param string $end_date
 * @param string $date_field
 * @return array<int|string, mixed>
 */
function filterByDateRange($items, $start_date, $end_date, $date_field = 'created') {
    return array_filter($items, function($item) use ($start_date, $end_date, $date_field) {
        $item_date = $item[$date_field] ?? null;
        if (!$item_date) {
            return false;
        }
        
        // Extract date only (ignore time)
        $item_date = substr($item_date, 0, 10);
        
        return $item_date >= $start_date && $item_date <= $end_date;
    });
}

// Load data from database - OPTIMIZED: Only load data for the selected time range
$cache = new DashboardCache();
$cache_key = 'dashboard_data_' . $time_range;

$dashboard_data = $cache->remember($cache_key, function() use ($current_period, $previous_period) {
    // Load only orders within the time range (+ previous period for comparison)
    $all_date_range_start = $previous_period['start'];
    $all_date_range_end = $current_period['end'];
    
    $all_orders = array_filter(get_orders_data(), function($order) use ($all_date_range_start, $all_date_range_end) {
        $order_date = substr($order['created'] ?? '', 0, 10);
        return $order_date >= $all_date_range_start && $order_date <= $all_date_range_end;
    });
    
    $all_customers = array_filter(get_customers_data(), function($customer) use ($all_date_range_start, $all_date_range_end) {
        $customer_date = substr($customer['created'] ?? '', 0, 10);
        return $customer_date >= $all_date_range_start && $customer_date <= $all_date_range_end;
    });
    
    $all_inquiries = array_filter(get_inquiries_data(), function($inquiry) use ($all_date_range_start, $all_date_range_end) {
        $inquiry_date = substr($inquiry['created'] ?? '', 0, 10);
        return $inquiry_date >= $all_date_range_start && $inquiry_date <= $all_date_range_end;
    });
    
    return [
        'orders' => $all_orders,
        'customers' => $all_customers,
        'inquiries' => $all_inquiries
    ];
}, 300); // Cache for 5 minutes

$all_orders = $dashboard_data['orders'];
$all_customers = $dashboard_data['customers'];
$all_inquiries = $dashboard_data['inquiries'];

// Products and categories are loaded without filtering (they don't change often)
$all_products = get_products_data();
$categories = get_categories_data();

// Filter data by current period
$products = $all_products; // Products don't need filtering for most metrics
$orders = filterByDateRange($all_orders, $current_period['start'], $current_period['end'], 'created');
$customers = filterByDateRange($all_customers, $current_period['start'], $current_period['end'], 'created');
$inquiries = filterByDateRange($all_inquiries, $current_period['start'], $current_period['end'], 'created');

// Filter data by previous period for comparison
$prev_orders = filterByDateRange($all_orders, $previous_period['start'], $previous_period['end'], 'created');
$prev_customers = filterByDateRange($all_customers, $previous_period['start'], $previous_period['end'], 'created');
$prev_inquiries = filterByDateRange($all_inquiries, $previous_period['start'], $previous_period['end'], 'created');

// Create category ID to name mapping
$category_names = [];
foreach ($categories as $cat_id => $cat_data) {
    $category_names[$cat_id] = $cat_data['name'] ?? 'Unknown';
}

// Helper function to calculate percentage change
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

// Helper function to format percentage change with sign and class
function formatPercentageChange($change) {
    $sign = $change > 0 ? '+' : '';
    if ($change > 0) {
        $class = 'positive';
    } elseif ($change < 0) {
        $class = 'negative';
    } else {
        $class = 'neutral';
    }
    return [
        'value' => $sign . $change . '%',
        'class' => $class,
        'numeric' => $change
    ];
}

// Helper function to get period label
function getPeriodLabel($range) {
    $labels = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '1y' => 'Last year'
    ];
    return $labels[$range] ?? 'Last 30 days';
}

// Analytics and financial data (can be extended to use database tables in future)
$analytics = [];
$financial = [];

// Load analytics data from database for current period
try {
    $analytics_rows = db_table('analytics_daily')->where('date', '>=', $current_period['start'])
                                                 ->where('date', '<=', $current_period['end'])
                                                 ->all();
    
    if (!empty($analytics_rows)) {
        $total_visits = 0;
        $unique_visitors = 0;
        $page_views = 0;
        $bounce_rates = [];
        $traffic_sources_combined = [
            'direct' => 0,
            'search' => 0,
            'social' => 0,
            'referral' => 0,
            'email' => 0
        ];
        
        foreach ($analytics_rows as $row) {
            $total_visits += intval($row['total_visits'] ?? 0);
            $unique_visitors += intval($row['unique_visitors'] ?? 0);
            $page_views += intval($row['page_views'] ?? 0);
            if (!empty($row['bounce_rate'])) {
                $bounce_rates[] = floatval($row['bounce_rate']);
            }
            
            $sources = json_decode($row['traffic_sources'] ?? '{}', true);
            if (is_array($sources)) {
                foreach ($sources as $source => $count) {
                    if (isset($traffic_sources_combined[$source])) {
                        $traffic_sources_combined[$source] += $count;
                    }
                }
            }
        }
        
        $analytics = [
            'total_visits' => $total_visits,
            'unique_visitors' => $unique_visitors,
            'page_views' => $page_views,
            'bounce_rate' => !empty($bounce_rates) ? array_sum($bounce_rates) / count($bounce_rates) : 0,
            'sources' => $traffic_sources_combined
        ];
    }
} catch (Exception $e) {
    // Fallback to empty array if table doesn't exist yet
}

// Load financial data from database for current period
try {
    $financial_row = db_table('financial_data')->where('period_start', '<=', $current_period['end'])
                                                ->where('period_end', '>=', $current_period['start'])
                                                ->first();
    
    if ($financial_row) {
        $financial = [
            'total_expenses' => floatval($financial_row['total_expenses'] ?? 0),
            'hosting_costs' => floatval($financial_row['hosting_costs'] ?? 0),
            'marketing_costs' => floatval($financial_row['marketing_costs'] ?? 0),
            'courier_costs' => floatval($financial_row['courier_costs'] ?? 0),
            'other_costs' => floatval($financial_row['other_costs'] ?? 0),
            'tax_rate' => floatval($financial_row['tax_rate'] ?? 20)
        ];
    }
} catch (Exception $e) {
    // Fallback to empty array if table doesn't exist yet
}

// Calculate statistics
$total_orders = count($orders);
$total_customers = count($customers);
$total_products = count($products);
$total_inquiries = count($inquiries);
$pending_inquiries = count(array_filter($inquiries, function($i) { return ($i['status'] ?? 'pending') === 'pending'; }));

// Order statistics
$pending_orders = 0;
$confirmed_orders = 0;
$processing_orders = 0;
$shipped_orders = 0;
$delivered_orders = 0;
$cancelled_orders = 0;
$total_revenue = 0;

foreach ($orders as $order) {
    $status = $order['status'] ?? 'pending';
    switch ($status) {
        case 'pending': $pending_orders++; break;
        case 'confirmed': $confirmed_orders++; break;
        case 'processing': $processing_orders++; break;
        case 'shipped': $shipped_orders++; break;
        case 'delivered': $delivered_orders++; break;
        case 'cancelled': $cancelled_orders++; break;
        default: break;
    }
    
    // Add to revenue only if not cancelled
    if ($status !== 'cancelled') {
        $total_revenue += floatval($order['total'] ?? 0);
    }
}

// Financial calculations
$total_income = $total_revenue;
$total_expenses = floatval($financial['total_expenses'] ?? 0);
$tax_rate = floatval($financial['tax_rate'] ?? 20); // Default 20% VAT for Bulgaria
$taxes_owed = ($total_income - $total_expenses) * ($tax_rate / 100);
$net_profit = $total_income - $total_expenses - $taxes_owed;

// Calculate previous period statistics for comparison
$prev_total_orders = count($prev_orders);
$prev_total_customers = count($prev_customers);
$prev_total_revenue = 0;

foreach ($prev_orders as $order) {
    $status = $order['status'] ?? 'pending';
    if ($status !== 'cancelled') {
        $prev_total_revenue += floatval($order['total'] ?? 0);
    }
}

$prev_total_income = $prev_total_revenue;
$prev_taxes_owed = ($prev_total_income - $total_expenses) * ($tax_rate / 100);
$prev_net_profit = $prev_total_income - $total_expenses - $prev_taxes_owed;

// Calculate percentage changes
$revenue_change = formatPercentageChange(calculatePercentageChange($total_revenue, $prev_total_revenue));
$orders_change = formatPercentageChange(calculatePercentageChange($total_orders, $prev_total_orders));
$customers_change = formatPercentageChange(calculatePercentageChange($total_customers, $prev_total_customers));
$profit_change = formatPercentageChange(calculatePercentageChange($net_profit, $prev_net_profit));

// Web traffic statistics
$total_visits = intval($analytics['total_visits'] ?? 0);
$unique_visitors = intval($analytics['unique_visitors'] ?? 0);
$page_views = intval($analytics['page_views'] ?? 0);
$bounce_rate = floatval($analytics['bounce_rate'] ?? 0);

// Traffic sources
$traffic_sources = $analytics['sources'] ?? [
    'direct' => 0,
    'search' => 0,
    'social' => 0,
    'referral' => 0,
    'email' => 0
];

// Courier statistics
$econt_shipments = 0;
$speedy_shipments = 0;
$cod_orders = 0;

foreach ($orders as $order) {
    $courier = $order['shipping']['courier'] ?? '';
    if ($courier === 'econt') {
        $econt_shipments++;
    }
    if ($courier === 'speedy') {
        $speedy_shipments++;
    }
    if (isset($order['payment']['cod']) && $order['payment']['cod']) {
        $cod_orders++;
    }
}

// Recent orders (last 5)
$recent_orders = array_slice(array_reverse($orders), 0, 5);

// Top selling products
$product_sales = [];
foreach ($orders as $order) {
    if (isset($order['items']) && ($order['status'] ?? '') !== 'cancelled') {
        foreach ($order['items'] as $item) {
            $prod_id = $item['id'] ?? '';
            if (!isset($product_sales[$prod_id])) {
                $product_sales[$prod_id] = [
                    'name' => $item['name'] ?? 'Unknown',
                    'quantity' => 0,
                    'revenue' => 0
                ];
            }
            $product_sales[$prod_id]['quantity'] += intval($item['quantity'] ?? 0);
            $product_sales[$prod_id]['revenue'] += floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 0);
        }
    }
}

// Sort by quantity sold
usort($product_sales, function($a, $b) {
    return $b['quantity'] - $a['quantity'];
});
$top_products = array_slice($product_sales, 0, 5);

// Category breakdown
$category_counts = [];
foreach ($products as $product) {
    $category = $product['category'] ?? 'general';
    if (!isset($category_counts[$category])) {
        $category_counts[$category] = 0;
    }
    $category_counts[$category]++;
}
arsort($category_counts);
$top_categories = array_slice($category_counts, 0, 6, true);

// Customer growth (last 6 months)
$customer_growth = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $customer_growth[$month] = 0;
}
foreach ($customers as $customer) {
    $created = $customer['created'] ?? date('Y-m-d');
    $month = date('Y-m', strtotime($created));
    if (isset($customer_growth[$month])) {
        $customer_growth[$month]++;
    }
}

// Recent inquiries
$recent_inquiries = array_slice(array_reverse($inquiries), 0, 5);

?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h2 class="page-title"><?php echo icon_package(28); ?> <?php echo __('admin.dashboard_overview'); ?></h2>
        <div class="dashboard-period">
            <div class="time-range-selector">
                <button class="time-range-button"id="timeRangeButton">
                    <span><?php echo getPeriodLabel($time_range); ?></span>
                    <svg width="12"height="12"viewBox="0 0 12 12"fill="currentColor">
                        <path d="M2 4l4 4 4-4"stroke="currentColor"stroke-width="2"fill="none"/>
                    </svg>
                </button>
                <div class="time-range-dropdown"id="timeRangeDropdown">
                    <a href="?range=7d"class="range-option <?php echo $time_range === '7d' ? 'active' : ''; ?>">Last 7 days</a>
                    <a href="?range=30d"class="range-option <?php echo $time_range === '30d' ? 'active' : ''; ?>">Last 30 days</a>
                    <a href="?range=90d"class="range-option <?php echo $time_range === '90d' ? 'active' : ''; ?>">Last 90 days</a>
                    <a href="?range=1y"class="range-option <?php echo $time_range === '1y' ? 'active' : ''; ?>">Last year</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="metrics-row">
        <div class="metric-card revenue">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_revenue'); ?></div>
                <div class="metric-value"><?php echo $currency_symbol; ?><?php echo number_format($total_revenue, 2); ?></div>
                <div class="metric-change <?php echo $revenue_change['class']; ?>">
                    <?php echo $revenue_change['value']; ?> from previous period
                </div>
            </div>
        </div>
        <div class="metric-card orders">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_orders'); ?></div>
                <div class="metric-value"><?php echo $total_orders; ?></div>
                <div class="metric-change <?php echo $orders_change['class']; ?>">
                    <?php echo $orders_change['value']; ?> from previous period
                </div>
            </div>
        </div>
        <div class="metric-card customers">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_customers'); ?></div>
                <div class="metric-value"><?php echo $total_customers; ?></div>
                <div class="metric-change <?php echo $customers_change['class']; ?>">
                    <?php echo $customers_change['value']; ?> from previous period
                </div>
            </div>
        </div>
        <div class="metric-card profit">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.net_profit'); ?></div>
                <div class="metric-value"><?php echo $currency_symbol; ?><?php echo number_format($net_profit, 2); ?></div>
                <div class="metric-change <?php echo $profit_change['class']; ?>">
                    <?php echo $profit_change['value']; ?> from previous period
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card">
            <h3><?php echo icon_package(20); ?> <?php echo __('dashboard.order_status_breakdown'); ?></h3>
            <div class="chart-container">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item"><span class="legend-dot pending"></span> Pending: <?php echo $pending_orders; ?></div>
                <div class="legend-item"><span class="legend-dot confirmed"></span> Confirmed: <?php echo $confirmed_orders; ?></div>
                <div class="legend-item"><span class="legend-dot processing"></span> Processing: <?php echo $processing_orders; ?></div>
                <div class="legend-item"><span class="legend-dot shipped"></span> Shipped: <?php echo $shipped_orders; ?></div>
                <div class="legend-item"><span class="legend-dot delivered"></span> Delivered: <?php echo $delivered_orders; ?></div>
                <div class="legend-item"><span class="legend-dot cancelled"></span> Cancelled: <?php echo $cancelled_orders; ?></div>
            </div>
        </div>
        
        <div class="chart-card">
            <h3>📊 Category Breakdown</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-legend">
                <?php foreach (array_slice($top_categories, 0, 6) as $cat_id => $count): ?>
                    <?php $cat_name = $category_names[$cat_id] ?? ucfirst($cat_id); ?>
                    <div class="legend-item"><span class="legend-dot"style="background: hsl(<?php echo array_search($cat_id, array_keys($top_categories)) * 60; ?>, 70%, 60%);"></span> <?php echo htmlspecialchars($cat_name); ?>: <?php echo $count; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Cloudflare Analytics Row -->
    <div class="cloudflare-stats-row">
        <div class="cf-stat-card">
            <div class="cf-icon">🌐</div>
            <div class="cf-content">
                <div class="cf-label"><?php echo __('dashboard.total_requests'); ?></div>
                <div class="cf-value"><?php echo number_format($cfTraffic['requests']); ?></div>
                <div class="cf-sublabel"><?php echo __('dashboard.last_30_days'); ?></div>
            </div>
        </div>
        <div class="cf-stat-card">
            <div class="cf-icon">👥</div>
            <div class="cf-content">
                <div class="cf-label">Unique Visitors</div>
                <div class="cf-value"><?php echo number_format($cfTraffic['unique_visitors']); ?></div>
                <div class="cf-sublabel">From Cloudflare</div>
            </div>
        </div>
        <div class="cf-stat-card security">
            <div class="cf-icon">🛡️</div>
            <div class="cf-content">
                <div class="cf-label">Threats Blocked</div>
                <div class="cf-value"><?php echo number_format($cfSecurity['threats_blocked']); ?></div>
                <div class="cf-sublabel">Last 7 days</div>
            </div>
        </div>
        <div class="cf-stat-card performance">
            <div class="cf-icon">⚡</div>
            <div class="cf-content">
                <div class="cf-label">Cache Hit Rate</div>
                <div class="cf-value"><?php echo $cfPerformance['cache_hit_rate']; ?>%</div>
                <div class="cf-sublabel"><?php echo CloudflareAnalytics::formatBytes($cfTraffic['bandwidth']); ?> saved</div>
            </div>
        </div>
        <div class="cf-stat-card ssl">
            <div class="cf-icon">🔒</div>
            <div class="cf-content">
                <div class="cf-label">SSL Encrypted</div>
                <div class="cf-value"><?php echo $cfPerformance['ssl_encrypted']; ?>%</div>
                <div class="cf-sublabel">Secure traffic</div>
            </div>
        </div>
    </div>

    <!-- Geographic & Performance Row -->
    <div class="analytics-row">
        <div class="geographic-widget">
            <h3>🗺️ Geographic Distribution</h3>
            <div class="geo-list">
                <?php
                $totalGeoRequests = array_sum(array_map(function($item) { return $item['sum']['requests'] ?? 0; }, $cfGeo));
                foreach (array_slice($cfGeo, 0, 8) as $index => $geo):
                    $country = $geo['dimensions']['clientCountryName'] ?? 'Unknown';
                    $requests = $geo['sum']['requests'] ?? 0;
                    $percentage = $totalGeoRequests > 0 ? round(($requests / $totalGeoRequests) * 100, 1) : 0;
                ?>
                    <div class="geo-item">
                        <div class="geo-info">
                            <span class="geo-rank">#<?php echo $index + 1; ?></span>
                            <span class="geo-country"><?php echo htmlspecialchars($country); ?></span>
                            <span class="geo-percentage"><?php echo $percentage; ?>%</span>
                        </div>
                        <div class="geo-progress">
                            <div class="geo-progress-bar"style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="geo-stats">
                            <span><?php echo number_format($requests); ?> requests</span>
                            <span><?php echo CloudflareAnalytics::formatBytes($geo['sum']['bytes'] ?? 0); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="status-codes-widget">
            <h3>📡 HTTP Status Codes</h3>
            <div class="chart-container">
                <canvas id="statusCodesChart"></canvas>
            </div>
            <div class="status-grid">
                <div class="status-item success">
                    <div class="status-label"><?php echo __('dashboard.http_success_label'); ?></div>
                    <div class="status-count"><?php echo number_format($cfStatusCodes['2xx']); ?></div>
                </div>
                <div class="status-item redirect">
                    <div class="status-label"><?php echo __('dashboard.http_redirect_label'); ?></div>
                    <div class="status-count"><?php echo number_format($cfStatusCodes['3xx']); ?></div>
                </div>
                <div class="status-item client-error">
                    <div class="status-label"><?php echo __('dashboard.http_client_error_label'); ?></div>
                    <div class="status-count"><?php echo number_format($cfStatusCodes['4xx']); ?></div>
                </div>
                <div class="status-item server-error">
                    <div class="status-label"><?php echo __('dashboard.http_server_error_label'); ?></div>
                    <div class="status-count"><?php echo number_format($cfStatusCodes['5xx']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth & Activity Row -->
    <div class="growth-activity-row">
        <div class="chart-card">
            <h3>📈 Customer Growth</h3>
            <div class="chart-container">
                <canvas id="customerGrowthChart"></canvas>
            </div>
        </div>

        <div class="data-card inquiries-widget">
            <h3><?php echo icon_mail(20); ?> Latest Inquiries</h3>
            <div class="compact-list">
                <?php if (!empty($recent_inquiries)): ?>
                    <?php foreach ($recent_inquiries as $inquiry): ?>
                        <div class="inquiry-item">
                            <div class="inquiry-header">
                                <strong><?php echo htmlspecialchars($inquiry['name'] ?? 'Anonymous'); ?></strong>
                                <span class="status-badge badge-<?php echo htmlspecialchars($inquiry['status'] ?? 'pending'); ?>">
                                    <?php echo ucfirst($inquiry['status'] ?? 'pending'); ?>
                                </span>
                            </div>
                            <div class="inquiry-subject"><?php echo htmlspecialchars(substr($inquiry['message'] ?? '', 0, 60)); ?>...</div>
                            <div class="inquiry-meta">
                                <span><?php echo htmlspecialchars($inquiry['email'] ?? ''); ?></span>
                                <span><?php echo date('M d, H:i', strtotime($inquiry['created'] ?? 'now')); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state"><?php echo __('dashboard.no_inquiries_yet'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="tables-row">
        <?php if (!empty($top_products)): ?>
        <div class="data-card">
            <h3>🏆 <?php echo __('dashboard.top_products'); ?></h3>
            <div class="compact-list">
                <?php foreach (array_slice($top_products, 0, 5) as $index => $product): ?>
                    <div class="compact-list-item">
                        <span class="item-rank">#<?php echo $index + 1; ?></span>
                        <span class="item-name"><?php echo htmlspecialchars($product['name']); ?></span>
                        <span class="item-stat"><?php echo $product['quantity']; ?> sold</span>
                        <span class="item-value"><?php echo $currency_symbol; ?><?php echo number_format($product['revenue'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($recent_orders)): ?>
        <div class="data-card">
            <h3><?php echo icon_package(20); ?> <?php echo __('dashboard.recent_orders'); ?></h3>
            <div class="compact-table">
                <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                    <div class="compact-table-row">
                        <div class="row-main">
                            <strong><?php echo htmlspecialchars($order['id']); ?></strong>
                            <span class="row-customer"><?php echo htmlspecialchars($order['customer']['name'] ?? 'Guest'); ?></span>
                        </div>
                        <div class="row-meta">
                            <span class="row-amount"><?php echo $currency_symbol; ?><?php echo number_format($order['total'], 2); ?></span>
                            <?php
                            $status = $order['status'] ?? 'pending';
                            echo '<span class="status-badge badge-' . $status . '">' . ucfirst($status) . '</span>';
                            ?>
                            <span class="row-date"><?php echo date('M d', strtotime($order['created'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Stats Grid -->
    <div class="quick-stats-grid">
        <div class="quick-stat">
            <div class="stat-label"><?php echo icon_mail(16); ?> <?php echo __('inquiry.title'); ?></div>
            <div class="stat-number"><?php echo $total_inquiries; ?></div>
            <div class="stat-change"><?php echo $pending_inquiries; ?> pending</div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">📦 Econt</div>
            <div class="stat-number"><?php echo $econt_shipments; ?></div>
            <div class="stat-change">shipments</div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">⚡ Speedy</div>
            <div class="stat-number"><?php echo $speedy_shipments; ?></div>
            <div class="stat-change"><?php echo __('dashboard.shipments'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">💵 COD</div>
            <div class="stat-number"><?php echo $cod_orders; ?></div>
            <div class="stat-change"><?php echo __('dashboard.orders'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">💸 Expenses</div>
            <div class="stat-number"><?php echo $currency_symbol; ?><?php echo number_format($total_expenses, 2); ?></div>
            <div class="stat-change"><?php echo __('dashboard.operating_costs'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">🏛️ Taxes</div>
            <div class="stat-number"><?php echo $currency_symbol; ?><?php echo number_format($taxes_owed, 2); ?></div>
            <div class="stat-change"><?php echo __('dashboard.vat'); ?> <?php echo $tax_rate; ?>%</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-bar">
        <a href="?section=orders"class="action-btn primary">📦 <?php echo __('admin.view_all_orders'); ?></a>
        <a href="?section=products&action=new"class="action-btn success">➕ <?php echo __('admin.add_new_product'); ?></a>
        <a href="?section=users"class="action-btn info">👥 <?php echo __('admin.manage_customers'); ?></a>
        <a href="?section=settings"class="action-btn secondary">⚙️ <?php echo __('settings.title'); ?></a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"defer></script>
<script>
// Order Status Chart
const orderCtx = document.getElementById('orderStatusChart');
if (orderCtx) {
    new Chart(orderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $pending_orders; ?>,
                    <?php echo $confirmed_orders; ?>,
                    <?php echo $processing_orders; ?>,
                    <?php echo $shipped_orders; ?>,
                    <?php echo $delivered_orders; ?>,
                    <?php echo $cancelled_orders; ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(40, 167, 69, 0.9)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// Category Breakdown Chart
const categoryCtx = document.getElementById('categoryChart');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php
                $category_labels = array_map(function($cat_id) use ($category_names) {
                    return $category_names[$cat_id] ?? ucfirst($cat_id);
                }, array_keys($top_categories));
                echo json_encode($category_labels);
            ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($top_categories)); ?>,
                backgroundColor: [
                    'hsl(0, 70%, 60%)',
                    'hsl(60, 70%, 60%)',
                    'hsl(120, 70%, 60%)',
                    'hsl(180, 70%, 60%)',
                    'hsl(240, 70%, 60%)',
                    'hsl(300, 70%, 60%)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// HTTP Status Codes Chart
const statusCodesCtx = document.getElementById('statusCodesChart');
if (statusCodesCtx) {
    new Chart(statusCodesCtx, {
        type: 'bar',
        data: {
            labels: ['Success', 'Redirect', 'Client Error', 'Server Error'],
            datasets: [{
                label: 'Requests',
                data: [
                    <?php echo $cfStatusCodes['2xx']; ?>,
                    <?php echo $cfStatusCodes['3xx']; ?>,
                    <?php echo $cfStatusCodes['4xx']; ?>,
                    <?php echo $cfStatusCodes['5xx']; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: '#a896ba' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#a896ba' }
                }
            }
        }
    });
}

// Customer Growth Chart
const customerGrowthCtx = document.getElementById('customerGrowthChart');
if (customerGrowthCtx) {
    new Chart(customerGrowthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($customer_growth)); ?>,
            datasets: [{
                label: 'New Customers',
                data: <?php echo json_encode(array_values($customer_growth)); ?>,
                backgroundColor: 'rgba(138, 51, 196, 0.2)',
                borderColor: 'rgba(138, 51, 196, 0.8)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(138, 51, 196, 1)',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: '#a896ba', stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#a896ba' }
                }
            }
        }
    });
}

// Time Range Selector Toggle
const timeRangeButton = document.getElementById('timeRangeButton');
const timeRangeDropdown = document.getElementById('timeRangeDropdown');

if (timeRangeButton && timeRangeDropdown) {
    timeRangeButton.addEventListener('click', function(e) {
        e.stopPropagation();
        timeRangeDropdown.classList.toggle('show');
        timeRangeButton.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!timeRangeButton.contains(e.target) && !timeRangeDropdown.contains(e.target)) {
            timeRangeDropdown.classList.remove('show');
            timeRangeButton.classList.remove('active');
        }
    });

    // Close dropdown when selecting an option
    const rangeOptions = timeRangeDropdown.querySelectorAll('.range-option');
    rangeOptions.forEach(option => {
        option.addEventListener('click', function() {
            timeRangeDropdown.classList.remove('show');
            timeRangeButton.classList.remove('active');
        });
    });
}
</script>

