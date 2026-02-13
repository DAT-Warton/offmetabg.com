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
    <div class="section-header">
        <h2 class="section-title"><?php echo icon_mail(24); ?> <?php echo __('inquiry.admin_title'); ?></h2>
        <?php if ($inquiryDetails): ?>
            <a href="?section=inquiries" class="btn-muted">← <?php echo __('inquiry.back_to_list'); ?></a>
        <?php endif; ?>
    </div>

    <?php if ($inquiryDetails): ?>
        <!-- Inquiry Details View -->
        <div class="card card-lg">
            <div class="flex-between-start mb-30">
                <div>
                    <h3 class="mb-10 section-title"><?php echo htmlspecialchars($inquiryDetails['subject']); ?></h3>
                    <p class="text-muted mb-5"><?php echo __('inquiry.from'); ?>: <strong><?php echo htmlspecialchars($inquiryDetails['username']); ?></strong></p>
                    <p class="text-muted mb-5"><?php echo __('inquiry.category'); ?>: <strong><?php echo htmlspecialchars($categories[$inquiryDetails['category']] ?? __('inquiry.category_other')); ?></strong></p>
                    <p class="text-muted mb-5"><?php echo __('inquiry.date'); ?>: <strong><?php echo date('d.m.Y H:i', strtotime($inquiryDetails['created'])); ?></strong></p>
                </div>
                <div>
                    <?php
                        $status = $inquiryDetails['status'] ?? 'pending';
                        $statusClass = $status === 'in_progress' ? 'in-progress' : $status;
                    ?>
                    <span class="status-pill status-pill-lg status-<?php echo htmlspecialchars($statusClass); ?>">
                        <?php echo $status_labels[$status]['label']; ?>
                    </span>
                </div>
            </div>

            <!-- Update Status Form -->
            <form method="POST" class="card-muted mb-30">
                <input type="hidden" name="action" value="update_inquiry_status">
                <input type="hidden" name="inquiry_id" value="<?php echo htmlspecialchars($inquiryDetails['id']); ?>">
                <div class="flex-end">
                    <div class="flex-1">
                        <label for="status"><?php echo __('inquiry.update_status'); ?>:</label>
                        <select name="status" id="status" class="select-plain select-block">
                            <?php foreach ($status_labels as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn"><?php echo __('inquiry.update'); ?></button>
                </div>
            </form>

            <!-- Message -->
            <h4 class="mb-15 section-title"><?php echo __('inquiry.message'); ?>:</h4>
            <div class="card-muted">
                <?php echo nl2br(htmlspecialchars($inquiryDetails['message'])); ?>
            </div>

            <!-- Delete Inquiry -->
            <form method="POST" class="mt-30 pt-20 border-top-2">
                <input type="hidden" name="action" value="delete_inquiry">
                <input type="hidden" name="inquiry_id" value="<?php echo htmlspecialchars($inquiryDetails['id']); ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('<?php echo __('inquiry.delete_inquiry'); ?>?');"><?php echo __('inquiry.delete_inquiry'); ?></button>
            </form>
        </div>
    <?php else: ?>
        <!-- Inquiries Statistics -->
        <div class="grid grid-auto-200 mb-30">
            <div class="card">
                <h4 class="text-sm text-muted mb-10"><?php echo __('inquiry.total_inquiries'); ?></h4>
                <p class="stat-number text-primary"><?php echo $totalInquiries; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10">⌛ <?php echo __('inquiry.pending'); ?></h4>
                <p class="stat-number text-warning"><?php echo $pendingInquiries; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10">⚙️ <?php echo __('inquiry.in_progress'); ?></h4>
                <p class="stat-number text-blue"><?php echo $inProgressInquiries; ?></p>
            </div>
            <div class="card">
                <h4 class="text-sm text-muted mb-10"><?php echo icon_check_circle(16, '#27ae60'); ?> <?php echo __('inquiry.resolved'); ?></h4>
                <p class="stat-number text-success"><?php echo $resolvedInquiries; ?></p>
            </div>
        </div>

        <!-- Inquiries List -->
        <?php if (empty($inquiries)): ?>
            <div class="card text-center">
                <h3 class="text-muted mb-10"><?php echo __('inquiry.no_inquiries'); ?></h3>
                <p class="text-muted"><?php echo __('inquiry.no_inquiries_desc'); ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('inquiry.subject'); ?></th>
                            <th><?php echo __('inquiry.from'); ?></th>
                            <th><?php echo __('inquiry.subject'); ?></th>
                            <th class="text-center"><?php echo __('inquiry.category'); ?></th>
                            <th class="text-center"><?php echo __('inquiry.status'); ?></th>
                            <th><?php echo __('inquiry.date'); ?></th>
                            <th class="text-center"><?php echo __('inquiry.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($inquiries, true) as $id => $inquiry): ?>
                            <?php
                                $status = $inquiry['status'] ?? 'pending';
                                $statusClass = $status === 'in_progress' ? 'in-progress' : $status;
                            ?>
                            <tr>
                                <td class="font-mono">#<?php echo htmlspecialchars(substr($inquiry['id'], -6)); ?></td>
                                <td><?php echo htmlspecialchars($inquiry['username']); ?></td>
                                <td><?php echo htmlspecialchars($inquiry['subject']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($categories[$inquiry['category']] ?? __('inquiry.category_other')); ?></td>
                                <td class="text-center">
                                    <span class="status-pill status-pill-sm status-<?php echo htmlspecialchars($statusClass); ?>">
                                        <?php echo $status_labels[$status]['label']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($inquiry['created'])); ?></td>
                                <td class="text-center">
                                    <a href="?section=inquiries&view=<?php echo $id; ?>" class="btn-small"><?php echo __('inquiry.view_details'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

