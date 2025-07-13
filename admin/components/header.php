<?php
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$page_title = $page_title ?? 'Dashboard';
?>

<!-- Skip Links for Accessibility -->
<a href="#main-content" class="skip-link">Skip to main content</a>
<a href="#sidebar" class="skip-link">Skip to navigation</a>

<header class="admin-header">
    <div class="header-left">
        <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
    </div>
    
    <div class="header-right">
        <div class="search-box">
            <i class="fas fa-search search-icon" aria-hidden="true"></i>
            <input type="text" placeholder="Cari..." id="globalSearch" aria-label="Search dashboard content">
        </div>
        
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
            </div>
            <span style="margin-right: 1rem;"><?php echo htmlspecialchars($admin_username); ?></span>
        </div>
    </div>
</header>

<!-- CDN Fallback Script -->
<script>
// Check if FontAwesome loaded successfully, provide fallback if needed
document.addEventListener('DOMContentLoaded', function() {
    const testIcon = document.createElement('i');
    testIcon.className = 'fas fa-test';
    document.body.appendChild(testIcon);
    
    const iconStyles = window.getComputedStyle(testIcon, ':before');
    if (!iconStyles.content || iconStyles.content === 'none') {
        console.warn('FontAwesome failed to load, using fallback icons');
        // Add fallback CSS
        const fallbackStyle = document.createElement('style');
        fallbackStyle.textContent = `
            .fas.fa-search::before { content: '🔍'; }
            .fas.fa-bars::before { content: '☰'; }
            .fas.fa-home::before { content: '🏠'; }
            .fas.fa-sign-out-alt::before { content: '🚪'; }
        `;
        document.head.appendChild(fallbackStyle);
    }
    
    document.body.removeChild(testIcon);
});
</script>

<style>
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    margin-top: 0.5rem;
    z-index: 1000;
}

.dropdown-item {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #F3F4F6;
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

.text-danger {
    color: var(--danger-color) !important;
}
</style>