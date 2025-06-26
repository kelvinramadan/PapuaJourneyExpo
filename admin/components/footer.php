<footer class="admin-footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Papua Journey Expo. Hak cipta dilindungi.</p>
        <p class="footer-links">
            <a href="#">Kebijakan Privasi</a>
            <span>•</span>
            <a href="#">Syarat Layanan</a>
            <span>•</span>
            <a href="#">Dukungan</a>
        </p>
    </div>
</footer>

<style>
.admin-footer {
    background: var(--card-bg);
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--border-color);
    margin-top: auto;
}

.footer-content {
    text-align: center;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.footer-links {
    margin-top: 0.5rem;
}

.footer-links a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.2s;
}

.footer-links a:hover {
    color: var(--primary-color);
}

.footer-links span {
    margin: 0 0.5rem;
}
</style>