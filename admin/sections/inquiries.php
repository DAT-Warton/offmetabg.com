<?php
/**
 * Inquiries Management Section - Admin
 */
$inquiries = get_inquiries_data();
$viewInquiry = $_GET['view'] ?? null;
$inquiryDetails = $viewInquiry ? ($inquiries[$viewInquiry] ?? null) : null;

$categories = [
    'general' => __('inquiry.category_general'),
    'order' => __('inquiry.category_order'),
    'product' => __('inquiry.category_product'),
    'payment' => __('inquiry.category_payment'),
    'delivery' => __('inquiry.category_delivery'),
    'technical' => __('inquiry.category_technical'),
    'complaint' => __('inquiry.category_complaint'),
    'other' => __('inquiry.category_other')
];

$status_labels = [
    'pending' => ['label' => __('inquiry.pending'), 'color' => '#fbbf24'],
    'in_progress' => ['label' => __('inquiry.in_progress'), 'color' => '#3b82f6'],
    'resolved' => ['label' => __('inquiry.resolved'), 'color' => '#27ae60'],
    'closed' => ['label' => __('inquiry.closed'), 'color' => '#6b7280']
];

// Statistics
$totalInquiries = count($inquiries);
$pendingInquiries = count(array_filter($inquiries, fn($i) => ($i['status'] ?? 'pending') === 'pending'));
$inProgressInquiries = count(array_filter($inquiries, fn($i) => ($i['status'] ?? '') === 'in_progress'));
$resolvedInquiries = count(array_filter($inquiries, fn($i) => ($i['status'] ?? '') === 'resolved'));
?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><?php echo icon_mail(24); ?> <?php echo __('inquiry.admin_title'); ?></h2>
        <?php if ($inquiryDetails): ?>
            <a href="?section=inquiries" style="padding: 10px 20px; background: var(--bg-tertiary, #6b7280); color: white; text-decoration: none; border-radius: 6px;">← <?php echo __('inquiry.back_to_list'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($inquiryDetails): ?>
        <!-- Inquiry Details View -->
        <div style="background: var(--bg-secondary, white); padding: 25px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px;">
                <div>
                    <h3 style="margin-bottom: 10px; color: var(--text-primary, #1f2937);"><?php echo htmlspecialchars($inquiryDetails['subject']); ?></h3>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('inquiry.from'); ?>: <strong><?php echo htmlspecialchars($inquiryDetails['username']); ?></strong></p>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('inquiry.category'); ?>: <strong><?php echo htmlspecialchars($categories[$inquiryDetails['category']] ?? __('inquiry.category_other')); ?></strong></p>
                    <p style="color: var(--text-secondary, #666); margin: 5px 0;"><?php echo __('inquiry.date'); ?>: <strong><?php echo date('d.m.Y H:i', strtotime($inquiryDetails['created'])); ?></strong></p>
                </div>
                <div>
                    <?php $status = $inquiryDetails['status'] ?? 'pending'; ?>
                    <span style="padding: 8px 20px; background: <?php echo $status_labels[$status]['color']; ?>; color: white; border-radius: 20px; font-size: 14px; font-weight: 600;">
                        <?php echo $status_labels[$status]['label']; ?>
                    </span>
                </div>
            </div>

            <!-- Update Status Form -->
            <form method="POST" style="background: var(--bg-primary, #f9fafb); padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid var(--border-color, #e5e7eb);">
                <input type="hidden" name="action" value="update_inquiry_status">
                <input type="hidden" name="inquiry_id" value="<?php echo htmlspecialchars($inquiryDetails['id']); ?>">
                <div style="display: flex; gap: 15px; align-items: end;">
                    <div style="flex: 1;">
                        <label for="status" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo __('inquiry.update_status'); ?>:</label>
                        <select name="status" id="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <?php foreach ($status_labels as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="padding: 10px 30px; background: var(--primary, #3498db); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;"><?php echo __('inquiry.update'); ?></button>
                </div>
            </form>

            <!-- Message -->
            <h4 style="margin-bottom: 15px; color: var(--text-primary, #1f2937);"><?php echo __('inquiry.message'); ?>:</h4>
            <div style="background: var(--bg-primary, #f9fafb); padding: 20px; border-radius: 8px; line-height: 1.6; border: 1px solid var(--border-color, #e5e7eb);">
                <?php echo nl2br(htmlspecialchars($inquiryDetails['message'])); ?>
            </div>

            <!-- Delete Inquiry -->
            <form method="POST" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                <input type="hidden" name="action" value="delete_inquiry">
                <input type="hidden" name="inquiry_id" value="<?php echo htmlspecialchars($inquiryDetails['id']); ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('<?php echo __('inquiry.delete_inquiry'); ?>?');" style="padding: 10px 20px; background: var(--danger, #ef4444); color: white; border: none; border-radius: 6px; cursor: pointer;"><?php echo __('inquiry.delete_inquiry'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <!-- Inquiries Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;"><?php echo __('inquiry.total_inquiries'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #3498db;"><?php echo $totalInquiries; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;">⌛ <?php echo __('inquiry.pending'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #fbbf24;"><?php echo $pendingInquiries; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;">⚙️ <?php echo __('inquiry.in_progress'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #3b82f6;"><?php echo $inProgressInquiries; ?></p>
            </div>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <h4 style="color: var(--text-secondary, #666); font-size: 14px; margin-bottom: 10px;"><?php echo icon_check_circle(16, '#27ae60'); ?> <?php echo __('inquiry.resolved'); ?></h4>
                <p style="font-size: 32px; font-weight: bold; color: #27ae60;"><?php echo $resolvedInquiries; ?></p>
            </div>
        </div>

        <!-- Inquiries List -->
        <?php if (empty($inquiries)): ?>
            <div style="text-align: center; padding: 60px; background: var(--bg-secondary, white); border-radius: 12px;">
                <h3 style="color: var(--text-secondary, #6b7280); margin-bottom: 10px;"><?php echo __('inquiry.no_inquiries'); ?></h3>
                <p style="color: #9ca3af;"><?php echo __('inquiry.no_inquiries_desc'); ?></p>
            </div>
        <?php else: ?>
            <div style="background: var(--bg-secondary, white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px var(--shadow, rgba(0,0,0,0.1));">
                <table style="width: 100%;">
                    <thead>
                        <tr style="background: var(--bg-primary, #f9fafb);">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid var(--border-color, #e5e7eb); color: var(--text-primary, #1f2937);"><?php echo __('inquiry.subject'); ?></th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.from'); ?></th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.subject'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.category'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.status'); ?></th>
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.date'); ?></th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;"><?php echo __('inquiry.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($inquiries, true) as $id => $inquiry): ?>
                            <?php $status = $inquiry['status'] ?? 'pending'; ?>
                            <tr>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; font-family: monospace;">#<?php echo htmlspecialchars(substr($inquiry['id'], -6)); ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?php echo htmlspecialchars($inquiry['username']); ?></td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?php echo htmlspecialchars($inquiry['subject']); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;"><?php echo htmlspecialchars($categories[$inquiry['category']] ?? __('inquiry.category_other')); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                    <span style="padding: 4px 12px; background: <?php echo $status_labels[$status]['color']; ?>; color: white; border-radius: 12px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        <?php echo $status_labels[$status]['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; border-bottom: 1px solid #e5e7eb;"><?php echo date('d.m.Y H:i', strtotime($inquiry['created'])); ?></td>
                                <td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                    <a href="?section=inquiries&view=<?php echo $id; ?>" class="btn-small" style="padding: 6px 12px; background: var(--primary, #3498db); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600;"><?php echo __('inquiry.view_details'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

