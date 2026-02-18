<?php
/**
 * Comprehensive Admin Dashboard with E-Commerce Statistics + Cloudflare Analytics
 */

// Load Cloudflare Analytics
require_once __DIR__ . '/../../includes/cloudflare-analytics.php';
$cloudflare = new CloudflareAnalytics();
$cfTraffic = $cloudflare->getTrafficStats();
$cfSecurity = $cloudflare->getSecurityStats();
$cfPerformance = $cloudflare->getPerformanceStats();
$cfGeo = $cloudflare->getGeographicData(10);
$cfStatusCodes = $cloudflare->getStatusCodeStats();

// Get currency settings from database
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

// Load data from database
$products = get_products_data();
$orders = get_orders_data();
$customers = get_customers_data();
$inquiries = get_inquiries_data();

// Analytics and financial data (can be extended to use database tables in future)
$analytics = [];
$financial = [];

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
            <span class="period-label"><?php echo __('dashboard.last_30_days'); ?></span>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="metrics-row">
        <div class="metric-card revenue">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_revenue'); ?></div>
                <div class="metric-value"><?php echo $currency_symbol; ?><?php echo number_format($total_revenue, 2); ?></div>
            </div>
        </div>
        <div class="metric-card orders">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_orders'); ?></div>
                <div class="metric-value"><?php echo $total_orders; ?></div>
                <div class="metric-sublabel"><?php echo $pending_orders; ?> <?php echo __('dashboard.pending'); ?></div>
            </div>
        </div>
        <div class="metric-card customers">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.total_customers'); ?></div>
                <div class="metric-value"><?php echo $total_customers; ?></div>
                <div class="metric-sublabel"><?php echo $total_products; ?> <?php echo __('dashboard.products_count'); ?></div>
            </div>
        </div>
        <div class="metric-card profit">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-label"><?php echo __('dashboard.net_profit'); ?></div>
                <div class="metric-value"><?php echo $currency_symbol; ?><?php echo number_format($net_profit, 2); ?></div>
                <div class="metric-sublabel"><?php echo __('dashboard.after_expenses'); ?></div>
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
                <?php foreach (array_slice($top_categories, 0, 6) as $cat => $count): ?>
                    <div class="legend-item"><span class="legend-dot"style="background: hsl(<?php echo (array_search($cat, array_keys($top_categories)) * 60); ?>, 70%, 60%);"></span> <?php echo ucfirst($cat); ?>: <?php echo $count; ?></div>
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
                $totalGeoRequests = array_sum(array_column($cfGeo, function($item) { return $item['sum']['requests']; }));
                foreach (array_slice($cfGeo, 0, 8) as $index => $geo): 
                    $country = $geo['dimensions']['clientCountryName'] ?? 'Unknown';
                    $requests = $geo['sum']['requests'] ?? 0;
                    $percentage = $totalGeoRequests > 0 ? round(($requests / $totalGeoRequests) * 100, 1) : 0;
                ?>
                    <div class="geo-item">
                        <div class="geo-info">
                            <span class="geo-rank">#<?php echo ($index + 1); ?></span>
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
                        <span class="item-rank">#<?php echo ($index + 1); ?></span>
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
            <div class="stat-change"><?php echo $pending_inquiries; ?> <?php echo __('dashboard.pending'); ?></div>
        </div>
        <div class="quick-stat">
            <div class="stat-label">📦 Econt</div>
            <div class="stat-number"><?php echo $econt_shipments; ?></div>
            <div class="stat-change"><?php echo __('dashboard.shipments'); ?></div>
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
            labels: [
                '<?php echo __('order.pending'); ?>',
                '<?php echo __('order.confirmed'); ?>',
                '<?php echo __('order.processing'); ?>',
                '<?php echo __('order.shipped'); ?>',
                '<?php echo __('order.delivered'); ?>',
                '<?php echo __('order.cancelled'); ?>'
            ],
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
            labels: <?php echo json_encode(array_keys($top_categories)); ?>,
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
            labels: [
                '<?php echo __('dashboard.http_success'); ?>',
                '<?php echo __('dashboard.http_redirect'); ?>',
                '<?php echo __('dashboard.http_client_error'); ?>',
                '<?php echo __('dashboard.http_server_error'); ?>'
            ],
            datasets: [{
                label: '<?php echo __('dashboard.requests'); ?>',
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
                label: '<?php echo __('dashboard.new_customers'); ?>',
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
</script>

