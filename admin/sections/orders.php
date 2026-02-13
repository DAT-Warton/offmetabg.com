<?php
/**
 * Orders Management Section - Detailed Order System
 */
$orders = get_orders_data();
$viewOrder = $_GET['view'] ?? null;
$orderDetails = $viewOrder ? ($orders[$viewOrder] ?? null) : null;

// Order stages/statuses
$orderStatuses = [
    'pending' => ['label' => __('order.pending'), 'color' => '#fbbf24', 'icon' => '⏳'],
    'confirmed' => ['label' => __('order.confirmed'), 'color' => '#3b82f6', 'icon' => '✓'],
    'processing' => ['label' => __('order.processing'), 'color' => '#8b5cf6', 'icon' => '⚙️'],
    'shipped' => ['label' => __('order.shipped'), 'color' => '#06b6d4', 'icon' => '🚚'],
    'delivered' => ['label' => __('order.delivered'), 'color' => '#27ae60', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'],
    'cancelled' => ['label' => __('order.cancelled'), 'color' => '#ef4444', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'],
];

// Statistics
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? 'pending') === 'pending'));
$processingOrders = count(array_filter($orders, fn($o) => in_array($o['status'] ?? '', ['confirmed', 'processing', 'shipped'])));
$completedOrders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'delivered'));
$totalRevenue = array_sum(array_column($orders, 'total'));
?>

<div>
    <div class="section-header">
        <h2 class="section-title">📦 <?php echo __('admin.order_management'); ?></h2>
        <?php if ($orderDetails): ?>
            <a href="?section=orders" class="btn-muted">← <?php echo __('admin.back_to_orders'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($orderDetails): ?>
        <!-- Order Details View -->
        <div class="card card-lg">
            <div class="flex-between-start mb-30">
                <div>
                    <h3 class="mb-10 section-title">Поръчка #<?php echo htmlspecialchars($orderDetails['id']); ?></h3>
                    <?php
                        $orderCustomerName = $orderDetails['customer']['name'] ?? ($orderDetails['customer_name'] ?? $orderDetails['customer'] ?? __('admin.guest'));
                    ?>
                    <p class="text-muted mb-5"><?php echo __('order.customer'); ?>: <strong><?php echo htmlspecialchars($orderCustomerName); ?></strong></p>
                    <p class="text-muted mb-5"><?php echo __('auth.email'); ?>: <strong><?php echo htmlspecialchars($orderDetails['email'] ?? __('admin.n_a')); ?></strong></p>
                    <p class="text-muted mb-5"><?php echo __('order.date'); ?>: <strong><?php echo date('F d, Y H:i', strtotime($orderDetails['created'])); ?></strong></p>
                </div>
                <div>
                    <?php $status = $orderDetails['status'] ?? 'pending'; ?>
                    <span class="status-pill status-pill-lg status-<?php echo htmlspecialchars($status); ?>">
                        <?php echo $orderStatuses[$status]['icon']; ?> <?php echo $orderStatuses[$status]['label']; ?>
                    </span>
                </div>
            </div>

            <!-- Update Status Form -->
            <form method="POST" class="card-muted mb-30">
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderDetails['id']); ?>">
                <div class="flex-end">
                    <div class="flex-1">
                        <label><?php echo __('admin.update_order_status'); ?>:</label>
                        <select name="status" class="select-plain select-block">
                            <?php foreach ($orderStatuses as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['icon']; ?> <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn"><?php echo __('admin.update_status'); ?></button>
                </div>
            </form>

            <!-- Order Items -->
            <h4 class="mb-15 section-title"><?php echo __('admin.order_items'); ?>:</h4>
            <table class="mb-20">
                <thead>
                    <tr>
                        <th><?php echo __('cart_page.item'); ?></th>
                        <th class="text-center"><?php echo __('cart_page.quantity'); ?></th>
                        <th class="text-right"><?php echo __('product.price'); ?></th>
                        <th class="text-right"><?php echo __('cart_page.subtotal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderDetails['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-right">$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-right font-semibold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="text-right font-semibold text-lg"><?php echo __('cart_page.total'); ?>:</td>
                        <td class="text-right font-bold text-lg text-primary">$<?php echo number_format($orderDetails['total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Shipping Information -->
            <?php if (!empty($orderDetails['shipping'])): ?>
                <h4 class="mt-30 mb-15 section-title"><?php echo __('admin.shipping_information'); ?>:</h4>
                <div class="card-muted">
                    <p class="mb-5"><strong><?php echo __('checkout.address'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['address'] ?? __('admin.n_a')); ?></p>
                    <p class="mb-5"><strong><?php echo __('checkout.city'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['city'] ?? __('admin.n_a')); ?></p>
                    <p class="mb-5"><strong><?php echo __('checkout.postal_code'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['postal_code'] ?? __('admin.n_a')); ?></p>
                    <p class="mb-5"><strong><?php echo __('checkout.phone'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['phone'] ?? __('admin.n_a')); ?></p>
                </div>
            <?php endif; ?>

            <!-- Delete Order -->
            <form method="POST" class="mt-30 pt-20 border-top-2">
                <input type="hidden" name="action" value="delete_order">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderDetails['id']); ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Сигурни ли сте, че искате да изтриете тази поръчка? Действието не може да бъде отменено.');"><?php echo __('order.delete_order'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <!-- Orders Statistics -->
        <div class="grid grid-auto-200 mb-30">
            <div class="card">
                <h4 class="text-sm text-muted mb-10"><?php echo __('order.total_orders'); ?></h4>
                <p class="stat-number text-primary"><?php echo $totalOrders; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10">⏳ <?php echo __('order.pending'); ?></h4>
                <p class="stat-number text-warning"><?php echo $pendingOrders; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10">⚙️ <?php echo __('order.processing'); ?></h4>
                <p class="stat-number text-purple"><?php echo $processingOrders; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10"><?php echo icon_check_circle(16, '#27ae60'); ?> <?php echo __('order.completed'); ?></h4>
                <p class="stat-number text-success"><?php echo $completedOrders; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10">💰 <?php echo __('order.revenue'); ?></h4>
                <p class="stat-number text-success">$<?php echo number_format($totalRevenue, 2); ?></p>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="card text-center">
                <h3 class="text-muted mb-10"><?php echo __('order.no_orders'); ?></h3>
                <p class="text-muted">Поръчките ще се появят тук, когато клиентите направят покупки</p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>ID поръчка</th>
                            <th><?php echo __('order.customer'); ?></th>
                            <th class="text-center"><?php echo __('order.items'); ?></th>
                            <th class="text-right"><?php echo __('cart_page.total'); ?></th>
                            <th class="text-center"><?php echo __('order.status'); ?></th>
                            <th><?php echo __('order.date'); ?></th>
                            <th class="text-center"><?php echo __('users.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($orders, true) as $id => $order): ?>
                            <?php $status = $order['status'] ?? 'pending'; ?>
                            <tr class="table-row-hover">
                                <td class="font-mono">#<?php echo htmlspecialchars($order['id']); ?></td>
                                <?php
                                    $listCustomerName = $order['customer_name'] ?? ($order['customer']['name'] ?? $order['customer'] ?? __('admin.guest'));
                                ?>
                                <td><?php echo htmlspecialchars($listCustomerName); ?></td>
                                <td class="text-center"><?php echo count($order['items']); ?></td>
                                <td class="text-right font-semibold">$<?php echo number_format($order['total'], 2); ?></td>
                                <td class="text-center">
                                    <span class="status-pill status-pill-sm status-<?php echo htmlspecialchars($status); ?>">
                                        <?php echo $orderStatuses[$status]['icon']; ?> <?php echo $orderStatuses[$status]['label']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created'])); ?></td>
                                <td class="text-center">
                                    <a href="?section=orders&view=<?php echo $id; ?>" class="btn-small"><?php echo __('order.view_details'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<div id="orderModal" class="modal-backdrop">
    <div class="modal-card">
        <h3 class="mb-20">Order Items</h3>
        <div id="orderItems"></div>
        <button onclick="closeOrderModal()" class="btn mt-20">Close</button>
    </div>
</div>

<script src="assets/js/orders.js"></script>

