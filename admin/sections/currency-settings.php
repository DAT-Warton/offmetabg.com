<?php
/**
 * Admin Section: Currency Exchange Settings
 * View and refresh EUR/BGN exchange rates
 */

if (!isset($_SESSION['admin_user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/currency-exchange.php';

// Get exchange rate info
$rate_info = get_exchange_rate_info();
$current_rate = $rate_info['rate'];
$last_update = $rate_info['last_update'];
$source = $rate_info['source'];
$age_hours = $rate_info['age_hours'];

// Calculate next update time
$next_update = $last_update + 86400; // 24 hours
$time_until_update = $next_update - time();
$hours_until_update = max(0, round($time_until_update / 3600, 1));
?>

<style>
.currency-dashboard {
    max-width: 1000px;
    margin: 0 auto;
}

.rate-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rate-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    transition: transform 0.2s;
}

.rate-card:hover {
    transform: translateY(-4px);
}

.rate-card-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.rate-card-value {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 4px;
}

.rate-card-subtext {
    font-size: 13px;
    opacity: 0.8;
}

.conversion-examples {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.conversion-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.conversion-item {
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.conversion-from {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.conversion-arrow {
    font-size: 14px;
    color: #666;
    margin: 8px 0;
}

.conversion-to {
    font-size: 16px;
    color: #667eea;
    font-weight: 600;
}

.rate-controls {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-refresh-rate {
    background: #10b981;
    color: white;
    border: none;
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-refresh-rate:hover {
    background: #059669;
    transform: translateY(-2px);
}

.btn-refresh-rate:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 12px;
}

.status-fresh {
    background: #d1fae5;
    color: #065f46;
}

.status-stale {
    background: #fed7d7;
    color: #742a2a;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin: 20px 0;
}

.info-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.info-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.info-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}
</style>

<div class="currency-dashboard">
    <h2 style="margin-bottom: 24px;">💱 Управление на валутни курсове</h2>
    
    <!-- Exchange Rate Cards -->
    <div class="rate-cards">
        <div class="rate-card">
            <div class="rate-card-label">Текущ курс EUR/BGN</div>
            <div class="rate-card-value"><?php echo number_format($current_rate, 5); ?></div>
            <div class="rate-card-subtext">
                1 € = <?php echo number_format($current_rate, 2); ?> лв.
                <?php if ($age_hours < 24): ?>
                    <span class="status-badge status-fresh">✓ Актуален</span>
                <?php else: ?>
                    <span class="status-badge status-stale">⚠️ Остарял</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="rate-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="rate-card-label">Последна актуализация</div>
            <div class="rate-card-value" style="font-size: 24px;">
                <?php 
                if ($last_update > 0) {
                    echo date('d.m.Y H:i', $last_update);
                } else {
                    echo 'Никога';
                }
                ?>
            </div>
            <div class="rate-card-subtext">
                <?php if ($last_update > 0): ?>
                    Преди <?php echo $age_hours; ?> часа
                <?php else: ?>
                    Курсът не е обновяван
                <?php endif; ?>
            </div>
        </div>
        
        <div class="rate-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="rate-card-label">Следваща актуализация</div>
            <div class="rate-card-value" style="font-size: 24px;">
                <?php echo $hours_until_update; ?> ч.
            </div>
            <div class="rate-card-subtext">
                Автоматична актуализация на всеки 24 часа
            </div>
        </div>
    </div>
    
    <!-- Conversion Examples -->
    <div class="conversion-examples">
        <h3 style="margin-bottom: 16px;">🔄 Примери за конверсия</h3>
        <div class="conversion-grid">
            <?php
            $examples_eur = [10, 25, 50, 100];
            foreach ($examples_eur as $eur) {
                $bgn = convert_eur_to_bgn($eur);
                ?>
                <div class="conversion-item">
                    <div class="conversion-from"><?php echo $eur; ?> €</div>
                    <div class="conversion-arrow">↓</div>
                    <div class="conversion-to"><?php echo number_format($bgn, 2); ?> лв.</div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <!-- Rate Information -->
    <div class="conversion-examples">
        <h3 style="margin-bottom: 16px;">ℹ️ Информация за курса</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Източник</div>
                <div class="info-value"><?php echo htmlspecialchars($source); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Тип курс</div>
                <div class="info-value">Фиксиран (Валутен борд)</div>
            </div>
            <div class="info-item">
                <div class="info-label">Кеш период</div>
                <div class="info-value">24 часа</div>
            </div>
            <div class="info-item">
                <div class="info-label">Актуализация</div>
                <div class="info-value">Автоматична</div>
            </div>
        </div>
        
        <div style="margin-top: 16px; padding: 12px; background: #e6f4ff; border-left: 4px solid #1890ff; border-radius: 4px;">
            <strong>ℹ️ Забележка:</strong> България използва валутен борд от 1997 г. с фиксиран курс 
            <strong>1 EUR = 1.95583 BGN</strong>. Курсът се актуализира от БНБ, но промените са минимални.
        </div>
    </div>
    
    <!-- Controls -->
    <div class="rate-controls">
        <h3 style="margin-bottom: 16px;">⚙️ Управление</h3>
        
        <form method="POST" action="dashboard.php?section=currency-settings" style="margin-bottom: 16px;">
            <input type="hidden" name="action" value="refresh_exchange_rate">
            <button type="submit" class="btn-refresh-rate">
                🔄 Актуализирай курса сега
            </button>
            <p style="color: #666; font-size: 13px; margin-top: 8px;">
                Принудително обновяване на курса от БНБ API
            </p>
        </form>
        
        <div style="border-top: 1px solid #e0e0e0; padding-top: 16px; margin-top: 16px;">
            <h4 style="margin-bottom: 8px;">🎯 Как работи системата:</h4>
            <ul style="color: #666; font-size: 14px; line-height: 1.8;">
                <li>✅ Всички цени в базата се съхраняват в <strong>EUR</strong></li>
                <li>✅ На сайта се показват двойни цени: <strong>EUR и BGN</strong></li>
                <li>✅ Конверсията се прави автоматично с актуален курс</li>
                <li>✅ Курсът се кешира за 24 часа (намалява API заявки)</li>
                <li>✅ При липса на връзка се използва официалният фиксиран курс</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Show loading state when refreshing
document.querySelector('form').addEventListener('submit', function() {
    const btn = this.querySelector('.btn-refresh-rate');
    btn.disabled = true;
    btn.innerHTML = '⏳ Актуализиране...';
});
</script>
