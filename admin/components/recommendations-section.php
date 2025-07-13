<?php
// admin/components/recommendations-section.php
?>

<section class="recommendations-section" id="recommendations-section">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-lightbulb"></i> 
                Rekomendasi & Insight Strategis
            </h3>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <span class="badge badge-warning" id="recommendations-count">Loading...</span>
                <button class="btn btn-sm btn-outline" onclick="refreshRecommendations()" id="refresh-recommendations-btn">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Loading State -->
            <div id="recommendations-loading" style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--mustard-yellow);"></i>
                <p style="margin-top: 1rem; color: #666;">Menganalisis data untuk rekomendasi...</p>
            </div>
            
            <!-- Error State -->
            <div id="recommendations-error" style="display: none; text-align: center; padding: 2rem;">
                <i class="fas fa-exclamation-triangle fa-2x" style="color: #e53e3e;"></i>
                <p style="margin-top: 1rem; color: #666;">Gagal memuat rekomendasi. Silakan coba lagi.</p>
                <button class="btn btn-primary btn-sm" onclick="loadRecommendations()">Coba Lagi</button>
            </div>
            
            <!-- Content -->
            <div id="recommendations-content" style="display: none;">
                <!-- Priority Filter -->
                <div style="margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <span style="font-weight: 600; color: #4a5568;">Filter Prioritas:</span>
                    <button class="btn btn-sm btn-outline filter-btn active" data-priority="all">
                        Semua (<span id="total-count">0</span>)
                    </button>
                    <button class="btn btn-sm btn-outline filter-btn priority-high" data-priority="high">
                        Tinggi (<span id="high-count">0</span>)
                    </button>
                    <button class="btn btn-sm btn-outline filter-btn priority-medium" data-priority="medium">
                        Sedang (<span id="medium-count">0</span>)
                    </button>
                    <button class="btn btn-sm btn-outline filter-btn priority-low" data-priority="low">
                        Rendah (<span id="low-count">0</span>)
                    </button>
                </div>
                
                <!-- Recommendations Container -->
                <div id="recommendations-container" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Recommendations will be populated here -->
                </div>
                
                <!-- Empty State -->
                <div id="recommendations-empty" style="display: none; text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-check-circle fa-3x" style="color: #38a169; margin-bottom: 1rem;"></i>
                    <h4 style="margin-bottom: 0.5rem;">Semua Rekomendasi Telah Diterapkan!</h4>
                    <p>Tidak ada rekomendasi baru saat ini. Sistem akan menganalisis data secara berkala untuk memberikan insight terbaru.</p>
                </div>
                
                <!-- Quick Actions Panel -->
                <div class="quick-actions-panel" style="margin-top: 2rem;">
                    <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <button class="btn btn-primary btn-sm" onclick="openUMKMManagement()">
                            <i class="fas fa-store"></i> Kelola UMKM
                        </button>
                        <button class="btn btn-success btn-sm" onclick="openWisataAnalytics()">
                            <i class="fas fa-chart-line"></i> Analytics Detail
                        </button>
                        <button class="btn btn-info btn-sm" onclick="openContentManager()">
                            <i class="fas fa-edit"></i> Update Konten
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="openPricingReview()">
                            <i class="fas fa-tags"></i> Review Harga
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
let recommendationsData = null;
let currentFilter = 'all';

// Load recommendations on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecommendations();
    setupRecommendationFilters();
});

async function loadRecommendations() {
    showRecommendationsLoading();
    
    try {
        const response = await fetch('api/get-recommendations.php');
        const result = await response.json();
        
        if (result.success) {
            recommendationsData = result.data;
            displayRecommendations(recommendationsData);
            updateRecommendationCounts(recommendationsData);
            hideRecommendationsLoading();
        } else {
            throw new Error(result.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading recommendations:', error);
        showRecommendationsError();
    }
}

function refreshRecommendations() {
    const refreshBtn = document.getElementById('refresh-recommendations-btn');
    const originalHtml = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refresh';
    refreshBtn.disabled = true;
    
    loadRecommendations().finally(() => {
        refreshBtn.innerHTML = originalHtml;
        refreshBtn.disabled = false;
    });
}

function showRecommendationsLoading() {
    document.getElementById('recommendations-loading').style.display = 'block';
    document.getElementById('recommendations-error').style.display = 'none';
    document.getElementById('recommendations-content').style.display = 'none';
}

function showRecommendationsError() {
    document.getElementById('recommendations-loading').style.display = 'none';
    document.getElementById('recommendations-error').style.display = 'block';
    document.getElementById('recommendations-content').style.display = 'none';
}

function hideRecommendationsLoading() {
    document.getElementById('recommendations-loading').style.display = 'none';
    document.getElementById('recommendations-error').style.display = 'none';
    document.getElementById('recommendations-content').style.display = 'block';
}

function setupRecommendationFilters() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update current filter
            currentFilter = this.dataset.priority;
            
            // Filter recommendations
            if (recommendationsData) {
                displayRecommendations(recommendationsData);
            }
        });
    });
}

function updateRecommendationCounts(data) {
    const recommendations = data.recommendations;
    const totalCount = recommendations.length;
    const highCount = recommendations.filter(r => r.priority === 'high').length;
    const mediumCount = recommendations.filter(r => r.priority === 'medium').length;
    const lowCount = recommendations.filter(r => r.priority === 'low').length;
    
    document.getElementById('total-count').textContent = totalCount;
    document.getElementById('high-count').textContent = highCount;
    document.getElementById('medium-count').textContent = mediumCount;
    document.getElementById('low-count').textContent = lowCount;
    
    // Update main counter
    document.getElementById('recommendations-count').textContent = `${totalCount} rekomendasi`;
}

function displayRecommendations(data) {
    const container = document.getElementById('recommendations-container');
    const emptyState = document.getElementById('recommendations-empty');
    
    let recommendations = data.recommendations;
    
    // Apply filter
    if (currentFilter !== 'all') {
        recommendations = recommendations.filter(r => r.priority === currentFilter);
    }
    
    if (recommendations.length === 0) {
        container.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    container.style.display = 'block';
    emptyState.style.display = 'none';
    
    container.innerHTML = recommendations.map(recommendation => {
        const priorityClass = `priority-${recommendation.priority}`;
        const urgencyBadge = getUrgencyBadge(recommendation.urgency);
        const actionsList = recommendation.actions.map(action => `<li>${action}</li>`).join('');
        
        return `
            <div class="recommendation-card ${priorityClass}">
                <div class="recommendation-header">
                    <div class="priority-indicator ${recommendation.priority}">
                        <i class="fas fa-flag"></i>
                        ${recommendation.priority.toUpperCase()} PRIORITY
                    </div>
                    <h3>${recommendation.title}</h3>
                    ${urgencyBadge}
                </div>
                <div class="recommendation-body">
                    <div class="recommendation-item ${recommendation.type === 'trending' ? 'positive' : recommendation.type === 'declining' ? 'urgent' : ''}">
                        <i class="${recommendation.icon}"></i>
                        <div>
                            <p>${recommendation.description}</p>
                            <div class="action-steps">
                                <span class="action-title">Langkah yang disarankan:</span>
                                <ul>${actionsList}</ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button class="btn btn-sm btn-outline" onclick="dismissRecommendation('${recommendation.type}')">
                        <i class="fas fa-times"></i> Dismiss
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="implementRecommendation('${recommendation.type}')">
                        <i class="fas fa-check"></i> Terapkan
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function getUrgencyBadge(urgency) {
    const urgencyConfig = {
        'immediate': { text: 'Segera', class: 'badge-danger' },
        'today': { text: 'Hari Ini', class: 'badge-warning' },
        'within_week': { text: 'Minggu Ini', class: 'badge-info' },
        'within_month': { text: 'Bulan Ini', class: 'badge-secondary' }
    };
    
    const config = urgencyConfig[urgency] || { text: 'Normal', class: 'badge-secondary' };
    return `<span class="badge ${config.class}">${config.text}</span>`;
}

function implementRecommendation(type) {
    // Show implementation options based on recommendation type
    const actions = {
        'trending': () => {
            showToast('Membuka panel promosi untuk destinasi trending...', 'info');
            // Here you could open a modal or redirect to relevant management page
        },
        'declining': () => {
            showToast('Membuka panel optimasi konten...', 'info');
            // Open content optimization panel
        },
        'seasonal': () => {
            showToast('Membuka kalender promosi musiman...', 'info');
            // Open seasonal promotion calendar
        },
        'resource': () => {
            showToast('Membuka dashboard alokasi sumber daya...', 'info');
            // Open resource allocation dashboard
        },
        'competitive': () => {
            showToast('Membuka analisis kompetitif...', 'info');
            // Open competitive analysis tools
        }
    };
    
    if (actions[type]) {
        actions[type]();
    } else {
        showToast('Fitur implementasi akan segera tersedia', 'info');
    }
}

function dismissRecommendation(type) {
    if (confirm('Yakin ingin mengabaikan rekomendasi ini? Rekomendasi akan muncul kembali jika kondisi masih relevan.')) {
        showToast('Rekomendasi telah diabaikan', 'success');
        // Here you could send a request to mark recommendation as dismissed
        
        // For now, just remove from display
        loadRecommendations();
    }
}

// Quick action functions
function openUMKMManagement() {
    window.location.href = '#umkm-management';
}

function openWisataAnalytics() {
    window.location.href = 'wisata_analytics.php';
}

function openContentManager() {
    showToast('Membuka panel manajemen konten...', 'info');
    // Could open a content management modal or page
}

function openPricingReview() {
    showToast('Membuka panel review harga...', 'info');
    // Could open pricing management tools
}

// Utility function for toast notifications (if not already available)
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} fade-in`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.innerHTML = `
        <span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}
</script>

<style>
.filter-btn.active {
    background-color: var(--mustard-yellow) !important;
    color: white !important;
    border-color: var(--mustard-yellow) !important;
}

.filter-btn.priority-high.active {
    background-color: #e53e3e !important;
    border-color: #e53e3e !important;
}

.filter-btn.priority-medium.active {
    background-color: var(--forest-green) !important;
    border-color: var(--forest-green) !important;
}

.filter-btn.priority-low.active {
    background-color: #4299e1 !important;
    border-color: #4299e1 !important;
}

.recommendation-card {
    transition: all 0.3s ease;
}

.recommendation-card:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .quick-actions-panel > div {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .filter-btn {
        font-size: 0.8rem !important;
        padding: 0.25rem 0.5rem !important;
    }
}
</style>