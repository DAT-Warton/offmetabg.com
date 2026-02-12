<?php
/**
 * Comprehensive Admin Dashboard with E-Commerce Statistics
 */

// Load data from JSON files
$products = load_json('storage/products.json');
$orders = load_json('storage/orders.json');
$customers = load_json('storage/customers.json');
$inquiries = load_json('storage/inquiries.json');
$analytics = load_json('storage/analytics.json');
$financial = load_json('storage/financial.json');

// Calculate statistics
$total_orders = count($orders);
$total_customers = count($customers);
$total_products = count($products);
$total_inquiries = count($inquiries);
$pending_inquiries = count(array_filter($inquiries, fn($i) => ($i['status'] ?? 'pending') === 'pending'));

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
    if ($courier === 'econt') $econt_shipments++;
    if ($courier === 'speedy') $speedy_shipments++;
    if (isset($order['payment']['cod']) && $order['payment']['cod']) $cod_orders++;
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

?>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: var(--bg-secondary, white);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px var(--shadow, rgba(0,0,0,0.1));
        border-left: 4px solid #667eea;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px var(--shadow, rgba(0,0,0,0.15));
    }
    .stat-card h3 {
        color: var(--text-secondary, #6b7280);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .stat-card .number {
        font-size: 32px;
        font-weight: bold;
        color: var(--text-primary, #1f2937);
        margin-bottom: 5px;
    }
    .stat-card .subtitle {
        color: var(--text-secondary, #9ca3af);
        font-size: 14px;
    }
    .stat-card.revenue {
        border-left-color: #10b981;
    }
    .stat-card.orders {
        border-left-color: #3b82f6;
    }
    .stat-card.customers {
        border-left-color: #f59e0b;
    }
    .stat-card.profit {
        border-left-color: #8b5cf6;
    }
    .stat-card.expenses {
        border-left-color: #ef4444;
    }
    .stat-card.taxes {
        border-left-color: #ec4899;
    }
    .stat-card.traffic {
        border-left-color: #06b6d4;
    }
    .dashboard-section {
        background: var(--bg-secondary, white);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px var(--shadow, rgba(0,0,0,0.1));
        margin-bottom: 25px;
    }
    .dashboard-section h2 {
        color: var(--text-primary, #1f2937);
        margin-bottom: 20px;
        border-bottom: 2px solid var(--border-color, #e5e7eb);
        padding-bottom: 10px;
    }
    .sources-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
    }
    .source-item {
        padding: 15px;
        background: var(--bg-primary, #f9fafb);
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 8px;
        text-align: center;
    }
    .source-item .source-name {
        font-weight: 600;
        color: var(--text-secondary, #6b7280);
        font-size: 14px;
        margin-bottom: 8px;
    }
    .source-item .source-value {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }
    .recent-orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    .recent-orders-table th {
        background: var(--bg-primary, #f9fafb);
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: var(--text-secondary, #6b7280);
        border-bottom: 2px solid var(--border-color, #e5e7eb);
    }
    .recent-orders-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
        color: var(--text-primary, #1f2937);
    }
    .recent-orders-table tr:hover {
        background: var(--bg-primary, #f9fafb);
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-confirmed { background: #dbeafe; color: #1e40af; }
    .badge-processing { background: #e0e7ff; color: #3730a3; }
    .badge-shipped { background: #d1fae5; color: #065f46; }
    .badge-delivered { background: #d1fae5; color: #065f46; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .top-products-list {
        list-style:none;
        padding: 0;
    }
    .top-products-list li {
        padding: 12px;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .top-products-list li:last-child {
        border-bottom: none;
    }
    .product-name {
        font-weight: 600;
        color: var(--text-primary, #1f2937);
    }
    .product-stats {
        text-align: right;
        color: var(--text-secondary, #6b7280);
        font-size: 14px;
    }
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .quick-action-btn {
        display: block;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        text-align: center;
        font-weight: 600;
        transition: transform 0.2s;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
    }
</style>

<div>
    <h2 style="color: var(--text-primary, #1f2937); font-size: 28px; margin-bottom: 30px;"><?php echo icon_package(28); ?> <?php echo __('admin.dashboard_overview'); ?></h2>

    <!-- Key Metrics -->
    <div class="dashboard-grid">
        <div class="stat-card revenue">
            <h3>💰 <?php echo __('dashboard.total_revenue'); ?></h3>
            <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
            <div class="subtitle"><?php echo $total_orders; ?> <?php echo __('orders'); ?></div>
        </div>
        <div class="stat-card profit">
            <h3>📈 <?php echo __('dashboard.net_profit'); ?></h3>
            <div class="number">$<?php echo number_format($net_profit, 2); ?></div>
            <div class="subtitle"><?php echo __('dashboard.after_expenses'); ?></div>
        </div>
        <div class="stat-card expenses">
            <h3>💸 <?php echo __('dashboard.total_expenses'); ?></h3>
            <div class="number">$<?php echo number_format($total_expenses, 2); ?></div>
            <div class="subtitle"><?php echo __('dashboard.operating_costs'); ?></div>
        </div>
        <div class="stat-card taxes">
            <h3>🏛️ <?php echo __('dashboard.taxes_owed'); ?></h3>
            <div class="number">$<?php echo number_format($taxes_owed, 2); ?></div>
            <div class="subtitle"><?php echo __('dashboard.vat'); ?> <?php echo$tax_rate; ?>%</div>
        </div>
        <div class="stat-card orders">
            <h3>📦 <?php echo __('dashboard.total_orders'); ?></h3>
            <div class="number"><?php echo $total_orders; ?></div>
            <div class="subtitle"><?php echo $pending_orders; ?> <?php echo __('dashboard.pending'); ?></div>
        </div>
        <div class="stat-card customers">
            <h3>👥 <?php echo __('dashboard.total_customers'); ?></h3>
            <div class="number"><?php echo $total_customers; ?></div>
            <div class="subtitle"><?php echo $total_products; ?> <?php echo __('dashboard.products_count'); ?></div>
        </div>
        <div class="stat-card">
            <h3><?php echo icon_mail(20); ?> <?php echo __('inquiry.title'); ?></h3>
            <div class="number"><?php echo $total_inquiries; ?></div>
            <div class="subtitle"><?php echo $pending_inquiries; ?> <?php echo __('inquiry.pending'); ?></div>
        </div>
        <div class="stat-card traffic">
            <h3>👁️ <?php echo __('dashboard.website_traffic'); ?></h3>
            <div class="number"><?php echo number_format($total_visits); ?></div>
            <div class="subtitle"><?php echo number_format($unique_visitors); ?> <?php echo __('dashboard.unique_visitors'); ?></div>
        </div>
        <div class="stat-card">
            <h3><?php echo icon_home(20); ?> <?php echo __('dashboard.page_views'); ?></h3>
            <div class="number"><?php echo number_format($page_views); ?></div>
            <div class="subtitle"><?php echo number_format($bounce_rate, 1); ?>% <?php echo __('dashboard.bounce_rate'); ?></div>
        </div>
    </div>

    <!-- Order Status Breakdown -->
    <div class="dashboard-section">
        <h2><?php echo icon_package(24); ?> <?php echo __('dashboard.order_status_breakdown'); ?></h2>
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>⏳ <?php echo __('order.pending'); ?></h3>
                <div class="number"><?php echo $pending_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3><?php echo icon_check_circle(20, '#10b981'); ?> <?php echo __('order.confirmed'); ?></h3>
                <div class="number"><?php echo $confirmed_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>⚙️ <?php echo __('order.processing'); ?></h3>
                <div class="number"><?php echo $processing_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>🚚 <?php echo __('order.shipped'); ?></h3>
                <div class="number"><?php echo $shipped_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>✔️ <?php echo __('order.delivered'); ?></h3>
                <div class="number"><?php echo $delivered_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3><?php echo icon_x_circle(20, '#ef4444'); ?> <?php echo __('order.cancelled'); ?></h3>
                <div class="number"><?php echo $cancelled_orders; ?></div>
            </div>
        </div>
    </div>

    <!-- Traffic Sources -->
    <div class="dashboard-section">
        <h2>🌐 <?php echo __('dashboard.traffic_sources'); ?></h2>
        <div class="sources-grid">
            <div class="source-item">
                <div class="source-name">🔗 <?php echo __('dashboard.direct'); ?></div>
                <div class="source-value"><?php echo number_format($traffic_sources['direct']); ?></div>
            </div>
            <div class="source-item">
                <div class="source-name">🔍 <?php echo __('dashboard.search'); ?></div>
                <div class="source-value"><?php echo number_format($traffic_sources['search']); ?></div>
            </div>
            <div class="source-item">
                <div class="source-name"><?php echo icon_package(16); ?> <?php echo __('dashboard.social'); ?></div>
                <div class="source-value"><?php echo number_format($traffic_sources['social']); ?></div>
            </div>
            <div class="source-item">
                <div class="source-name">🔗 <?php echo __('dashboard.referral'); ?></div>
                <div class="source-value"><?php echo number_format($traffic_sources['referral']); ?></div>
            </div>
            <div class="source-item">
                <div class="source-name"><?php echo icon_mail(16); ?> <?php echo __('dashboard.email'); ?></div>
                <div class="source-value"><?php echo number_format($traffic_sources['email']); ?></div>
            </div>
        </div>
    </div>

    <!-- Courier Statistics -->
    <div class="dashboard-section">
        <h2>🚚 <?php echo __('dashboard.courier_statistics'); ?></h2>
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>📦 <?php echo __('dashboard.econt_shipments'); ?></h3>
                <div class="number"><?php echo $econt_shipments; ?></div>
                <div class="subtitle"><?php echo __('courier.econt_description'); ?></div>
            </div>
            <div class="stat-card">
                <h3>⚡ <?php echo __('dashboard.speedy_shipments'); ?></h3>
                <div class="number"><?php echo $speedy_shipments; ?></div>
                <div class="subtitle"><?php echo __('courier.speedy_description'); ?></div>
            </div>
            <div class="stat-card">
                <h3>💵 <?php echo __('dashboard.cod_orders'); ?></h3>
                <div class="number"><?php echo $cod_orders; ?></div>
                <div class="subtitle"><?php echo __('payment.cod_short'); ?> <?php echo __('orders'); ?></div>
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <?php if (!empty($top_products)): ?>
    <div class="dashboard-section">
        <h2>🏆 <?php echo __('dashboard.top_products'); ?></h2>
        <ul class="top-products-list">
            <?php foreach ($top_products as $index => $product): ?>
                <li>
                    <span class="product-name"><?php echo ($index + 1); ?>. <?php echo htmlspecialchars($product['name']); ?></span>
                    <span class="product-stats">
                        <?php echo $product['quantity']; ?> <?php echo __('dashboard.sold'); ?> • $<?php echo number_format($product['revenue'], 2); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Recent Orders -->
    <?php if (!empty($recent_orders)): ?>
    <div class="dashboard-section">
        <h2><?php echo icon_package(24); ?> <?php echo __('dashboard.recent_orders'); ?></h2>
        <table class="recent-orders-table">
            <thead>
                <tr>
                    <th><?php echo __('order.id'); ?></th>
                    <th><?php echo __('order.customer'); ?></th>
                    <th><?php echo __('order.total'); ?></th>
                    <th><?php echo __('order.status'); ?></th>
                    <th><?php echo __('order.courier'); ?></th>
                    <th><?php echo __('order.date'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['customer']['name'] ?? $order['customer'] ?? 'Guest'); ?></td>
                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <?php 
                            $status = $order['status'] ?? 'pending';
                            echo '<span class="status-badge badge-' . $status . '">' . ucfirst($status) . '</span>';
                            ?>
                        </td>
                        <td><?php echo strtoupper($order['shipping']['courier'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['created'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>⚡ <?php echo __('dashboard.quick_actions'); ?></h2>
        <div class="quick-actions">
            <a href="?section=orders" class="quick-action-btn">📦 <?php echo __('admin.view_all_orders'); ?></a>
            <a href="?section=products&action=new" class="quick-action-btn">➕ <?php echo __('admin.add_new_product'); ?></a>
            <a href="?section=users" class="quick-action-btn">👥 <?php echo __('admin.manage_customers'); ?></a>
            <a href="?section=settings" class="quick-action-btn">⚙️ <?php echo __('settings.title'); ?></a>
        </div>
    </div>
</div>
