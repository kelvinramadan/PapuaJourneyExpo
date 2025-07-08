<?php
// admin/recommendation_system.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Recommendation System';
$db = getDbConnection();

// Recommendation Engine Functions
function getProductRecommendations($db) {
    // Collaborative filtering based on user behavior
    $recommendations = [];
    
    // Products frequently bought together
    $collaborative_query = "
        SELECT 
            p1.item_type as product_a_type,
            p1.item_id as product_a_id,
            p2.item_type as product_b_type,
            p2.item_id as product_b_id,
            COUNT(*) as co_occurrence,
            COUNT(*) * 100.0 / (
                SELECT COUNT(DISTINCT user_id) FROM cart_items WHERE item_id = p1.item_id AND item_type = p1.item_type
            ) as confidence_score
        FROM cart_items p1
        JOIN cart_items p2 ON p1.user_id = p2.user_id
        WHERE p1.item_id != p2.item_id OR p1.item_type != p2.item_type
        GROUP BY p1.item_type, p1.item_id, p2.item_type, p2.item_id
        HAVING co_occurrence >= 2
        ORDER BY confidence_score DESC, co_occurrence DESC
        LIMIT 20
    ";
    
    $recommendations['collaborative'] = $db->query($collaborative_query)->fetch_all(MYSQLI_ASSOC);
    
    // Content-based recommendations (similar products)
    $content_based_query = "
        SELECT 
            'Tourism' as category,
            w1.id as product_id,
            w1.judul as product_name,
            w1.kategori as subcategory,
            COUNT(DISTINCT w2.id) as similar_products,
            AVG(views.view_count) as avg_views
        FROM wisata w1
        LEFT JOIN wisata w2 ON w1.kategori = w2.kategori AND w1.id != w2.id
        LEFT JOIN (
            SELECT wisata_id, COUNT(*) as view_count
            FROM wisata_views
            WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY wisata_id
        ) views ON w1.id = views.wisata_id
        GROUP BY w1.id
        
        UNION ALL
        
        SELECT 
            'Accommodation' as category,
            p1.id as product_id,
            p1.judul as product_name,
            p1.kategori as subcategory,
            COUNT(DISTINCT p2.id) as similar_products,
            AVG(views.view_count) as avg_views
        FROM penginapan p1
        LEFT JOIN penginapan p2 ON p1.kategori = p2.kategori AND p1.id != p2.id
        LEFT JOIN (
            SELECT penginapan_id, COUNT(*) as view_count
            FROM penginapan_views
            WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY penginapan_id
        ) views ON p1.id = views.penginapan_id
        GROUP BY p1.id
        
        ORDER BY avg_views DESC
        LIMIT 15
    ";
    
    $recommendations['content_based'] = $db->query($content_based_query)->fetch_all(MYSQLI_ASSOC);
    
    return $recommendations;
}

function getUserRecommendations($db) {
    $user_recs = [];
    
    // Users likely to purchase (based on cart activity)
    $purchase_intent_query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            COUNT(ci.id) as cart_items,
            SUM(
                CASE 
                    WHEN ci.item_type = 'wisata' THEN (SELECT harga FROM wisata WHERE id = ci.item_id)
                    WHEN ci.item_type = 'penginapan' THEN (SELECT harga FROM penginapan WHERE id = ci.item_id)
                    WHEN ci.item_type = 'umkm' THEN (SELECT harga FROM artikel WHERE id = ci.item_id)
                    ELSE 0
                END
            ) as cart_value,
            MAX(ci.updated_at) as last_cart_activity,
            TIMESTAMPDIFF(HOUR, MAX(ci.updated_at), NOW()) as hours_since_activity,
            COUNT(t.id) as previous_purchases,
            CASE 
                WHEN COUNT(t.id) = 0 AND COUNT(ci.id) > 2 THEN 'High Intent - First Time'
                WHEN COUNT(t.id) > 0 AND TIMESTAMPDIFF(HOUR, MAX(ci.updated_at), NOW()) < 24 THEN 'High Intent - Returning'
                WHEN COUNT(ci.id) > 1 AND TIMESTAMPDIFF(HOUR, MAX(ci.updated_at), NOW()) < 48 THEN 'Medium Intent'
                ELSE 'Low Intent'
            END as purchase_intent
        FROM users u
        JOIN cart_items ci ON u.id = ci.user_id
        LEFT JOIN transaksi t ON u.id = t.user_id AND t.payment_status = 'paid'
        WHERE ci.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY u.id
        HAVING cart_items > 0
        ORDER BY 
            CASE purchase_intent
                WHEN 'High Intent - First Time' THEN 1
                WHEN 'High Intent - Returning' THEN 2
                WHEN 'Medium Intent' THEN 3
                ELSE 4
            END,
            cart_value DESC
        LIMIT 25
    ";
    
    $user_recs['purchase_intent'] = $db->query($purchase_intent_query)->fetch_all(MYSQLI_ASSOC);
    
    // Users for re-engagement (inactive but valuable)
    $reengagement_query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            DATEDIFF(NOW(), u.last_login) as days_since_login,
            SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as lifetime_value,
            COUNT(t.id) as total_purchases,
            MAX(t.created_at) as last_purchase,
            CASE 
                WHEN SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) > 2000000 THEN 'VIP'
                WHEN SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) > 500000 THEN 'Premium'
                ELSE 'Regular'
            END as customer_segment
        FROM users u
        LEFT JOIN transaksi t ON u.id = t.user_id
        WHERE DATEDIFF(NOW(), u.last_login) BETWEEN 7 AND 60
        AND u.last_login IS NOT NULL
        GROUP BY u.id
        HAVING total_purchases > 0
        ORDER BY lifetime_value DESC, days_since_login ASC
        LIMIT 20
    ";
    
    $user_recs['reengagement'] = $db->query($reengagement_query)->fetch_all(MYSQLI_ASSOC);
    
    return $user_recs;
}

function getMarketingRecommendations($db) {
    $marketing = [];
    
    // Trending products for promotion
    $trending_query = "
        SELECT 
            'wisata' as category,
            w.id,
            w.judul as title,
            w.harga as price,
            COUNT(wv.id) as views_30d,
            COUNT(ci.id) as cart_adds,
            COUNT(ci.id) * 100.0 / NULLIF(COUNT(wv.id), 0) as conversion_rate,
            'Promote as trending destination' as recommendation
        FROM wisata w
        LEFT JOIN wisata_views wv ON w.id = wv.wisata_id AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN cart_items ci ON w.id = ci.item_id AND ci.item_type = 'wisata' AND ci.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY w.id
        HAVING views_30d > 5
        
        UNION ALL
        
        SELECT 
            'penginapan' as category,
            p.id,
            p.judul as title,
            p.harga as price,
            COUNT(pv.id) as views_30d,
            COUNT(ci.id) as cart_adds,
            COUNT(ci.id) * 100.0 / NULLIF(COUNT(pv.id), 0) as conversion_rate,
            'Feature in accommodation spotlight' as recommendation
        FROM penginapan p
        LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id AND pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN cart_items ci ON p.id = ci.item_id AND ci.item_type = 'penginapan' AND ci.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id
        HAVING views_30d > 3
        
        ORDER BY views_30d DESC, conversion_rate DESC
        LIMIT 15
    ";
    
    $marketing['trending'] = $db->query($trending_query)->fetch_all(MYSQLI_ASSOC);
    
    // Underperforming products needing attention
    $underperforming_query = "
        SELECT 
            'wisata' as category,
            w.id,
            w.judul as title,
            w.harga as price,
            COALESCE(COUNT(wv.id), 0) as views_30d,
            COALESCE(COUNT(ci.id), 0) as cart_adds,
            'Needs marketing boost - low visibility' as recommendation,
            'Consider SEO optimization or featured placement' as action
        FROM wisata w
        LEFT JOIN wisata_views wv ON w.id = wv.wisata_id AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN cart_items ci ON w.id = ci.item_id AND ci.item_type = 'wisata' AND ci.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE w.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY w.id
        HAVING views_30d < 3
        
        UNION ALL
        
        SELECT 
            'penginapan' as category,
            p.id,
            p.judul as title,
            p.harga as price,
            COALESCE(COUNT(pv.id), 0) as views_30d,
            COALESCE(COUNT(ci.id), 0) as cart_adds,
            'Needs marketing boost - low engagement' as recommendation,
            'Review pricing or improve description' as action
        FROM penginapan p
        LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id AND pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN cart_items ci ON p.id = ci.item_id AND ci.item_type = 'penginapan' AND ci.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE p.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id
        HAVING views_30d < 2
        
        ORDER BY views_30d ASC
        LIMIT 10
    ";
    
    $marketing['underperforming'] = $db->query($underperforming_query)->fetch_all(MYSQLI_ASSOC);
    
    return $marketing;
}

function getPricingRecommendations($db) {
    $pricing = [];
    
    // Price optimization suggestions
    $price_analysis_query = "
        SELECT 
            category,
            AVG(price) as avg_price,
            MIN(price) as min_price,
            MAX(price) as max_price,
            STDDEV(price) as price_stddev,
            COUNT(*) as product_count,
            AVG(demand_score) as avg_demand
        FROM (
            SELECT 
                'Tourism' as category,
                w.harga as price,
                (COUNT(wv.id) + COUNT(ci.id) * 2) as demand_score
            FROM wisata w
            LEFT JOIN wisata_views wv ON w.id = wv.wisata_id AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LEFT JOIN cart_items ci ON w.id = ci.item_id AND ci.item_type = 'wisata'
            GROUP BY w.id, w.harga
            
            UNION ALL
            
            SELECT 
                'Accommodation' as category,
                p.harga as price,
                (COUNT(pv.id) + COUNT(ci.id) * 2) as demand_score
            FROM penginapan p
            LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id AND pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LEFT JOIN cart_items ci ON p.id = ci.item_id AND ci.item_type = 'penginapan'
            GROUP BY p.id, p.harga
        ) price_data
        GROUP BY category
    ";
    
    $pricing['market_analysis'] = $db->query($price_analysis_query)->fetch_all(MYSQLI_ASSOC);
    
    return $pricing;
}

// Get all recommendation data
$product_recommendations = getProductRecommendations($db);
$user_recommendations = getUserRecommendations($db);
$marketing_recommendations = getMarketingRecommendations($db);
$pricing_recommendations = getPricingRecommendations($db);

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendation System - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .recommendation-header {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .rec-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .rec-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .rec-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .product-icon { background: #EFF6FF; color: #1D4ED8; }
        .user-icon { background: #F0FDF4; color: #16A34A; }
        .marketing-icon { background: #FEF3C7; color: #D97706; }
        .pricing-icon { background: #F3E8FF; color: #7C3AED; }
        
        .rec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .rec-card {
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid #3B82F6;
        }
        
        .rec-card.high-priority {
            border-left-color: #EF4444;
            background: #FEF2F2;
        }
        
        .rec-card.medium-priority {
            border-left-color: #F59E0B;
            background: #FFFBEB;
        }
        
        .rec-card.low-priority {
            border-left-color: #10B981;
            background: #ECFDF5;
        }
        
        .intent-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .intent-high { background: #FEE2E2; color: #DC2626; }
        .intent-medium { background: #FEF3C7; color: #D97706; }
        .intent-low { background: #D1FAE5; color: #059669; }
        
        .confidence-score {
            background: #3B82F6;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .rec-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .rec-table th,
        .rec-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .rec-table th {
            background: #F9FAFB;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn.promote {
            background: #10B981;
            color: white;
        }
        
        .action-btn.engage {
            background: #3B82F6;
            color: white;
        }
        
        .action-btn.optimize {
            background: #F59E0B;
            color: white;
        }
        
        .insight-box {
            background: #EFF6FF;
            border: 1px solid #DBEAFE;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .metric-highlight {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1D4ED8;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Recommendation System Header -->
                <div class="recommendation-header">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        🎯 AI Recommendation System
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        Intelligent recommendations for products, users, marketing, and pricing optimization
                    </div>
                </div>
                
                <!-- Product Recommendations -->
                <div class="rec-section">
                    <div class="rec-header">
                        <div class="rec-icon product-icon">📦</div>
                        <div>
                            <h3>Product Recommendations</h3>
                            <p style="margin: 0; color: #6B7280;">Collaborative filtering and content-based suggestions</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Collaborative Filtering -->
                        <div>
                            <h4>🤝 Products Frequently Bought Together</h4>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach (array_slice($product_recommendations['collaborative'], 0, 8) as $rec): ?>
                                <div class="rec-card">
                                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 0.5rem;">
                                        <strong><?php echo ucfirst($rec['product_a_type']); ?> #<?php echo $rec['product_a_id']; ?></strong>
                                        <span style="margin: 0 0.5rem;">→</span>
                                        <strong><?php echo ucfirst($rec['product_b_type']); ?> #<?php echo $rec['product_b_id']; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.85rem; color: #6B7280;">
                                            <?php echo $rec['co_occurrence']; ?> co-purchases
                                        </span>
                                        <span class="confidence-score">
                                            <?php echo number_format($rec['confidence_score'], 1); ?>% confidence
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Content-Based -->
                        <div>
                            <h4>📊 Popular Products by Category</h4>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($product_recommendations['content_based'] as $product): ?>
                                <div class="rec-card">
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        <span style="background: #E5E7EB; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">
                                            <?php echo $product['category']; ?>
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 0.85rem; color: #6B7280;">
                                            <?php echo number_format($product['avg_views'] ?? 0); ?> avg views
                                        </span>
                                        <button class="action-btn promote" onclick="promoteProduct('<?php echo $product['category']; ?>', <?php echo $product['product_id']; ?>)">
                                            Promote
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Recommendations -->
                <div class="rec-section">
                    <div class="rec-header">
                        <div class="rec-icon user-icon">👥</div>
                        <div>
                            <h3>User Engagement Recommendations</h3>
                            <p style="margin: 0; color: #6B7280;">Target users for conversion and re-engagement campaigns</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- High Purchase Intent -->
                        <div>
                            <h4>🎯 High Purchase Intent Users</h4>
                            <div style="overflow-x: auto;">
                                <table class="rec-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Cart Value</th>
                                            <th>Intent</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($user_recommendations['purchase_intent'], 0, 8) as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                                <small style="color: #6B7280;"><?php echo $user['cart_items']; ?> items</small>
                                            </td>
                                            <td>Rp <?php echo number_format($user['cart_value'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="intent-badge intent-<?php echo strpos($user['purchase_intent'], 'High') !== false ? 'high' : (strpos($user['purchase_intent'], 'Medium') !== false ? 'medium' : 'low'); ?>">
                                                    <?php echo $user['purchase_intent']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="action-btn engage" onclick="engageUser(<?php echo $user['id']; ?>)">
                                                    Send Offer
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Re-engagement Targets -->
                        <div>
                            <h4>🔄 Re-engagement Opportunities</h4>
                            <div style="overflow-x: auto;">
                                <table class="rec-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Lifetime Value</th>
                                            <th>Last Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($user_recommendations['reengagement'], 0, 8) as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                                <small style="color: #6B7280;"><?php echo $user['customer_segment']; ?></small>
                                            </td>
                                            <td>Rp <?php echo number_format($user['lifetime_value'], 0, ',', '.'); ?></td>
                                            <td><?php echo $user['days_since_login']; ?> days ago</td>
                                            <td>
                                                <button class="action-btn engage" onclick="reengageUser(<?php echo $user['id']; ?>)">
                                                    Re-engage
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Marketing Recommendations -->
                <div class="rec-section">
                    <div class="rec-header">
                        <div class="rec-icon marketing-icon">📈</div>
                        <div>
                            <h3>Marketing & Promotion Recommendations</h3>
                            <p style="margin: 0; color: #6B7280;">Strategic recommendations for product promotion and visibility</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Trending Products -->
                        <div>
                            <h4>⭐ Trending Products to Promote</h4>
                            <?php foreach (array_slice($marketing_recommendations['trending'], 0, 6) as $product): ?>
                            <div class="rec-card high-priority">
                                <div style="margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                                    <span style="background: #10B981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">
                                        <?php echo ucfirst($product['category']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.85rem; color: #6B7280; margin-bottom: 0.5rem;">
                                    <?php echo $product['views_30d']; ?> views • <?php echo $product['cart_adds']; ?> cart adds
                                    <?php if ($product['conversion_rate'] > 0): ?>
                                    • <?php echo number_format($product['conversion_rate'], 1); ?>% conversion
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                    <button class="action-btn promote" onclick="createCampaign('<?php echo $product['category']; ?>', <?php echo $product['id']; ?>)">
                                        Create Campaign
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Underperforming Products -->
                        <div>
                            <h4>⚠️ Products Needing Attention</h4>
                            <?php foreach (array_slice($marketing_recommendations['underperforming'], 0, 6) as $product): ?>
                            <div class="rec-card medium-priority">
                                <div style="margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                                    <span style="background: #F59E0B; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">
                                        <?php echo ucfirst($product['category']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.85rem; color: #6B7280; margin-bottom: 0.5rem;">
                                    <?php echo $product['views_30d']; ?> views • <?php echo $product['cart_adds']; ?> cart adds
                                </div>
                                <div style="font-size: 0.8rem; color: #DC2626; margin-bottom: 0.5rem;">
                                    <?php echo $product['recommendation']; ?>
                                </div>
                                <button class="action-btn optimize" onclick="optimizeProduct('<?php echo $product['category']; ?>', <?php echo $product['id']; ?>)">
                                    Optimize
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Recommendations -->
                <div class="rec-section">
                    <div class="rec-header">
                        <div class="rec-icon pricing-icon">💰</div>
                        <div>
                            <h3>Pricing Intelligence</h3>
                            <p style="margin: 0; color: #6B7280;">Market analysis and pricing optimization insights</p>
                        </div>
                    </div>
                    
                    <div class="rec-grid">
                        <?php foreach ($pricing_recommendations['market_analysis'] as $market): ?>
                        <div class="insight-box">
                            <h4><?php echo $market['category']; ?> Market Analysis</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Average Price</div>
                                    <div class="metric-highlight">Rp <?php echo number_format($market['avg_price'], 0, ',', '.'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Price Range</div>
                                    <div style="font-size: 0.9rem; font-weight: 600;">
                                        Rp <?php echo number_format($market['min_price'], 0, ',', '.'); ?> - 
                                        Rp <?php echo number_format($market['max_price'], 0, ',', '.'); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Products</div>
                                    <div style="font-size: 0.9rem; font-weight: 600;"><?php echo number_format($market['product_count']); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Avg Demand</div>
                                    <div style="font-size: 0.9rem; font-weight: 600;"><?php echo number_format($market['avg_demand'], 1); ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script>
        // Action functions for recommendations
        function promoteProduct(category, productId) {
            if (confirm(`Promote this ${category} product?`)) {
                showToast(`${category} product #${productId} added to promotion queue`, 'success');
            }
        }
        
        function engageUser(userId) {
            if (confirm('Send personalized offer to this user?')) {
                showToast(`Engagement campaign initiated for User #${userId}`, 'success');
            }
        }
        
        function reengageUser(userId) {
            if (confirm('Send re-engagement email to this user?')) {
                showToast(`Re-engagement campaign sent to User #${userId}`, 'success');
            }
        }
        
        function createCampaign(category, productId) {
            if (confirm(`Create marketing campaign for this ${category}?`)) {
                showToast(`Marketing campaign created for ${category} #${productId}`, 'success');
            }
        }
        
        function optimizeProduct(category, productId) {
            if (confirm(`Optimize this ${category} product?`)) {
                showToast(`Optimization plan created for ${category} #${productId}`, 'info');
            }
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type}`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>