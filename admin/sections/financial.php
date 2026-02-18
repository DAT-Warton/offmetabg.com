<?php
/**
 * Financial Data Management Section
 * Manage expenses, costs, and tax information
 */

// Get currency settings from database
$currency_settings = get_currency_settings();
$currency_symbol = $currency_settings['symbol'];

// Get financial data
$financial_data = [];
try {
    $rows = db_table('financial_data')->orderBy('period_start', 'DESC')->all();
    foreach ($rows as $row) {
        $financial_data[] = [
            'id' => $row['id'],
            'period_start' => $row['period_start'],
            'period_end' => $row['period_end'],
            'total_expenses' => $row['total_expenses'] ?? 0,
            'hosting_costs' => $row['hosting_costs'] ?? 0,
            'marketing_costs' => $row['marketing_costs'] ?? 0,
            'courier_costs' => $row['courier_costs'] ?? 0,
            'other_costs' => $row['other_costs'] ?? 0,
            'tax_rate' => $row['tax_rate'] ?? 20,
            'notes' => $row['notes'] ?? ''
        ];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$editId = $_GET['edit'] ?? null;
$editEntry = null;
if ($editId) {
    foreach ($financial_data as $entry) {
        if ($entry['id'] == $editId) {
            $editEntry = $entry;
            break;
        }
    }
}
?>

<div class="section-header">
    <h2>💰 Financial Data Management</h2>
    <?php if ($action !== 'new' && $action !== 'edit'): ?>
        <a href="?section=financial&action=new"class="btn">+ Add Financial Period</a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="message message-error">
        ❌ Error: <?php echo htmlspecialchars($error); ?><br>
        <small>Make sure the financial_data table exists. Go to Database section and save configuration to create tables.</small>
    </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <h3><?php echo $action === 'edit' ? 'Edit Financial Period' : 'Add Financial Period'; ?></h3>
        <form method="POST">
            <input type="hidden"name="action"value="save_financial">
            <input type="hidden"name="entry_id"value="<?php echo htmlspecialchars($editEntry['id'] ?? ''); ?>">
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Period Start Date</label>
                    <input type="date"name="period_start"value="<?php echo htmlspecialchars($editEntry['period_start'] ?? date('Y-m-01')); ?>"required>
                </div>
                
                <div class="form-group">
                    <label>Period End Date</label>
                    <input type="date"name="period_end"value="<?php echo htmlspecialchars($editEntry['period_end'] ?? date('Y-m-t')); ?>"required>
                </div>
            </div>
            
            <h4 class="mt-20 mb-10">Expenses Breakdown</h4>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Hosting Costs (<?php echo $currency_symbol; ?>)</label>
                    <input type="number"step="0.01"name="hosting_costs"value="<?php echo htmlspecialchars($editEntry['hosting_costs'] ?? 0); ?>"min="0">
                    <small class="hint">Server, domain, CDN costs</small>
                </div>
                
                <div class="form-group">
                    <label>Marketing Costs (<?php echo $currency_symbol; ?>)</label>
                    <input type="number"step="0.01"name="marketing_costs"value="<?php echo htmlspecialchars($editEntry['marketing_costs'] ?? 0); ?>"min="0">
                    <small class="hint">Ads, promotions, SEO</small>
                </div>
                
                <div class="form-group">
                    <label>Courier Costs (<?php echo $currency_symbol; ?>)</label>
                    <input type="number"step="0.01"name="courier_costs"value="<?php echo htmlspecialchars($editEntry['courier_costs'] ?? 0); ?>"min="0">
                    <small class="hint">Shipping, delivery fees</small>
                </div>
                
                <div class="form-group">
                    <label>Other Costs (<?php echo $currency_symbol; ?>)</label>
                    <input type="number"step="0.01"name="other_costs"value="<?php echo htmlspecialchars($editEntry['other_costs'] ?? 0); ?>"min="0">
                    <small class="hint">Miscellaneous expenses</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Total Expenses (<?php echo $currency_symbol; ?>)</label>
                <input type="number"step="0.01"name="total_expenses"value="<?php echo htmlspecialchars($editEntry['total_expenses'] ?? 0); ?>"min="0"required>
                <small class="hint">Sum of all expenses (or custom amount)</small>
            </div>
            
            <div class="form-group">
                <label>Tax Rate (%)</label>
                <input type="number"step="0.01"name="tax_rate"value="<?php echo htmlspecialchars($editEntry['tax_rate'] ?? 20); ?>"min="0"max="100">
                <small class="hint">Default: 20% VAT for Bulgaria</small>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes"rows="3"><?php echo htmlspecialchars($editEntry['notes'] ?? ''); ?></textarea>
                <small class="hint">Optional notes about this period</small>
            </div>
            
            <div class="form-actions">
                <button type="submit"class="btn">💾 Save Financial Data</button>
                <a href="?section=financial"class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h3>📊 Financial History</h3>
        
        <?php if (empty($financial_data)): ?>
            <p class="text-muted">No financial data yet. Add your first entry to start tracking expenses.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Total Expenses</th>
                        <th>Hosting</th>
                        <th>Marketing</th>
                        <th>Courier</th>
                        <th>Other</th>
                        <th>Tax Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($financial_data as $entry): ?>
                        <tr>
                            <td>
                                <?php echo date('M d, Y', strtotime($entry['period_start'])); ?> - 
                                <?php echo date('M d, Y', strtotime($entry['period_end'])); ?>
                            </td>
                            <td><strong><?php echo $currency_symbol; ?><?php echo number_format($entry['total_expenses'], 2); ?></strong></td>
                            <td><?php echo $currency_symbol; ?><?php echo number_format($entry['hosting_costs'], 2); ?></td>
                            <td><?php echo $currency_symbol; ?><?php echo number_format($entry['marketing_costs'], 2); ?></td>
                            <td><?php echo $currency_symbol; ?><?php echo number_format($entry['courier_costs'], 2); ?></td>
                            <td><?php echo $currency_symbol; ?><?php echo number_format($entry['other_costs'], 2); ?></td>
                            <td><?php echo number_format($entry['tax_rate'], 1); ?>%</td>
                            <td>
                                <a href="?section=financial&action=edit&edit=<?php echo $entry['id']; ?>"class="btn-sm">✏️ Edit</a>
                                <form method="POST"style="display:inline;"onsubmit="return confirm('Delete this entry?');">
                                    <input type="hidden"name="action"value="delete_financial">
                                    <input type="hidden"name="entry_id"value="<?php echo $entry['id']; ?>">
                                    <button type="submit"class="btn-sm btn-delete">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
