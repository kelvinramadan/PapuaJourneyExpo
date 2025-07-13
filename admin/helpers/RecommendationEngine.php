<?php
// admin/helpers/RecommendationEngine.php
require_once __DIR__ . '/../../config/database.php';

class RecommendationEngine {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    public function generateRecommendations() {
        $recommendations = [];
        
        // Trending destinations that need support
        $recommendations = array_merge($recommendations, $this->getTrendingDestinationRecommendations());
        
        // Declining destinations that need attention
        $recommendations = array_merge($recommendations, $this->getDecliningDestinationRecommendations());
        
        // Seasonal opportunities
        $recommendations = array_merge($recommendations, $this->getSeasonalRecommendations());
        
        // Resource allocation recommendations
        $recommendations = array_merge($recommendations, $this->getResourceAllocationRecommendations());
        
        // Competitive insights
        $recommendations = array_merge($recommendations, $this->getCompetitiveInsights());
        
        // Sort by priority
        usort($recommendations, function($a, $b) {
            $priority_order = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priority_order[$b['priority']] - $priority_order[$a['priority']];
        });
        
        return array_slice($recommendations, 0, 10); // Return top 10 recommendations
    }
    
    private function getTrendingDestinationRecommendations() {
        $recommendations = [];
        
        // Find destinations with significant growth (>50% increase in views)
        $query = "
            SELECT 
                w.id, w.judul, w.alamat as lokasi,
                COUNT(wv_current.id) as current_views,
                COUNT(wv_previous.id) as previous_views
            FROM wisata w
            LEFT JOIN wisata_views wv_current ON w.id = wv_current.wisata_id 
                AND wv_current.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            LEFT JOIN wisata_views wv_previous ON w.id = wv_previous.wisata_id 
                AND wv_previous.view_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                AND wv_previous.view_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY w.id, w.judul, w.alamat
            HAVING current_views > 10 AND current_views > previous_views * 1.5
            ORDER BY (current_views / GREATEST(previous_views, 1)) DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $trending = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        foreach ($trending as $destination) {
            $growth_rate = $destination['previous_views'] > 0 
                ? round((($destination['current_views'] - $destination['previous_views']) / $destination['previous_views']) * 100)
                : 100;
                
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'trending',
                'icon' => 'fas fa-fire',
                'title' => "🔥 {$destination['judul']} sedang trending",
                'description' => "Destinasi ini mengalami peningkatan {$growth_rate}% views minggu ini ({$destination['current_views']} views). Saatnya memberikan dukungan ekstra.",
                'actions' => [
                    'Tambahkan promosi khusus atau diskon',
                    'Update foto dan deskripsi dengan konten menarik',
                    'Bagikan di media sosial untuk memaksimalkan momentum',
                    'Koordinasi dengan UMKM lokal untuk paket bundling'
                ],
                'urgency' => 'immediate'
            ];
        }
        
        return $recommendations;
    }
    
    private function getDecliningDestinationRecommendations() {
        $recommendations = [];
        
        // Find destinations with declining views (>40% decrease)
        $query = "
            SELECT 
                w.id, w.judul, w.alamat as lokasi, w.harga,
                COUNT(wv_current.id) as current_views,
                COUNT(wv_previous.id) as previous_views
            FROM wisata w
            LEFT JOIN wisata_views wv_current ON w.id = wv_current.wisata_id 
                AND wv_current.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            LEFT JOIN wisata_views wv_previous ON w.id = wv_previous.wisata_id 
                AND wv_previous.view_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                AND wv_previous.view_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY w.id, w.judul, w.alamat, w.harga
            HAVING previous_views > 5 AND current_views < previous_views * 0.6
            ORDER BY (previous_views - current_views) DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $declining = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        foreach ($declining as $destination) {
            $decline_rate = $destination['previous_views'] > 0 
                ? round((($destination['previous_views'] - $destination['current_views']) / $destination['previous_views']) * 100)
                : 0;
                
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'declining',
                'icon' => 'fas fa-exclamation-triangle',
                'title' => "⚠️ {$destination['judul']} mengalami penurunan",
                'description' => "Views turun {$decline_rate}% dari {$destination['previous_views']} menjadi {$destination['current_views']}. Perlu tindakan segera.",
                'actions' => [
                    'Review dan perbaiki harga (saat ini: Rp ' . number_format($destination['harga']) . ')',
                    'Tambah foto berkualitas tinggi dan video promosi',
                    'Lakukan survei kepuasan untuk feedback',
                    'Pertimbangkan paket promosi atau diskon sementara'
                ],
                'urgency' => 'within_week'
            ];
        }
        
        return $recommendations;
    }
    
    private function getSeasonalRecommendations() {
        $recommendations = [];
        $current_month = date('n');
        $current_day = date('N'); // 1 = Monday, 7 = Sunday
        
        // Weekend optimization
        if ($current_day >= 5) { // Friday or later
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'seasonal',
                'icon' => 'fas fa-calendar-weekend',
                'title' => "💡 Optimasi konten akhir pekan",
                'description' => "Akhir pekan adalah waktu puncak aktivitas. Data menunjukkan peningkatan 40% traffic di hari Sabtu-Minggu.",
                'actions' => [
                    'Update konten destinasi dengan tips akhir pekan',
                    'Promosikan paket weekend getaway',
                    'Aktifkan notifikasi push untuk penawaran weekend',
                    'Koordinasi dengan penginapan untuk promo last-minute'
                ],
                'urgency' => 'today'
            ];
        }
        
        // Holiday season recommendations (based on Indonesian holidays)
        $holiday_months = [6, 7, 12]; // June, July (holiday season), December
        if (in_array($current_month, $holiday_months)) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'seasonal',
                'icon' => 'fas fa-umbrella-beach',
                'title' => "🌴 Musim liburan - maksimalkan potensi",
                'description' => "Periode puncak wisata. Tingkatkan strategi pemasaran untuk memanfaatkan momentum liburan.",
                'actions' => [
                    'Buat paket liburan keluarga dengan harga khusus',
                    'Tambahkan konten aktivitas ramah anak',
                    'Tingkatkan kapasitas customer service',
                    'Koordinasi dengan maskapai untuk paket transport+accommodation'
                ],
                'urgency' => 'immediate'
            ];
        }
        
        return $recommendations;
    }
    
    private function getResourceAllocationRecommendations() {
        $recommendations = [];
        
        // Analyze top performing categories
        $query = "
            SELECT 
                'wisata' as category,
                COUNT(*) as total_items,
                SUM(views_30d) as total_views
            FROM (
                SELECT 
                    w.id,
                    COUNT(wv.id) as views_30d
                FROM wisata w
                LEFT JOIN wisata_views wv ON w.id = wv.wisata_id 
                    AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY w.id
            ) w_stats
            
            UNION ALL
            
            SELECT 
                'penginapan' as category,
                COUNT(*) as total_items,
                SUM(views_30d) as total_views
            FROM (
                SELECT 
                    p.id,
                    COUNT(pv.id) as views_30d
                FROM penginapan p
                LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id 
                    AND pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.id
            ) p_stats
        ";
        $result = $this->db->query($query);
        $categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Find which category needs more attention
        $wisata_data = isset($categories[0]) ? $categories[0] : ['total_views' => 0, 'total_items' => 0];
        $penginapan_data = isset($categories[1]) ? $categories[1] : ['total_views' => 0, 'total_items' => 0];
        
        $wisata_avg = $wisata_data['total_views'] / max($wisata_data['total_items'], 1);
        $penginapan_avg = $penginapan_data['total_views'] / max($penginapan_data['total_items'], 1);
        
        if ($wisata_avg > $penginapan_avg * 1.5) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'resource',
                'icon' => 'fas fa-chart-line',
                'title' => "📊 Fokuskan marketing pada penginapan",
                'description' => "Destinasi wisata mendapat perhatian lebih tinggi (rata-rata {$wisata_avg} vs {$penginapan_avg} views). Seimbangkan dengan promosi penginapan.",
                'actions' => [
                    'Alokasikan budget marketing lebih untuk penginapan',
                    'Buat konten visual menarik untuk properti accommodation',
                    'Kembangkan program loyalty untuk tamu penginapan',
                    'Partner dengan travel blogger untuk review penginapan'
                ],
                'urgency' => 'within_month'
            ];
        } elseif ($penginapan_avg > $wisata_avg * 1.5) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'resource',
                'icon' => 'fas fa-map-marked-alt',
                'title' => "📊 Tingkatkan promosi destinasi wisata",
                'description' => "Penginapan lebih populer dari destinasi wisata. Perlu keseimbangan untuk ecosystem yang sehat.",
                'actions' => [
                    'Kembangkan konten storytelling untuk destinasi',
                    'Buat virtual tour untuk destinasi unggulan',
                    'Adakan kompetisi foto wisata untuk user-generated content',
                    'Kerjasama dengan local guide untuk tour package'
                ],
                'urgency' => 'within_month'
            ];
        }
        
        return $recommendations;
    }
    
    private function getCompetitiveInsights() {
        $recommendations = [];
        
        // Analyze pricing competitiveness
        $query = "
            SELECT 
                AVG(harga) as avg_price,
                COUNT(*) as total_destinations,
                MAX(harga) as max_price,
                MIN(harga) as min_price
            FROM wisata
        ";
        $result = $this->db->query($query);
        $pricing_analysis = $result ? $result->fetch_assoc() : [];
        
        $price_range = isset($pricing_analysis['max_price']) && isset($pricing_analysis['min_price']) 
            ? $pricing_analysis['max_price'] - $pricing_analysis['min_price'] 
            : 0;
        
        if ($price_range > 0 && isset($pricing_analysis['avg_price']) && $price_range > $pricing_analysis['avg_price'] * 2) {
            $recommendations[] = [
                'priority' => 'low',
                'type' => 'competitive',
                'icon' => 'fas fa-balance-scale',
                'title' => "💰 Standardisasi harga perlu perhatian",
                'description' => "Perbedaan harga terlalu besar (Rp " . number_format($pricing_analysis['min_price']) . " - Rp " . number_format($pricing_analysis['max_price']) . "). Perlu strategi pricing yang lebih konsisten.",
                'actions' => [
                    'Review struktur harga berdasarkan kategori dan fasilitas',
                    'Buat tier pricing yang jelas (budget, premium, luxury)',
                    'Analisis harga competitor untuk benchmarking',
                    'Implementasikan dynamic pricing untuk peak season'
                ],
                'urgency' => 'within_month'
            ];
        }
        
        // User experience insights
        $query = "
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating
            FROM reviews 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        $result = $this->db->query($query);
        $reviews_data = $result ? $result->fetch_assoc() : [];
        
        if (isset($reviews_data['avg_rating']) && $reviews_data['avg_rating'] < 4.0 && $reviews_data['total_reviews'] > 10) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'competitive',
                'icon' => 'fas fa-star',
                'title' => "⭐ Rating perlu perbaikan",
                'description' => "Rating rata-rata {$reviews_data['avg_rating']}/5 dari {$reviews_data['total_reviews']} review bulan ini. Di bawah standar industri (4.0+).",
                'actions' => [
                    'Lakukan audit kualitas layanan secara menyeluruh',
                    'Follow up dengan customer yang memberikan rating rendah',
                    'Implementasikan program training untuk mitra UMKM',
                    'Buat sistem reward untuk service excellence'
                ],
                'urgency' => 'immediate'
            ];
        }
        
        return $recommendations;
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}