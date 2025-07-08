<!-- guestaccount.php -->
<?php
session_start();
require_once 'config/database.php';

$message = '';
$error_message = '';

// Get filters and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;
$offset = ($current_page - 1) * $items_per_page;

// Check if viewing article detail
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$related_articles = [];

if ($view_mode === 'detail' && $article_id > 0) {
    // Get article details with UMKM information
    $db = getDbConnection();
    $query = "SELECT a.*, u.business_name, u.owner_name, u.phone, u.address, u.business_type, u.profile_image as umkm_image, u.description as umkm_description 
              FROM artikel a 
              JOIN umkm u ON a.umkm_id = u.id 
              WHERE a.id = ? AND a.status = 'active'";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $article = $result->fetch_assoc();
        
        // Get related articles from same category
        $related_query = "SELECT a.*, u.business_name 
                          FROM artikel a 
                          JOIN umkm u ON a.umkm_id = u.id 
                          WHERE a.kategori = ? AND a.id != ? AND a.status = 'active' 
                          ORDER BY a.created_at DESC 
                          LIMIT 4";

        $related_stmt = $db->prepare($related_query);
        $related_stmt->bind_param("si", $article['kategori'], $article_id);
        $related_stmt->execute();
        $related_articles = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $related_stmt->close();
    } else {
        $view_mode = 'dashboard'; // Reset to dashboard if article not found
    }
    $stmt->close();
    $db->close();
}

// Get articles for dashboard view with filtering
$articles = [];
$total_articles = 0;
$total_pages = 1;

if ($view_mode === 'dashboard' || $view_mode === 'umkm') {
    $db = getDbConnection();
    // Build WHERE clause for filtering
    $where_conditions = ["a.status = 'active'"];
    $params = [];
    $param_types = "";

    if (!empty($search)) {
        $where_conditions[] = "(a.judul LIKE ? OR a.deskripsi LIKE ? OR u.business_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= "sss";
    }

    if (!empty($kategori_filter)) {
        $where_conditions[] = "a.kategori = ?";
        $params[] = $kategori_filter;
        $param_types .= "s";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Count total articles for pagination
    $count_query = "SELECT COUNT(*) as total 
                    FROM artikel a 
                    JOIN umkm u ON a.umkm_id = u.id 
                    WHERE $where_clause";

    if (!empty($params)) {
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bind_param($param_types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_articles = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $db->query($count_query);
        $total_articles = $count_result->fetch_assoc()['total'];
    }

    $total_pages = ceil($total_articles / $items_per_page);

    // Get articles with pagination
    $articles_query = "SELECT a.*, u.business_name, u.profile_image as umkm_image,
                       COALESCE(rsc.total_reviews, 0) as review_count,
                       COALESCE(rsc.average_rating, 0) as average_rating
                       FROM artikel a 
                       JOIN umkm u ON a.umkm_id = u.id 
                       LEFT JOIN review_summary_cache rsc 
                           ON rsc.item_type = 'artikel' 
                           AND rsc.item_id = a.id
                       WHERE $where_clause
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $param_types .= "ii";

    if (!empty($params)) {
        $articles_stmt = $db->prepare($articles_query);
        $articles_stmt->bind_param($param_types, ...$params);
        $articles_stmt->execute();
        $articles_result = $articles_stmt->get_result();
        $articles = $articles_result->fetch_all(MYSQLI_ASSOC);
        $articles_stmt->close();
    }
    
    $db->close();
}

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function truncateText($text, $length) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papua Journey - Discover Authentic Adventures in Papua</title>
    <meta name="description" content="Explore Papua's breathtaking landscapes, rich cultures, and unforgettable adventures. Connect with local businesses and plan your perfect journey.">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/logo.png" as="image">
    <link rel="preload" href="assets/banner.jpg" as="image">
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    
    <!-- Scripts -->
    <script src="script.js" defer></script>
    
    <style>
    
    :root{
        --primary-color: #536245;
        --secondary-color: #d9d9d9;
        --button-color: #DC9B11;
        --button-hover-color: #f4b63b;
        --text-color: #FFFCF7;
        --text-color-secondary: #191919;
        --background-color: #EBE7E4;
        --transition: all 0.3s ease-in-out;
        --shadow: #333333b2;
        --success-color: #4CAF50;
        --error-color: #f44336;
        --info-color: #2196F3;
    }

    /* Base Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
        background-color: var(--background-color);
        color: var(--text-color);
        scroll-behavior: smooth;
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* Scroll Progress Bar */
    .scroll-progress-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(to right, var(--button-color), var(--button-hover-color));
        z-index: 10000;
        transition: width 0.2s ease-out;
    }

    /* UMKM Section Styles - PRIORITY */
    .umkm-section {
        padding: 5rem 2rem;
        background-color: #f8f8f8;
    }

    .umkm-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .umkm-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .umkm-header h2 {
        font-size: 2.5rem;
        color: var(--text-color-secondary);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .umkm-header p {
        color: #666;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Filters - PRIORITY */
    .filters-section {
        margin-bottom: 2rem;
    }

    .filters-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .search-box-umkm {
        flex: 1;
        position: relative;
    }

    .search-box-umkm input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 25px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .search-box-umkm input:focus {
        border-color: var(--button-color);
        outline: none;
    }

    .category-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
    }

    .category-btn {
        padding: 8px 16px;
        background: white;
        color: var(--text-color-secondary);
        text-decoration: none;
        border-radius: 20px;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .category-btn:hover,
    .category-btn.active {
        background: var(--button-color);
        color: white;
        border-color: var(--button-color);
    }

    /* Articles Grid - PRIORITY */
    .articles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .article-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .article-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .article-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .article-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .article-card:hover .article-image img {
        transform: scale(1.05);
    }

    .placeholder-image {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #999;
    }

    .card-category {
        position: absolute;
        top: 15px;
        left: 15px;
        padding: 5px 12px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .category-jasa { color: #3498db; }
    .category-event { color: #e74c3c; }
    .category-kuliner { color: #f39c12; }
    .category-kerajinan { color: #9b59b6; }
    .category-wisata { color: #27ae60; }

    .article-card-content {
        padding: 1.5rem;
    }

    .article-card-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-color-secondary);
        margin-bottom: 0.8rem;
        line-height: 1.3;
    }

    .article-card-price {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--button-color);
        margin-bottom: 0.8rem;
    }

    .card-description {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 1rem;
    }

    .card-umkm {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
        padding: 8px 0;
        border-top: 1px solid #f0f0f0;
        font-size: 0.9rem;
        color: #666;
    }

    .umkm-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
    }

    .card-rating {
        margin-bottom: 1rem;
    }

    .rating-stars {
        display: inline-flex;
        gap: 2px;
        margin-right: 8px;
    }

    .star {
        color: #ddd;
        font-size: 0.9rem;
    }

    .star.filled {
        color: #f39c12;
    }

    .rating-value {
        font-weight: 600;
        color: var(--text-color-secondary);
        margin-right: 5px;
    }

    .review-count,
    .no-reviews {
        font-size: 0.8rem;
        color: #999;
    }

    .card-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-detail {
        background: var(--button-color);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-detail:hover {
        background: var(--button-hover-color);
        transform: translateY(-2px);
    }

    .card-date {
        font-size: 0.8rem;
        color: #999;
    }

    /* Pagination - PRIORITY */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }

    .pagination a,
    .pagination span {
        padding: 10px 15px;
        background: white;
        color: var(--text-color-secondary);
        text-decoration: none;
        border-radius: 8px;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .pagination a:hover {
        background: var(--button-color);
        color: white;
        border-color: var(--button-color);
    }

    .pagination .current {
        background: var(--button-color);
        color: white;
        border-color: var(--button-color);
    }

    .view-all-btn {
        display: block;
        width: fit-content;
        margin: 2rem auto 0;
        padding: 12px 30px;
        background: var(--button-color);
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .view-all-btn:hover {
        background: var(--button-hover-color);
        transform: translateY(-2px);
    }

    /* Article Detail Styles - PRIORITY */
    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--button-color);
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 2rem;
        transition: color 0.3s ease;
    }

    .back-button:hover {
        color: var(--button-hover-color);
    }

    .article-detail {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 3rem;
    }

    .article-header {
        position: relative;
        height: 400px;
        overflow: hidden;
    }

    .article-header img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .article-category {
        position: absolute;
        top: 30px;
        left: 30px;
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 25px;
        font-weight: 600;
    }

    .article-content {
        padding: 3rem;
    }

    .article-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-color-secondary);
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .article-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #f0f0f0;
    }

    .article-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--button-color);
    }

    .article-date {
        color: #666;
        font-size: 1rem;
    }

    .article-description {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #555;
        margin-bottom: 3rem;
    }

    /* Booking Form - PRIORITY */
    .booking-form {
        background: #f8f8f8;
        border-radius: 15px;
        padding: 2rem;
        margin: 2rem 0;
    }

    .booking-form h3 {
        color: var(--text-color-secondary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-color-secondary);
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--button-color);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .total-price {
        background: var(--button-color);
        color: white;
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
        text-align: center;
    }

    .total-price h4 {
        margin: 0;
        font-size: 1.2rem;
    }

    .btn-book {
        background: linear-gradient(135deg, var(--button-color), var(--button-hover-color));
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: transform 0.2s;
    }

    .btn-book:hover {
        transform: translateY(-2px);
    }

    /* UMKM Info Section - PRIORITY */
    .umkm-section-detail {
        background: #f8f8f8;
        border-radius: 15px;
        padding: 2rem;
        margin: 2rem 0;
    }

    .umkm-header-detail {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 2rem;
    }

    .umkm-avatar-detail {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }

    .umkm-avatar-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--button-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: white;
    }

    .umkm-info h3 {
        font-size: 1.5rem;
        color: var(--text-color-secondary);
        margin-bottom: 0.5rem;
    }

    .umkm-info p {
        color: #666;
        margin-bottom: 0.3rem;
    }

    .umkm-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .umkm-detail-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .umkm-detail-item span {
        font-size: 1.5rem;
        width: 30px;
        text-align: center;
    }

    .umkm-detail-item strong {
        display: block;
        color: var(--text-color-secondary);
        margin-bottom: 0.3rem;
    }

    /* Success Notification - PRIORITY */
    .notification-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .notification-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .notification-modal {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        text-align: center;
        max-width: 400px;
        animation: slideUp 0.3s ease;
    }

    .checkmark-container {
        margin-bottom: 1.5rem;
    }

    .checkmark-circle {
        width: 80px;
        height: 80px;
        background: #4CAF50;
        border-radius: 50%;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .checkmark {
        width: 40px;
        height: 40px;
        stroke: white;
        stroke-width: 3;
        fill: none;
    }

    .notification-message {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-color-secondary);
        margin-bottom: 0.5rem;
    }

    .notification-submessage {
        color: #666;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Alert Styles - PRIORITY */
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .results-info {
        margin-bottom: 1rem;
        color: #666;
        font-size: 0.9rem;
    }

    .no-results {
        text-align: center;
        padding: 3rem;
        color: #666;
    }

    .no-results h3 {
        color: var(--text-color-secondary);
        margin-bottom: 1rem;
    }

    /* SECONDARY STYLES FROM INDEX.CSS */

    /* Search Suggestions */
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-top: 10px;
        max-height: 300px;
        overflow-y: auto;
        display: none;
    }

    .search-suggestions.active {
        display: block;
    }

    .search-suggestion {
        padding: 12px 20px;
        cursor: pointer;
        transition: background 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    .search-suggestion:hover {
        background: #f8f8f8;
    }

    .search-suggestion:last-child {
        border-bottom: none;
    }


    /* Hero Section */
    .hero {
        height: 100vh;
        background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.5)), url(assets/banner.jpg) no-repeat center center/cover;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--text-color);
        position: relative;
        overflow: hidden;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
        pointer-events: none;
    }

    .hero-content {
        max-width: 900px;
        padding: 0 20px;
        z-index: 1;
        animation: heroFadeIn 1s ease-out;
    }

    @keyframes heroFadeIn {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        font-size: 0.9rem;
        margin-bottom: 20px;
        animation: fadeInDown 0.8s ease-out 0.2s both;
    }

    .hero-title {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .hero-title-line {
        display: block;
        animation: fadeInUp 0.8s ease-out;
    }

    .hero-title-line:nth-child(2) {
        animation-delay: 0.2s;
    }

    .hero-title .highlight {
        color: var(--button-color);
        text-shadow: 0 0 30px rgba(220, 155, 17, 0.5);
    }

    .hero-description {
        font-size: 1.3rem;
        margin-bottom: 2.5rem;
        opacity: 0.9;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        animation: fadeInUp 0.8s ease-out 0.4s both;
    }

    .hero-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 3rem;
        animation: fadeInUp 0.8s ease-out 0.6s both;
    }

    .btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-primary {
        background-color: var(--button-color);
        color: var(--text-color);
    }

    .btn-primary:hover {
        background-color: var(--button-hover-color);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(220, 155, 17, 0.4);
    }

    .btn-secondary {
        background-color: transparent;
        color: var(--text-color);
        border: 2px solid var(--text-color);
    }

    .btn-secondary:hover {
        background-color: var(--text-color);
        color: var(--primary-color);
        transform: translateY(-3px);
    }

    /* Hero Stats */
    .hero-stats {
        display: flex;
        gap: 3rem;
        justify-content: center;
        animation: fadeInUp 0.8s ease-out 0.8s both;
    }

    .stat-item {
        text-align: center;
    }

    .stat-item h3 {
        font-size: 2.5rem;
        margin-bottom: 5px;
        color: var(--button-color);
    }

    .stat-item p {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .hero-scroll-indicator {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        animation: bounce 2s ease-in-out infinite;
    }

    .hero-scroll-indicator i {
        font-size: 2rem;
        opacity: 0.7;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateX(-50%) translateY(0);
        }
        40% {
            transform: translateX(-50%) translateY(-10px);
        }
        60% {
            transform: translateX(-50%) translateY(-5px);
        }
    }

    /* Destinations Section */
    .destinations {
        padding: 5rem 2rem;
        background-color: var(--background-color);
        position: relative;
    }

    .destination-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }

    .section-label {
        display: inline-block;
        color: var(--button-color);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .destination-title h2 {
        font-size: 2.5rem;
        font-weight: 600;
        color: var(--text-color-secondary);
        margin-bottom: 1.5rem;
        line-height: 1.3;
    }

    .destination-title p {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 2rem;
        line-height: 1.8;
    }

    .destination-features {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
        font-size: 0.9rem;
    }

    .feature-item i {
        color: var(--button-color);
    }

    .destination-media {
        position: relative;
    }

    .video-container {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .video-container video {
        width: 100%;
        height: auto;
        display: block;
    }

    .video-play-button {
        position: absolute;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .video-play-button:hover {
        background: white;
        transform: scale(1.1);
    }

    .destination-cards {
        position: absolute;
        bottom: -30px;
        left: -30px;
        display: flex;
        gap: 15px;
    }

    .mini-card {
        background: white;
        border-radius: 15px;
        padding: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mini-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .mini-card img {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 5px;
    }

    .mini-card span {
        display: block;
        text-align: center;
        font-size: 0.8rem;
        color: var(--text-color-secondary);
        font-weight: 500;
    }

    /* Experiences Section */
    .experiences {
        background-color: var(--primary-color);
        padding: 5rem 2rem;
        text-align: center;
        color: #fff;
    }

    .experiences h2 {
        color: #fff;
        font-size: 2.5rem;
        margin-bottom: 3rem;
        font-weight: 600;
    }

    .experiences-icons {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 3rem;
        max-width: 800px;
        margin: 0 auto;
    }

    .icon-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .icon-item:hover {
        transform: translateY(-10px);
    }

    .icon {
        font-size: 3rem;
        color: var(--button-color);
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .icon:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        background-color: white;
    }

    .icon-item p {
        color: var(--text-color);
        font-size: 0.95rem;
        margin-top: 1rem;
        font-weight: 600;
    }

    /* Interests Section */
    .interests {
        padding: 5rem 2rem;
        background: var(--background-color);
        position: relative;
        overflow: hidden;
    }

    .interest-title {
        text-align: center;
        max-width: 600px;
        margin: 0 auto 3rem;
    }

    .interest-title h2 {
        font-size: 2.5rem;
        color: var(--text-color-secondary);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .interest-title p {
        color: #666;
        font-size: 1.1rem;
    }

    .interest-wrapper {
        position: relative;
        max-width: 1200px;
        margin: 0 auto;
    }

    .slider-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        z-index: 10;
    }

    .slider-nav:hover {
        background: var(--button-color);
        color: white;
        transform: translateY(-50%) scale(1.1);
    }

    .slider-nav-prev {
        left: -25px;
    }

    .slider-nav-next {
        right: -25px;
    }

    .interest-slider {
        display: flex;
        gap: 2rem;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        padding: 2rem 0 3rem;
        -webkit-overflow-scrolling: touch;
    }

    .interest-slider::-webkit-scrollbar {
        height: 8px;
    }

    .interest-slider::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .interest-slider::-webkit-scrollbar-thumb {
        background: var(--button-color);
        border-radius: 10px;
    }

    .interest-card {
        position: relative;
        min-width: 300px;
        height: 400px;
        border-radius: 20px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        scroll-snap-align: start;
        flex-shrink: 0;
    }

    .interest-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .interest-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.7));
        transition: all 0.3s ease;
    }

    .interest-card:hover .interest-overlay {
        background: linear-gradient(to bottom, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.8));
    }

    .interest-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 2rem;
        color: white;
        text-align: center;
        z-index: 2;
    }

    .interest-content i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .interest-content h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .interest-content p {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .interest-tag {
        display: inline-block;
        padding: 5px 15px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Plan Section */
    .plan-trip {
        padding: 5rem 2rem;
        background-color: #f8f8f8;
        text-align: center;
    }

    .plan-trip h2 {
        font-size: 2.5rem;
        color: var(--text-color-secondary);
        margin-bottom: 3rem;
        font-weight: 600;
    }

    .plan-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    .plan-card {
        background-color: white;
        border: none;
        border-radius: 15px;
        padding: 2rem;
        text-align: left;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        cursor: pointer;
    }

    .plan-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .plan-card i {
        color: var(--button-color);
        font-size: 3rem;
        margin-bottom: 1.5rem;
    }

    .plan-card h3 {
        font-size: 1.3rem;
        color: var(--text-color-secondary);
        margin-bottom: 0.8rem;
        font-weight: 600;
    }

    .plan-card p {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.6;
    }

    .trip-options {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 3rem;
    }

    .tab-btn {
        background: white;
        border: 2px solid transparent;
        color: var(--text-color-secondary);
        padding: 12px 20px;
        border-radius: 25px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        border-color: var(--button-color);
        transform: translateY(-2px);
    }

    .tab-btn.active {
        background-color: var(--button-color);
        color: white;
        border-color: var(--button-color);
    }

    .trip-form {
        background: white;
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .submit-btn {
        width: 100%;
        padding: 1rem;
        background-color: var(--button-color);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .submit-btn:hover {
        background-color: var(--button-hover-color);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220, 155, 17, 0.3);
    }

    /* Testimonials Section */
    .testimonials {
        padding: 5rem 2rem;
        background: white;
    }

    .testimonials-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .testimonials-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .testimonials-header h2 {
        font-size: 2.5rem;
        color: var(--text-color-secondary);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .testimonial-card {
        background: #f8f8f8;
        padding: 2rem;
        border-radius: 15px;
        transition: all 0.3s ease;
    }

    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .testimonial-rating {
        margin-bottom: 1rem;
    }

    .testimonial-rating i {
        color: var(--button-color);
        font-size: 1.1rem;
    }

    .testimonial-card p {
        color: #666;
        font-style: italic;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .testimonial-author {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .testimonial-author img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .testimonial-author h4 {
        margin: 0;
        color: var(--text-color-secondary);
        font-size: 1rem;
    }

    .testimonial-author span {
        color: #999;
        font-size: 0.9rem;
    }

    .testimonial-author .avatar-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    /* Interest Card Backgrounds */
    .interest-card.food {
        background: url('assets/food.jpg') center/cover;
    }

    .interest-card.culture {
        background: url('assets/culture.png') center/cover;
    }

    .interest-card.adventures {
        background: url('assets/diving.jpg') center/cover;
    }

    .interest-card.tracking {
        background: url('assets/tracking.png') center/cover;
    }

    .interest-card.wildlife {
        background: url('assets/wildlife.jpg') center/cover;
    }


    /* Scroll to Top Button */
    .scroll-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--button-color);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        z-index: 999;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: var(--button-hover-color);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Footer */
    .footer {
        background-color: var(--primary-color);
        color: var(--text-color);
        padding: 60px 20px 30px;
    }

    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto 40px;
    }

    .footer h3 {
        font-size: 1.3rem;
        margin-bottom: 20px;
        color: var(--button-color);
    }

    .footer-links ul {
        list-style: none;
    }

    .footer-links ul li {
        margin-bottom: 12px;
    }

    .footer-links ul li a {
        color: var(--text-color);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .footer-links ul li a:hover {
        color: var(--button-color);
        transform: translateX(5px);
    }

    .footer-contact p,
    .footer-contact a {
        color: var(--text-color);
        font-size: 0.95rem;
        line-height: 1.8;
        text-decoration: none;
    }

    .footer-contact a:hover {
        color: var(--button-color);
    }

    .footer-social .social-icons {
        display: flex;
        gap: 15px;
    }

    .footer-social .social-icons a {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-color);
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .footer-social .social-icons a:hover {
        background: var(--button-color);
        transform: translateY(-5px);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.9rem;
        opacity: 0.8;
    }

    /* Animation Classes */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease;
    }

    .fade-in-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Loading States */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* Utility Classes */
    .text-center {
        text-align: center;
    }

    .mt-1 { margin-top: 1rem; }
    .mt-2 { margin-top: 2rem; }
    .mt-3 { margin-top: 3rem; }

    .mb-1 { margin-bottom: 1rem; }
    .mb-2 { margin-bottom: 2rem; }
    .mb-3 { margin-bottom: 3rem; }

    .gap-1 { gap: 1rem; }
    .gap-2 { gap: 2rem; }
    .gap-3 { gap: 3rem; }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .destination-container {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .destination-media {
            margin-top: 2rem;
        }
        
        .destination-cards {
            position: static;
            justify-content: center;
            margin-top: 2rem;
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }
        
        .hero-description {
            font-size: 1.1rem;
        }
        
        .hero-actions {
            flex-direction: column;
            width: 100%;
            padding: 0 20px;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .hero-stats {
            gap: 1.5rem;
        }
        
        .stat-item h3 {
            font-size: 2rem;
        }
        
        .destinations,
        .plan-trip,
        .testimonials,
        .umkm-section {
            padding: 3rem 1rem;
        }
        
        .slider-nav {
            display: none;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .article-title {
            font-size: 2rem;
        }
        
        .article-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .umkm-header-detail {
            flex-direction: column;
            text-align: center;
        }
        
        .category-filters {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .filters-row {
            flex-direction: column;
        }
        
        .articles-grid {
            grid-template-columns: 1fr;
        }
        
        .scroll-to-top {
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
        }
    }

    @media (max-width: 480px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .section-label {
            font-size: 0.8rem;
        }
        
        h2 {
            font-size: 2rem !important;
        }
        
        .trip-options {
            gap: 0.5rem;
        }
        
        .tab-btn {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
    }

    /* Print Styles */
    @media print {
        .header,
        .scroll-to-top,
        .mobile-nav,
        .notification-overlay {
            display: none !important;
        }
        
        body {
            font-size: 12pt;
            line-height: 1.4;
        }
        
        .article-detail {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress-bar"></div>

    <?php if ($view_mode === 'detail' && $article): ?>
        <!-- Article Detail View -->
        <div style="padding-top: 100px;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Beranda
                </a>
                
                <!-- Success Notification Modal -->
                <div id="notification-overlay" class="notification-overlay">
                    <div class="notification-modal">
                        <div class="checkmark-container">
                            <div class="checkmark-circle">
                                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                                </svg>
                            </div>
                        </div>
                        <div class="notification-message">Berhasil ditambahkan!</div>
                        <div class="notification-submessage">Item telah ditambahkan ke keranjang</div>
                    </div>
                </div>
                
                <div class="article-detail">
                    <div class="article-header">
                        <?php if ($article['gambar']): ?>
                            <img src="uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                📷
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $article['kategori']; ?>">
                            <?php
                            $kategori_icons = [
                                'jasa' => '🔧 Jasa',
                                'event' => '🎉 Event',
                                'kuliner' => '🍽️ Kuliner',
                                'kerajinan' => '🎨 Kerajinan',
                                'wisata' => '🏝️ Wisata'
                            ];
                            echo $kategori_icons[$article['kategori']] ?? ucfirst($article['kategori']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($article['harga']); ?> / tiket</div>
                            <div class="article-date">
                                📅 <?php echo formatDate($article['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($article['deskripsi'])); ?>
                        </div>
                        
                        <!-- Booking Form -->
                        <?php if(isset($_SESSION['username'])): ?>
                        <div class="booking-form">
                            <h3>🎫 Pesan Tiket</h3>
                            <div id="cart-message" style="display: none;"></div>
                            <form id="add-to-cart-form">
                                <input type="hidden" name="item_type" value="artikel">
                                <input type="hidden" name="item_id" value="<?php echo $article['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nama_pemesan">Nama Pemesan</label>
                                        <input type="text" id="nama_pemesan" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_pemesan">Email</label>
                                        <input type="email" id="email_pemesan" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="jumlah_tiket">Jumlah Tiket *</label>
                                        <input type="number" name="quantity" id="jumlah_tiket" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_kunjungan">Tanggal Kunjungan *</label>
                                        <input type="date" name="booking_date" id="tanggal_kunjungan" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="catatan">Catatan Tambahan</label>
                                    <textarea name="notes" id="catatan" rows="3" placeholder="Catatan khusus untuk pemesanan Anda..."></textarea>
                                </div>
                                
                                <div class="total-price">
                                    <h4>Total: <span id="total-amount"><?php echo formatPrice($article['harga']); ?></span></h4>
                                </div>
                                
                                <button type="button" onclick="addToCart()" class="btn-book">
                                    🛒 Tambahkan ke Keranjang
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="booking-form">
                            <h3>🎫 Pesan Tiket</h3>
                            <p style="text-align: center; color: #666; margin: 2rem 0;">
                                Silakan <a href="login.php" style="color: var(--button-color); font-weight: 600;">login</a> terlebih dahulu untuk memesan tiket.
                            </p>
                            <button type="button" onclick="window.location.href='login.php'" class="btn-book">
                                🔑 Login untuk Pesan
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="umkm-section-detail">
                            <div class="umkm-header-detail">
                                <?php if ($article['umkm_image']): ?>
                                    <img src="uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['business_name']); ?>" class="umkm-avatar-detail">
                                <?php else: ?>
                                    <div class="umkm-avatar-placeholder">
                                        🏪
                                    </div>
                                <?php endif; ?>
                                
                                <div class="umkm-info">
                                    <h3><?php echo htmlspecialchars($article['business_name']); ?></h3>
                                    <p><strong>Pemilik:</strong> <?php echo htmlspecialchars($article['owner_name']); ?></p>
                                    <p><strong>Jenis Usaha:</strong> <?php echo ucfirst(htmlspecialchars($article['business_type'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="umkm-details">
                                <div class="umkm-detail-item">
                                    <span>📞</span>
                                    <div>
                                        <strong>Telepon</strong><br>
                                        <?php echo htmlspecialchars($article['phone']); ?>
                                    </div>
                                </div>
                                
                                <div class="umkm-detail-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Alamat</strong><br>
                                        <?php echo htmlspecialchars($article['address']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($article['umkm_description']): ?>
                                <div class="umkm-detail-item" style="grid-column: 1 / -1;">
                                    <span>📝</span>
                                    <div>
                                        <strong>Tentang UMKM</strong><br>
                                        <?php echo nl2br(htmlspecialchars($article['umkm_description'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($related_articles) > 0): ?>
                <div style="margin-top: 3rem;">
                    <h3 style="text-align: center; margin-bottom: 2rem; color: var(--text-color-secondary);">🌟 Artikel Terkait</h3>
                    <div class="articles-grid">
                        <?php foreach ($related_articles as $related): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($related['gambar']): ?>
                                        <img src="uploads/artikel_images/<?php echo htmlspecialchars($related['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                    <div class="card-umkm">🏪 <?php echo htmlspecialchars($related['business_name']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    
    <?php elseif ($view_mode === 'umkm'): ?>
        <!-- UMKM Browse View -->
        <div style="padding-top: 100px;">
            <div class="umkm-section">
                <div class="umkm-container">
                    <a href="?" class="back-button">
                        ⬅️ Kembali ke Beranda
                    </a>
                    
                    <div class="umkm-header">
                        <span class="section-label">Local Business</span>
                        <h2>Jelajahi <b>UMKM Papua</b></h2>
                        <p>Temukan produk dan layanan autentik dari usaha mikro, kecil, dan menengah di Papua</p>
                    </div>
                    
                    <!-- Filters Section -->
                    <div class="filters-section">
                        <form method="GET" action="">
                            <input type="hidden" name="view" value="umkm">
                            <div class="filters-row">
                                <div class="search-box-umkm">
                                    <input type="text" name="search" placeholder="🔍 Cari artikel, produk, atau UMKM..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit" style="display: none;"></button>
                            </div>
                            
                            <div class="category-filters">
                                <a href="?view=umkm" class="category-btn <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                                    🌟 Semua
                                </a>
                                <a href="?view=umkm&kategori=jasa<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'jasa' ? 'active' : ''; ?>">
                                    🔧 Jasa
                                </a>
                                <a href="?view=umkm&kategori=event<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'event' ? 'active' : ''; ?>">
                                    🎉 Event
                                </a>
                                <a href="?view=umkm&kategori=kuliner<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'kuliner' ? 'active' : ''; ?>">
                                    🍽️ Kuliner
                                </a>
                                <a href="?view=umkm&kategori=kerajinan<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'kerajinan' ? 'active' : ''; ?>">
                                    🎨 Kerajinan
                                </a>
                                <a href="?view=umkm&kategori=wisata<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'wisata' ? 'active' : ''; ?>">
                                    🏝️ Wisata
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Info -->
                    <div class="results-info">
                        <p>Menampilkan <?php echo count($articles); ?> dari <?php echo $total_articles; ?> artikel
                           <?php if ($kategori_filter): ?>
                               dalam kategori <strong><?php echo ucfirst($kategori_filter); ?></strong>
                           <?php endif; ?>
                           <?php if ($search): ?>
                               untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                           <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if (count($articles) > 0): ?>
                    <div class="articles-grid">
                        <?php foreach ($articles as $artikel): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($artikel['gambar']): ?>
                                        <img src="uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $artikel['kategori']; ?>">
                                        <?php
                                        $kategori_icons = [
                                            'jasa' => '🔧 Jasa',
                                            'event' => '🎉 Event',
                                            'kuliner' => '🍽️ Kuliner',
                                            'kerajinan' => '🎨 Kerajinan',
                                            'wisata' => '🏝️ Wisata'
                                        ];
                                        echo $kategori_icons[$artikel['kategori']] ?? ucfirst($artikel['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 80); ?>
                                    </div>
                                    
                                    <div class="card-umkm">
                                        <?php if ($artikel['umkm_image']): ?>
                                            <img src="uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                        <?php else: ?>
                                            <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                                🏪
                                            </div>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($artikel['business_name']); ?></span>
                                    </div>
                                    
                                    <!-- Review Rating Display -->
                                    <div class="card-rating">
                                        <?php if ($artikel['review_count'] > 0): ?>
                                            <div class="rating-stars">
                                                <?php 
                                                $rating = round($artikel['average_rating']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                                <span class="rating-value"><?php echo number_format($artikel['average_rating'], 1); ?></span>
                                            </div>
                                            <span class="review-count">(<?php echo $artikel['review_count']; ?> review<?php echo $artikel['review_count'] > 1 ? 's' : ''; ?>)</span>
                                        <?php else: ?>
                                            <span class="no-reviews">Belum ada review</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $artikel['id']; ?>" class="btn-detail">
                                            🎫 Lihat Detail
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($artikel['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?view=umkm&page=<?php echo $current_page - 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    ⬅️ Sebelumnya
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?view=umkm&page=<?php echo $i; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?view=umkm&page=<?php echo $current_page + 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    Selanjutnya ➡️
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <div class="no-results">
                            <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                            <h3>Tidak Ada Artikel Ditemukan</h3>
                            <p>Maaf, tidak ada artikel yang sesuai dengan pencarian Anda.</p>
                            <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Home Page -->
        <section class="hero" id="home">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge fade-in">Indonesia's Hidden Paradise</span>
                <h1 class="hero-title">
                    <span class="hero-title-line">Discover the</span>
                    <span class="hero-title-line">Beauty of <span class="highlight">Papua</span></span>
                </h1>
                <p class="hero-description">Explore breathtaking landscapes, rich cultures, and unforgettable adventures with our AI-powered travel assistant.</p>
                <div class="hero-actions">
                    <a href="#destinations" class="btn btn-primary">
                        <i class="fas fa-compass"></i>
                        Explore Now
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="500">0</span>+</h3>
                        <p>Local Partners</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="50">0</span>+</h3>
                        <p>Destinations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="1000">0</span>+</h3>
                        <p>Happy Travelers</p>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section> 

        <section class="destinations" id="destinations">
            <div class="destination-container">
                <div class="destination-title fade-in">
                    <span class="section-label">Your Adventure Awaits</span>
                    <h2>This Is Your <b>Papua Journey</b></h2>
                    <p>Discover the true essence of Papua through a personalized journey tailored exclusively to your preferences and interests, where every adventure becomes your own unique story to tell.</p>
                    <div class="destination-features">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Safe & Secure</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Local Guides</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>Best Rated</span>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="showDestinationModal()">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </button>
                </div>
                <div class="destination-media">
                    <div class="video-container">
                        <video autoplay muted loop playsinline>
                            <source src="assets/destination-video.mp4" type="video/mp4">
                        </video>
                        <div class="video-play-button" onclick="toggleVideoSound(this)">
                            <i class="fas fa-volume-mute"></i>
                        </div>
                    </div>
                    <div class="destination-cards">
                        <div class="mini-card fade-in">
                            <img src="assets/rajaAmpat.jpg" alt="Raja Ampat">
                            <span>Raja Ampat</span>
                        </div>
                        <div class="mini-card fade-in">
                            <img src="assets/TamanNasionalTelukCendrawasih.jpg" alt="Jayapura">
                            <span>Jayapura</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="experiences" class="experiences">
            <h2>Your Gateaway to <b>Authentic Experiences</b></h2>
            <div class="experiences-icons">
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-rr-mosque-alt"></i>
                    </div>
                    <p>Muslim Friendly</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-br-wheelchair"></i>
                    </div>
                    <p>Inclusive Tourism</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-sr-population-globe"></i>
                    </div>
                    <p>Community Updates</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-sr-badge-leaf"></i>
                    </div>
                    <p>Eco-Travel</p>
                </div>
            </div>
        </section>

        <section class="interests" id="interest">
            <div class="interest-container">
                <div class="interest-title fade-in">
                    <span class="section-label">Choose Your Adventure</span>
                    <h2>Explore Your <b>Interests</b></h2>
                    <p>Select your preferred activities and we'll create a personalized itinerary just for you</p>
                </div>
                <div class="interest-wrapper fade-in">
                    <button class="slider-nav slider-nav-prev" onclick="slideInterests('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="interest-slider" id="interestSlider">
                        <div class="interest-card food" data-category="culinary">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-hamburger-soda"></i>
                                <h3>Food & Drink</h3>
                                <p>Taste authentic Papuan cuisine</p>
                                <span class="interest-tag">20+ Restaurants</span>
                            </div>
                        </div>
                        <div class="interest-card culture" data-category="cultural">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-people"></i>
                                <h3>Culture & Heritage</h3>
                                <p>Experience traditional ceremonies</p>
                                <span class="interest-tag">15+ Villages</span>
                            </div>
                        </div>
                        <div class="interest-card adventures" data-category="marine">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-dolphin"></i>
                                <h3>Underwater Adventures</h3>
                                <p>Dive in pristine coral reefs</p>
                                <span class="interest-tag">30+ Dive Sites</span>
                            </div>
                        </div>
                        <div class="interest-card tracking" data-category="hiking">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-br-mountain"></i>
                                <h3>Trekking Tours</h3>
                                <p>Hike through rainforests</p>
                                <span class="interest-tag">25+ Trails</span>
                            </div>
                        </div>
                        <div class="interest-card wildlife" data-category="wildlife">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-bird"></i>
                                <h3>Wildlife & Nature</h3>
                                <p>See exotic birds of paradise</p>
                                <span class="interest-tag">40+ Species</span>
                            </div>
                        </div>
                    </div>
                    <button class="slider-nav slider-nav-next" onclick="slideInterests('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="plan-trip" id="plan">
            <div class="plan-container">
                <h2>Plan Your <b>Trip</b></h2>
                <div class="plan-content fade-in">
                    <div class="plan-card">
                        <i class="fi fi-rr-bag-map-pin"></i>
                        <h3>Before you go</h3>
                        <p>Get ready for your adventure with essential tips and information.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-car-bus"></i>
                        <h3>Transportation</h3>
                        <p>Navigate Papua with ease using our transportation guides.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-building"></i>
                        <h3>Accommodation</h3>
                        <p>Find the perfect place to stay during your journey.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-salad"></i>
                        <h3>Itinerary Ideas</h3>
                        <p>Explore suggested itineraries for a memorable trip.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-bus-ticket"></i>
                        <h3>Tour guide</h3>
                        <p>Connect with local guides for an authentic experience.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-guide-alt"></i>
                        <h3>Etiquette</h3>
                        <p>Learn about local customs and etiquette for a respectful visit.</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- UMKM Section -->
        <section class="umkm-section" id="umkm">
            <div class="umkm-container">
                <div class="umkm-header fade-in">
                    <span class="section-label">Local Business</span>
                    <h2>Temukan <b>UMKM Papua</b></h2>
                    <p>Dukung ekonomi lokal dengan menjelajahi produk dan layanan autentik dari usaha mikro, kecil, dan menengah di Papua</p>
                </div>
                
                <?php if (count($articles) > 0): ?>
                <div class="articles-grid fade-in">
                    <?php foreach (array_slice($articles, 0, 8) as $artikel): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                            <div class="article-image">
                                <?php if ($artikel['gambar']): ?>
                                    <img src="uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        📷
                                    </div>
                                <?php endif; ?>
                                <div class="card-category category-<?php echo $artikel['kategori']; ?>">
                                    <?php
                                    $kategori_icons = [
                                        'jasa' => '🔧 Jasa',
                                        'event' => '🎉 Event',
                                        'kuliner' => '🍽️ Kuliner',
                                        'kerajinan' => '🎨 Kerajinan',
                                        'wisata' => '🏝️ Wisata'
                                    ];
                                    echo $kategori_icons[$artikel['kategori']] ?? ucfirst($artikel['kategori']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                
                                <div class="card-description">
                                    <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 80); ?>
                                </div>
                                
                                <div class="card-umkm">
                                    <?php if ($artikel['umkm_image']): ?>
                                        <img src="uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                    <?php else: ?>
                                        <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                            🏪
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($artikel['business_name']); ?></span>
                                </div>
                                
                                <!-- Review Rating Display -->
                                <div class="card-rating">
                                    <?php if ($artikel['review_count'] > 0): ?>
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = round($artikel['average_rating']);
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span class="rating-value"><?php echo number_format($artikel['average_rating'], 1); ?></span>
                                        </div>
                                        <span class="review-count">(<?php echo $artikel['review_count']; ?> review<?php echo $artikel['review_count'] > 1 ? 's' : ''; ?>)</span>
                                    <?php else: ?>
                                        <span class="no-reviews">Belum ada review</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="?view=detail&id=<?php echo $artikel['id']; ?>" class="btn-detail">
                                        🎫 Pesan Tiket
                                    </a>
                                    <span class="card-date">
                                        <?php echo formatDate($artikel['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="?view=umkm" class="view-all-btn">
                    <i class="fas fa-arrow-right"></i>
                    Lihat Semua UMKM
                </a>
                <?php else: ?>
                <div class="no-results">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏪</div>
                    <h3>Belum Ada UMKM Terdaftar</h3>
                    <p>Saat ini belum ada UMKM yang terdaftar dalam sistem.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials" id="testimonials">
            <div class="testimonials-container">
                <div class="testimonials-header fade-in">
                    <span class="section-label">What Travelers Say</span>
                    <h2>Real Stories from <b>Real Adventurers</b></h2>
                </div>
                <div class="testimonials-grid">
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"The AI chatbot helped me plan the perfect diving trip to Raja Ampat. Found hidden spots I would never have discovered on my own!"</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #4A90E2;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>Sarah M.</h4>
                                <span>Adventure Diver</span>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Connected with amazing local guides through the platform. The cultural experiences were authentic and unforgettable."</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #50C878;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>John D.</h4>
                                <span>Culture Enthusiast</span>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Best platform for exploring Papua! The local business connections made our trip smooth and supported the community."</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #FF6B6B;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>Maria L.</h4>
                                <span>Eco-Tourist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#experiences">Experiences</a></li>
                    <li><a href="#plan">Plan your trip</a></li>
                    <li><a href="#umkm">UMKM</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto:info@papuajourney.com">info@papuajourney.com</a></p>
                <p>Phone: <a href="tel:+62123456789">+62 123 456 789</a></p>
            </div>
            <div class="footer-social">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Papua Journey. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Smooth scroll for pagination and navigation
        document.querySelectorAll('.pagination a, .nav-links a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
        
        // Calculate total price based on quantity for booking form
        document.getElementById('jumlah_tiket')?.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const pricePerTicket = <?php echo isset($article['harga']) ? $article['harga'] : 0; ?>;
            const total = quantity * pricePerTicket;
            const totalElement = document.getElementById('total-amount');
            if (totalElement) {
                totalElement.textContent = formatPrice(total);
            }
        });
        
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Add to cart function for logged in users
        function addToCart() {
            <?php if (!isset($_SESSION['username'])): ?>
            window.location.href = 'login.php';
            return;
            <?php endif; ?>
            
            const form = document.getElementById('add-to-cart-form');
            const formData = new FormData(form);
            
            // Show loading state
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⏳ Menambahkan...';
            
            fetch('users/cart/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    // Show success notification
                    showNotification();
                    
                    // Update cart badge if exists
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge && data.cart_count) {
                        cartBadge.textContent = data.cart_count;
                    }
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Error:', error);
                // Show success notification as fallback
                showNotification();
            });
        }
        
        // Show notification function
        function showNotification() {
            const overlay = document.getElementById('notification-overlay');
            if (overlay) {
                overlay.classList.add('show');
                
                // Hide notification after 2 seconds
                setTimeout(() => {
                    overlay.classList.remove('show');
                }, 2000);
            }
        }
        
        // Mobile menu functionality is handled by navbar.php
        
        // Scroll progress bar
        window.addEventListener('scroll', function() {
            const scrollProgress = document.querySelector('.scroll-progress-bar');
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.offsetHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            scrollProgress.style.width = scrollPercent + '%';
            
            // Show/hide scroll to top button
            const scrollBtn = document.querySelector('.scroll-to-top');
            if (scrollTop > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
            
            // Header scroll effect is handled by navbar.php
        });
        
        // Animate stats counter
        function animateStats() {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                const count = parseInt(stat.textContent);
                const increment = target / 100;
                
                if (count < target) {
                    stat.textContent = Math.ceil(count + increment);
                    setTimeout(() => animateStats(), 20);
                } else {
                    stat.textContent = target;
                }
            });
        }
        
        // Start animation when page loads
        window.addEventListener('load', () => {
            setTimeout(animateStats, 1000);
        });
        
        // Fade in animation for elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-visible');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
        
        // Interest slider functionality
        function slideInterests(direction) {
            const slider = document.getElementById('interestSlider');
            const cardWidth = 320; // card width + gap
            const currentScroll = slider.scrollLeft;
            
            if (direction === 'next') {
                slider.scrollTo({
                    left: currentScroll + cardWidth,
                    behavior: 'smooth'
                });
            } else {
                slider.scrollTo({
                    left: currentScroll - cardWidth,
                    behavior: 'smooth'
                });
            }
        }
        
        // Tab functionality for booking form
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Here you could add logic to show different form content based on tab
                console.log('Selected tab:', this.getAttribute('data-tab'));
            });
        });
        
        // Form validation
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const destination = document.getElementById('destination').value;
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            
            if (!destination || !checkin || !checkout) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Check if checkout is after checkin
            if (new Date(checkout) <= new Date(checkin)) {
                alert('Check-out date must be after check-in date');
                return;
            }
            
            alert('Booking search completed! This would typically redirect to results page.');
        });
        
        // Set minimum date for booking form
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('checkin')?.setAttribute('min', today);
        document.getElementById('checkout')?.setAttribute('min', today);
        
        // Update checkout min date when checkin changes
        document.getElementById('checkin')?.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckout = checkinDate.toISOString().split('T')[0];
            document.getElementById('checkout').setAttribute('min', minCheckout);
        });
    </script>
</body>
</html>