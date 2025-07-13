<?php
// admin/components/summary-section.php
?>

<section class="summary-section" id="summary-section">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar"></i> 
                Ringkasan Data & Analisis Tren
            </h3>
            <div class="summary-header-actions">
                <span class="badge badge-info" id="summary-last-updated">Loading...</span>
                <button class="btn btn-sm btn-outline" onclick="refreshSummaryData()" id="refresh-summary-btn">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Loading State -->
            <div id="summary-loading" class="summary-state">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Memuat data analisis...</p>
            </div>
            
            <!-- Error State -->
            <div id="summary-error" class="summary-state" style="display: none;">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <p>Gagal memuat data. Silakan coba lagi.</p>
                <button class="btn btn-primary btn-sm" onclick="loadSummaryData()">Coba Lagi</button>
            </div>
            
            <!-- Content -->
            <div id="summary-content" style="display: none;">
                <div class="performance-indicator excellent" id="overall-performance">
                    <div class="indicator-dot"></div>
                    <span id="performance-text">Memuat status performa...</span>
                </div>
                
                <div class="summary-grid">
                    
                    <!-- Trend Analysis Card -->
                    <div class="summary-card-item">
                        <div class="summary-card-header">
                            <i class="fas fa-trending-up icon-primary"></i>
                            <h4>Analisis Tren</h4>
                        </div>
                        <div class="summary-card-body">
                            <div class="quick-stats">
                                <div class="stat">
                                    <span class="stat-label">Views Minggu Ini</span>
                                    <span class="stat-value" id="current-week-views">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Perubahan</span>
                                    <span class="stat-value" id="views-change">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Transaksi Bulan Ini</span>
                                    <span class="stat-value" id="current-month-transactions">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="summary-card-footer">
                            <a href="wisata_analytics.php" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Performance Highlights Card -->
                    <div class="summary-card-item">
                        <div class="summary-card-header">
                            <i class="fas fa-trophy icon-success"></i>
                            <h4>Performa Terbaik</h4>
                        </div>
                        <div class="summary-card-body">
                            <div class="highlight-item">
                                <span class="stat-label">Destinasi Terpopuler</span>
                                <span class="highlight-value" id="best-destination">-</span>
                            </div>
                            <div class="highlight-item">
                                <span class="stat-label">Penginapan Terpopuler</span>
                                <span class="highlight-value" id="best-accommodation">-</span>
                            </div>
                            <div class="highlight-item">
                                <span class="stat-label">Perlu Perhatian</span>
                                <span class="highlight-value attention" id="needs-attention">-</span>
                            </div>
                        </div>
                        <div class="summary-card-footer">
                            <a href="adminwisata.php" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- User Engagement Card -->
                    <div class="summary-card-item">
                        <div class="summary-card-header">
                            <i class="fas fa-users icon-warning"></i>
                            <h4>Engagement Pengguna</h4>
                        </div>
                        <div class="summary-card-body">
                            <div class="quick-stats">
                                <div class="stat">
                                    <span class="stat-label">User Aktif (7 hari)</span>
                                    <span class="stat-value" id="active-users">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Jam Puncak</span>
                                    <span class="stat-value" id="peak-hour">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Hari Terpopuler</span>
                                    <span class="stat-value" id="popular-day">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="summary-card-footer">
                            <a href="index.php" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Revenue Insights Card -->
                    <div class="summary-card-item">
                        <div class="summary-card-header">
                            <i class="fas fa-money-bill-wave icon-info"></i>
                            <h4>Insight Revenue</h4>
                        </div>
                        <div class="summary-card-body">
                            <div class="quick-stats">
                                <div class="stat">
                                    <span class="stat-label">Revenue Bulan Ini</span>
                                    <span class="stat-value" id="current-month-revenue">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Pertumbuhan</span>
                                    <span class="stat-value" id="revenue-growth">-</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Top Revenue Generator</span>
                                    <span class="stat-value" id="top-revenue-item">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="summary-card-footer">
                            <a href="financial_reports.php" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="detailed-insights-section">
                    <div class="insight-column">
                        <h4 class="insight-title">
                            <i class="fas fa-bell"></i> Indikator Perhatian
                        </h4>
                        <div id="alerts-container" class="insight-list">
                            <!-- Alerts will be populated here -->
                        </div>
                    </div>
                    <div class="insight-column">
                        <h4 class="insight-title">
                            <i class="fas fa-lightbulb"></i> Insight Detail
                        </h4>
                        <ul id="detailed-insights" class="insight-list">
                            <!-- Detailed insights will be populated here -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let summaryData = null;

// Load summary data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryData();
});

async function loadSummaryData() {
    showSummaryLoading();
    
    try {
        const response = await fetch('api/get-summary-data.php');
        const result = await response.json();
        
        if (result.success) {
            summaryData = result.data;
            displaySummaryData(summaryData);
            hideSummaryLoading();
        } else {
            throw new Error(result.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading summary data:', error);
        showSummaryError();
    }
}

function refreshSummaryData() {
    const refreshBtn = document.getElementById('refresh-summary-btn');
    const originalHtml = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refresh';
    refreshBtn.disabled = true;
    
    loadSummaryData().finally(() => {
        refreshBtn.innerHTML = originalHtml;
        refreshBtn.disabled = false;
    });
}

function showSummaryLoading() {
    document.getElementById('summary-loading').style.display = 'flex';
    document.getElementById('summary-error').style.display = 'none';
    document.getElementById('summary-content').style.display = 'none';
}

function showSummaryError() {
    document.getElementById('summary-loading').style.display = 'none';
    document.getElementById('summary-error').style.display = 'flex';
    document.getElementById('summary-content').style.display = 'none';
}

function hideSummaryLoading() {
    document.getElementById('summary-loading').style.display = 'none';
    document.getElementById('summary-error').style.display = 'none';
    document.getElementById('summary-content').style.display = 'block';
}

function displaySummaryData(data) {
    // Update last updated timestamp
    const lastUpdated = new Date(data.generated_at).toLocaleString('id-ID');
    document.getElementById('summary-last-updated').textContent = `Diperbarui: ${lastUpdated}`;
    
    // Overall performance indicator
    const alertCount = data.alert_indicators.length;
    const performanceEl = document.getElementById('overall-performance');
    const performanceText = document.getElementById('performance-text');
    
    if (alertCount === 0) {
        performanceEl.className = 'performance-indicator excellent';
        performanceText.textContent = 'Semua metrik dalam kondisi baik';
    } else if (alertCount <= 2) {
        performanceEl.className = 'performance-indicator good';
        performanceText.textContent = `${alertCount} item membutuhkan perhatian`;
    } else if (alertCount <= 4) {
        performanceEl.className = 'performance-indicator fair';
        performanceText.textContent = `${alertCount} item perlu ditinjau`;
    } else {
        performanceEl.className = 'performance-indicator needs-attention';
        performanceText.textContent = `${alertCount} item membutuhkan tindakan segera`;
    }
    
    // Trend Analysis
    const trends = data.trend_analysis;
    document.getElementById('current-week-views').textContent = new Intl.NumberFormat('id-ID').format(trends.views.current_week.total);
    
    const viewsChangeEl = document.getElementById('views-change');
    const viewsChange = trends.views.change_percentage;
    viewsChangeEl.textContent = `${viewsChange > 0 ? '▲' : '▼'} ${Math.abs(viewsChange)}%`;
    viewsChangeEl.className = `stat-value ${viewsChange > 0 ? 'positive' : viewsChange < 0 ? 'negative' : ''}`;
    
    document.getElementById('current-month-transactions').textContent = new Intl.NumberFormat('id-ID').format(trends.transactions.current_month.count || 0);
    
    // Performance Highlights
    const highlights = data.performance_highlights;
    if (highlights.best_destinations.length > 0) {
        const best = highlights.best_destinations[0];
        document.getElementById('best-destination').textContent = `${best.judul} (${new Intl.NumberFormat('id-ID').format(best.views)} views)`;
    }
    
    if (highlights.best_accommodations.length > 0) {
        const bestAcc = highlights.best_accommodations[0];
        document.getElementById('best-accommodation').textContent = `${bestAcc.judul} (${new Intl.NumberFormat('id-ID').format(bestAcc.views)} views)`;
    }
    
    if (highlights.worst_destinations.length > 0) {
        const worst = highlights.worst_destinations[0];
        document.getElementById('needs-attention').textContent = `${worst.judul} (${worst.views} views)`;
    }
    
    // User Engagement
    const engagement = data.user_engagement;
    document.getElementById('active-users').textContent = new Intl.NumberFormat('id-ID').format(engagement.active_users);
    
    if (engagement.peak_hours.length > 0) {
        document.getElementById('peak-hour').textContent = `${engagement.peak_hours[0].hour}:00`;
    }
    
    if (engagement.booking_patterns.length > 0) {
        document.getElementById('popular-day').textContent = engagement.booking_patterns[0].day_name;
    }
    
    // Revenue Insights
    const revenue = data.revenue_insights;
    document.getElementById('current-month-revenue').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(revenue.current_month_revenue)}`;
    
    const revenueGrowthEl = document.getElementById('revenue-growth');
    const revenueGrowth = revenue.revenue_growth;
    revenueGrowthEl.textContent = `${revenueGrowth > 0 ? '▲' : '▼'} ${Math.abs(revenueGrowth)}%`;
    revenueGrowthEl.className = `stat-value ${revenueGrowth > 0 ? 'positive' : revenueGrowth < 0 ? 'negative' : ''}`;
    
    if (revenue.top_revenue_items.length > 0) {
        const topItem = revenue.top_revenue_items[0];
        document.getElementById('top-revenue-item').textContent = `${topItem.item_type} (Rp ${new Intl.NumberFormat('id-ID').format(topItem.total_revenue)})`;
    }
    
    // Alert Indicators
    displayAlerts(data.alert_indicators);
    
    // Detailed Insights
    displayDetailedInsights(data);
}

function displayAlerts(alerts) {
    const container = document.getElementById('alerts-container');
    
    if (alerts.length === 0) {
        container.innerHTML = '<div class="insight-item empty"><i class="fas fa-check-circle"></i><span>Tidak ada alert saat ini.</span></div>';
        return;
    }
    
    container.innerHTML = alerts.map(alert => {
        const iconClass = alert.type === 'error' ? 'fas fa-times-circle' : 
                         alert.type === 'warning' ? 'fas fa-exclamation-triangle' : 
                         'fas fa-info-circle';
        const colorClass = alert.type === 'error' ? 'negative' : 
                          alert.type === 'warning' ? 'neutral' : 
                          'positive';
        
        return `
            <div class="insight-item ${colorClass}">
                <i class="${iconClass}"></i>
                <span>${alert.message}</span>
            </div>
        `;
    }).join('');
}

function displayDetailedInsights(data) {
    const container = document.getElementById('detailed-insights');
    const insights = [];
    
    // Views insights
    const viewsTrend = data.trend_analysis.views;
    if (viewsTrend.change_percentage > 10) {
        insights.push({
            type: 'positive',
            text: `Views naik ${viewsTrend.change_percentage}% dari minggu lalu.`
        });
    } else if (viewsTrend.change_percentage < -10) {
        insights.push({
            type: 'negative', 
            text: `Views turun ${Math.abs(viewsTrend.change_percentage)}%.`
        });
    }
    
    // Revenue insights
    const revenueGrowth = data.revenue_insights.revenue_growth;
    if (revenueGrowth > 5) {
        insights.push({
            type: 'positive',
            text: `Revenue bertumbuh ${revenueGrowth}%.`
        });
    }
    
    // Engagement insights
    if (data.user_engagement.active_users > 50) {
        insights.push({
            type: 'positive',
            text: `${data.user_engagement.active_users} user aktif minggu ini.`
        });
    }
    
    // Performance insights
    const bestDest = data.performance_highlights.best_destinations[0];
    if (bestDest && bestDest.views > 100) {
        insights.push({
            type: 'neutral',
            text: `${bestDest.judul} berkinerja baik.`
        });
    }
    
    if (insights.length === 0) {
        container.innerHTML = '<div class="insight-item empty"><i class="fas fa-info-circle"></i><span>Belum ada insight detail.</span></div>';
        return;
    }

    container.innerHTML = insights.map(insight => {
        const iconClass = insight.type === 'positive' ? 'fas fa-arrow-up' :
                         insight.type === 'negative' ? 'fas fa-arrow-down' :
                         'fas fa-lightbulb';
        const itemClass = insight.type;
        
        return `
            <li class="insight-item ${itemClass}">
                <i class="${iconClass}"></i>
                <span>${insight.text}</span>
            </li>
        `;
    }).join('');
}
</script>
