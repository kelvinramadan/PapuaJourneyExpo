// Admin Dashboard JavaScript

// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar || !mainContent) return;
    
    // Mobile behavior
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('active');
    } else {
        // Desktop behavior
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed');
        
        // Save state to localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

// Auto-collapse sidebar when expand button is clicked
function autoCollapseSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && !sidebar.classList.contains('collapsed')) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('sidebar-collapsed');
        }
        localStorage.setItem('sidebarCollapsed', 'true');
    }
}

// Initialize sidebar state from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar || !mainContent) return;
    
    // Check localStorage or default to collapsed on smaller screens
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true' || window.innerWidth < 1400;
    
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
    } else {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('sidebar-collapsed');
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize charts if present
    if (typeof initializeCharts === 'function') {
        initializeCharts();
    }
    
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
});

// Show/Hide Modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Image Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file && preview) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        
        reader.readAsDataURL(file);
    }
}

// Initialize Tooltips
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}

// Initialize Data Tables
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sortable]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, this.cellIndex);
            });
        });
    });
}

// Table Sorting
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelectorAll('th')[columnIndex];
    const isAscending = header.classList.contains('sort-asc');
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (!isNaN(aValue) && !isNaN(bValue)) {
            return isAscending ? bValue - aValue : aValue - bValue;
        }
        
        return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
    });
    
    // Clear existing sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add new sort class
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

// Confirm Delete
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus item ini?') {
    return confirm(message);
}

// Show Toast Notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${getToastIcon(type)}</span>
            <span class="toast-message">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastIcon(type) {
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
            
            // Show error message
            let errorMsg = input.parentElement.querySelector('.error-message');
            if (!errorMsg) {
                errorMsg = document.createElement('span');
                errorMsg.className = 'error-message';
                errorMsg.textContent = 'Kolom ini wajib diisi';
                input.parentElement.appendChild(errorMsg);
            }
        } else {
            input.classList.remove('error');
            const errorMsg = input.parentElement.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    });
    
    return isValid;
}

// Global Search
document.addEventListener('DOMContentLoaded', function() {
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            // Implement search functionality based on current page
            console.log('Searching for:', searchTerm);
        });
    }
});

// Add CSS for toast notifications
const style = document.createElement('style');
style.textContent = `
.tooltip {
    position: absolute;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    z-index: 9999;
    pointer-events: none;
}

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 0.375rem;
    box-shadow: var(--shadow-lg);
    padding: 1rem;
    z-index: 9999;
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.toast-icon {
    font-size: 1.25rem;
}

.toast-success { border-left: 4px solid var(--secondary-color); }
.toast-success .toast-icon { color: var(--secondary-color); }

.toast-error { border-left: 4px solid var(--danger-color); }
.toast-error .toast-icon { color: var(--danger-color); }

.toast-warning { border-left: 4px solid var(--warning-color); }
.toast-warning .toast-icon { color: var(--warning-color); }

.toast-info { border-left: 4px solid var(--info-color); }
.toast-info .toast-icon { color: var(--info-color); }

.error-message {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

.form-control.error {
    border-color: var(--danger-color);
}

th[data-sortable]::after {
    content: '↕';
    margin-left: 0.5rem;
    opacity: 0.3;
}

th.sort-asc::after {
    content: '↑';
    opacity: 1;
}

th.sort-desc::after {
    content: '↓';
    opacity: 1;
}
`;
document.head.appendChild(style);