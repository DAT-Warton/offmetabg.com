<?php
/**
 * Orders Management Section - Detailed Order System
 */
$orders = load_json('storage/orders.json');
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--text-primary, #1f2937);">📦 <?php echo __('admin.order_management'); ?></h2>
        <?php if ($orderDetails): ?>
            <a href="?section=orders" style="padding: 10px 20px; background: var(--bg-tertiary, #6b7280); color: white; text-decoration: none; border-radius: 6px;">← <?php echo __('admin.back_to_orders'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($orderDetails): ?>
        <!-- Order Details View -->
        <div style="background: var(--bg-secondary, white); padding: 25px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px;">
                <div>
                    <h3 style="margin-bottom: 10px; color: var(--text-primary, #1f2937);">Поръчка #<?php echo htmlspecialchars($orderDetails['id']); ?></h3>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('order.customer'); ?>: <strong><?php echo htmlspecialchars($orderDetails['customer'] ?? __('admin.guest')); ?></strong></p>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('auth.email'); ?>: <strong><?php echo htmlspecialchars($orderDetails['email'] ?? __('admin.n_a')); ?></strong></p>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('order.date'); ?>: <strong><?php echo date('F d, Y H:i', strtotime($orderDetails['created'])); ?></strong></p>
                </div>
                <div>
                    <?php $status = $orderDetails['status'] ?? 'pending'; ?>
                    <span style="padding: 8px 20px; background: <?php echo $orderStatuses[$status]['color']; ?>; color: white; border-radius: 20px; font-size: 14px; font-weight: 600;">
                        <?php echo $orderStatuses[$status]['icon']; ?> <?php echo $orderStatuses[$status]['label']; ?>
                    </span>
                </div>
            </div>

            <!-- Update Status Form -->
            <form method="POST" style="background: var(--bg-primary, #f9fafb); padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--border-color, #e5e7eb);">
                <input type="hidden" name="action" value="update_order_status">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderDetails['id']); ?>">
                <div style="display: flex; gap: 15px; align-items: end;">
                    <div style="flex: 1;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo __('admin.update_order_status'); ?>:</label>
                        <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--bg-secondary, white); color: var(--text-primary, #1f2937);">
                            <?php foreach ($orderStatuses as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['icon']; ?> <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 10px 30px; background: var(--primary, #3498db); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;"><?php echo __('admin.update_status'); ?></button>
                </div>
            </form>

            <!-- Order Items -->
            <h4 style="margin-bottom: 15px; color: var(--text-primary, #1f2937);"><?php echo __('admin.order_items'); ?>:</h4>
            <table style="width: 100%; margin-bottom: 20px;">
                <thead>
                    <tr style="background: var(--bg-primary, #f9fafb);">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('cart_page.item'); ?></th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('cart_page.quantity'); ?></th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('product.price'); ?></th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('cart_page.subtotal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderDetails['items'] as $item): ?>
                        <tr>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="padding: 12px; text-align: center; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo $item['quantity']; ?></td>
                            <td style="padding: 12px; text-align: right; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);">$<?php echo number_format($item['price'], 2); ?></td>
                            <td style="padding: 12px; text-align: right; border-bottom: 1px solid var(--border-color, #e5e7eb); font-weight: 600; color: var(--text-primary, #1f2937);">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" style="padding: 12px; text-align: right; font-weight: 600; font-size: 18px; color: var(--text-primary, #1f2937);"><?php echo __('cart_page.total'); ?>:</td>
                        <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 18px; color: #3498db;">$<?php echo number_format($orderDetails['total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Shipping Information -->
            <?php if (!empty($orderDetails['shipping'])): ?>
                <h4 style="margin-bottom: 15px; margin-top: 30px; color: var(--text-primary, #1f2937);"><?php echo __('admin.shipping_information'); ?>:</h4>
                <div style="background: var(--bg-primary, #f9fafb); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color, #e5e7eb);">
                    <p style="margin: 5px 0; color: var(--text-primary, #1f2937);"><strong><?php echo __('checkout.address'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['address'] ?? __('admin.n_a')); ?></p>
                    <p style="margin: 5px 0; color: var(--text-primary, #1f2937);"><strong><?php echo __('checkout.city'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['city'] ?? __('admin.n_a')); ?></p>
                    <p style="margin: 5px 0; color: var(--text-primary, #1f2937);"><strong><?php echo __('checkout.postal_code'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['postal_code'] ?? __('admin.n_a')); ?></p>
                    <p style="margin: 5px 0; color: var(--text-primary, #1f2937);"><strong><?php echo __('checkout.phone'); ?>:</strong> <?php echo htmlspecialchars($orderDetails['shipping']['phone'] ?? __('admin.n_a')); ?></p>
                </div>
            <?php endif; ?>

            <!-- Delete Order -->
            <form method="POST" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--border-color, #e5e7eb);">
                <input type="hidden" name="action" value="delete_order">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderDetails['id']); ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Сигурни ли сте, че искате да изтриете тази поръчка? Действието не може да бъде отменено.');" style="padding: 10px 20px; background: var(--danger, #ef4444); color: white; border: none; border-radius: 6px; cursor: pointer;"><?php echo __('order.delete_order'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <!-- Orders Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;"><?php echo __('order.total_orders'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #3498db;"><?php echo $totalOrders; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;">⏳ <?php echo __('order.pending'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #fbbf24;"><?php echo $pendingOrders; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;">⚙️ <?php echo __('order.processing'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #8b5cf6;"><?php echo $processingOrders; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;"><?php echo icon_check_circle(16, '#27ae60'); ?> <?php echo __('order.completed'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #27ae60;"><?php echo $completedOrders; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;">💰 <?php echo __('order.revenue'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #27ae60;">$<?php echo number_format($totalRevenue, 2); ?></p>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 60px; background: var(--bg-secondary, white); border-radius: 12px;">
                <h3 style="color: var(--text-secondary, #6b7280); margin-bottom: 10px;"><?php echo __('order.no_orders'); ?></h3>
                <p style="color: var(--text-secondary, #9ca3af);">Поръчките ще се появят тук, когато клиентите направят покупки</p>
            </div>
        <?php else: ?>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <table style="width: 100%;">
                    <thead>
                        <tr style="background: var(--bg-primary, #f9fafb);">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);">ID поръчка</th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('order.customer'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('order.items'); ?></th>
                            <th style="padding: 12px; text-align: right; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('cart_page.total'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('order.status'); ?></th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('order.date'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('users.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($orders, true) as $id => $order): ?>
                            <?php $status = $order['status'] ?? 'pending'; ?>
                            <tr style="transition: background 0.2s;">
                                <td style="padding: 12px; border-bottom: 1px solid var(--border-color, #e5e7eb); font-family: monospace; color: var(--text-primary, #1f2937);">#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo htmlspecialchars($order['customer'] ?? __('admin.guest')); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo count($order['items']); ?></td>
                                <td style="padding: 12px; text-align: right; border-bottom: 1px solid var(--border-color, #e5e7eb); font-weight: 600; color: var(--text-primary, #1f2937);">$<?php echo number_format($order['total'], 2); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid var(--border-color, #e5e7eb);">
                                    <span style="padding: 4px 12px; background: <?php echo $orderStatuses[$status]['color']; ?>; color: white; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        <?php echo $orderStatuses[$status]['icon']; ?> <?php echo $orderStatuses[$status]['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; border-bottom: 1px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo date('M d, Y H:i', strtotime($order['created'])); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid var(--border-color, #e5e7eb);">
                                    <a href="?section=orders&view=<?php echo $id; ?>" class="btn-small" style="padding: 6px 12px; background: var(--primary, #3498db); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?php echo __('order.view_details'); ?></a>
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

<div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-secondary, white); padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; color: var(--text-primary, #1f2937);">
        <h3 style="margin-bottom: 20px; color: var(--text-primary, #1f2937);">Order Items</h3>
        <div id="orderItems"></div>
        <button onclick="closeOrderModal()" style="margin-top: 20px; padding: 10px 20px; background: var(--primary, #3498db); color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
    </div>
</div>

<script src="assets/js/orders.js"></script>

