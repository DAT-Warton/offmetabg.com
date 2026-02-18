<?php
/**
 * Analytics Management Section
 * Manage daily web traffic statistics
 */

// Get analytics data
$analytics_data = [];
try {
    $rows = db_table('analytics_daily')->orderBy('date', 'DESC')->limit(90)->all();
    foreach ($rows as $row) {
        $analytics_data[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'total_visits' => $row['total_visits'] ?? 0,
            'unique_visitors' => $row['unique_visitors'] ?? 0,
            'page_views' => $row['page_views'] ?? 0,
            'bounce_rate' => $row['bounce_rate'] ?? 0,
            'traffic_sources' => json_decode($row['traffic_sources'] ?? '{}', true)
        ];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$editId = $_GET['edit'] ?? null;
$editEntry = null;
if ($editId) {
    foreach ($analytics_data as $entry) {
        if ($entry['id'] == $editId) {
            $editEntry = $entry;
            break;
        }
    }
}
?>

<div class="section-header">
    <h2>📊 Web Analytics Management</h2>
    <?php if ($action !== 'new' && $action !== 'edit'): ?>
        <a href="?section=analytics&action=new"class="btn">+ Add Analytics Entry</a>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="message message-error">
        ❌ Error: <?php echo htmlspecialchars($error); ?><br>
        <small>Make sure the analytics_daily table exists. Go to Database section and save configuration to create tables.</small>
    </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
    <div class="card">
        <h3><?php echo $action === 'edit' ? 'Edit Analytics Entry' : 'Add Analytics Entry'; ?></h3>
        <form method="POST">
            <input type="hidden"name="action"value="save_analytics">
            <input type="hidden"name="entry_id"value="<?php echo htmlspecialchars($editEntry['id'] ?? ''); ?>">
            
            <div class="form-group">
                <label>Date</label>
                <input type="date"name="date"value="<?php echo htmlspecialchars($editEntry['date'] ?? date('Y-m-d')); ?>"required>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Total Visits</label>
                    <input type="number"name="total_visits"value="<?php echo htmlspecialchars($editEntry['total_visits'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Unique Visitors</label>
                    <input type="number"name="unique_visitors"value="<?php echo htmlspecialchars($editEntry['unique_visitors'] ?? 0); ?>"min="0">
                </div>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Page Views</label>
                    <input type="number"name="page_views"value="<?php echo htmlspecialchars($editEntry['page_views'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Bounce Rate (%)</label>
                    <input type="number"step="0.01"name="bounce_rate"value="<?php echo htmlspecialchars($editEntry['bounce_rate'] ?? 0); ?>"min="0"max="100">
                </div>
            </div>
            
            <h4 class="mt-20 mb-10">Traffic Sources</h4>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Direct</label>
                    <input type="number"name="source_direct"value="<?php echo htmlspecialchars($editEntry['traffic_sources']['direct'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Search Engines</label>
                    <input type="number"name="source_search"value="<?php echo htmlspecialchars($editEntry['traffic_sources']['search'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Social Media</label>
                    <input type="number"name="source_social"value="<?php echo htmlspecialchars($editEntry['traffic_sources']['social'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Referrals</label>
                    <input type="number"name="source_referral"value="<?php echo htmlspecialchars($editEntry['traffic_sources']['referral'] ?? 0); ?>"min="0">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="number"name="source_email"value="<?php echo htmlspecialchars($editEntry['traffic_sources']['email'] ?? 0); ?>"min="0">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit"class="btn">💾 Save Analytics Entry</button>
                <a href="?section=analytics"class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h3>📈 Analytics History (Last 90 Days)</h3>
        
        <?php if (empty($analytics_data)): ?>
            <p class="text-muted">No analytics data yet. Add your first entry to start tracking.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Visits</th>
                        <th>Unique Visitors</th>
                        <th>Page Views</th>
                        <th>Bounce Rate</th>
                        <th>Top Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics_data as $entry): 
                        $sources = $entry['traffic_sources'];
                        arsort($sources);
                        $top_source = key($sources);
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['date']); ?></td>
                            <td><?php echo number_format($entry['total_visits']); ?></td>
                            <td><?php echo number_format($entry['unique_visitors']); ?></td>
                            <td><?php echo number_format($entry['page_views']); ?></td>
                            <td><?php echo number_format($entry['bounce_rate'], 1); ?>%</td>
                            <td><?php echo ucfirst($top_source); ?> (<?php echo $sources[$top_source]; ?>)</td>
                            <td>
                                <a href="?section=analytics&action=edit&edit=<?php echo $entry['id']; ?>"class="btn-sm">✏️ Edit</a>
                                <form method="POST"style="display:inline;"onsubmit="return confirm('Delete this entry?');">
                                    <input type="hidden"name="action"value="delete_analytics">
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
