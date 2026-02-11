// Global JavaScript untuk Sistem Arsip Dokumen

document.addEventListener('DOMContentLoaded', function() {
    // Initialize multi-tab support (harus pertama)
    initializeMultiTabSupport();
    
    // Initialize common functionality
    initializeCommonFeatures();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize notifications
    initializeNotifications();
    
    // Initialize confirmations
    initializeConfirmations();
});

// Multi-Tab Support: Pertahankan parameter tab di semua link
function initializeMultiTabSupport() {
    // Dapatkan parameter tab dari URL
    const urlParams = new URLSearchParams(window.location.search);
    let tabId = urlParams.get('tab');
    
    // Simpan tab ID di sessionStorage untuk recovery saat refresh
    if (tabId) {
        // Simpan dengan key yang unik per tab (menggunakan timestamp saat pertama kali dibuka)
        let tabKey = sessionStorage.getItem('tabKey');
        if (!tabKey) {
            tabKey = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('tabKey', tabKey);
        }
        sessionStorage.setItem('tabId_' + tabKey, tabId);
    }
    
    // Hanya proses jika ada parameter tab di URL
    if (tabId) {
        // Tambahkan parameter tab ke semua link internal
        document.querySelectorAll('a[href]').forEach(function(link) {
            const href = link.getAttribute('href');
            
            // Skip jika link external, anchor, atau sudah ada parameter tab
            if (!href || 
                href.startsWith('http://') || 
                href.startsWith('https://') || 
                href.startsWith('mailto:') || 
                href.startsWith('tel:') || 
                href.startsWith('#') ||
                href.includes('?tab=') ||
                href.includes('&tab=')) {
                return;
            }
            
            // Skip jika link ke halaman yang sama tanpa parameter
            if (href === window.location.pathname && !href.includes('?')) {
                return;
            }
            
            // Tambahkan parameter tab
            const separator = href.includes('?') ? '&' : '?';
            link.setAttribute('href', href + separator + 'tab=' + tabId);
        });
        
        // Tambahkan parameter tab ke form action
        document.querySelectorAll('form[action]').forEach(function(form) {
            const action = form.getAttribute('action');
            if (action && !action.includes('?tab=') && !action.includes('&tab=')) {
                const separator = action.includes('?') ? '&' : '?';
                form.setAttribute('action', action + separator + 'tab=' + tabId);
            }
        });
        
        // Override location.reload untuk mempertahankan parameter tab
        const originalReload = window.location.reload;
        window.location.reload = function(forceReload) {
            const currentUrl = new URL(window.location.href);
            if (!currentUrl.searchParams.has('tab') && tabId) {
                currentUrl.searchParams.set('tab', tabId);
                window.location.href = currentUrl.toString();
            } else {
                originalReload.call(window.location, forceReload);
            }
        };
    }
    // Jika tidak ada parameter tab, biarkan saja - tidak perlu redirect
}

// Initialize Common Features
function initializeCommonFeatures() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card, index) {
        setTimeout(function() {
            card.classList.add('fade-in');
        }, index * 100);
    });
}

// Form Validations
function initializeFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Real-time validation
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // Clear previous validation
    field.classList.remove('is-valid', 'is-invalid');
    
    // Required validation
    if (required && !value) {
        field.classList.add('is-invalid');
        showFieldError(field, 'Field ini wajib diisi');
        return false;
    }
    
    // Type-specific validation
    if (value) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    field.classList.add('is-invalid');
                    showFieldError(field, 'Format email tidak valid');
                    return false;
                }
                break;
                
            case 'password':
                if (value.length < 6) {
                    field.classList.add('is-invalid');
                    showFieldError(field, 'Password minimal 6 karakter');
                    return false;
                }
                break;
                
            case 'tel':
                if (!isValidPhone(value)) {
                    field.classList.add('is-invalid');
                    showFieldError(field, 'Format nomor telepon tidak valid');
                    return false;
                }
                break;
        }
    }
    
    field.classList.add('is-valid');
    hideFieldError(field);
    return true;
}

function showFieldError(field, message) {
    hideFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function hideFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Utility Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Notifications
function initializeNotifications() {
    // Check for flash messages
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(function(message) {
        const type = message.dataset.type;
        const text = message.textContent;
        
        showNotification(type, text);
        message.remove();
    });
}

function showNotification(type, message, duration = 5000) {
    const notificationContainer = getNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)} me-2"></i>
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(function() {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

function getNotificationContainer() {
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    return container;
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Confirmations
function initializeConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.confirmMessage || 'Apakah Anda yakin ingin menghapus item ini?';
            if (confirm(message)) {
                this.closest('form').submit();
            }
        });
    });
}

// AJAX Helper Functions
function makeAjaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showNotification('danger', 'Terjadi kesalahan dalam komunikasi dengan server');
            throw error;
        });
}

// Loading States
function showLoading(element, text = 'Memproses...') {
    const originalContent = element.innerHTML;
    element.dataset.originalContent = originalContent;
    element.innerHTML = `<span class="loading-spinner me-2"></span>${text}`;
    element.disabled = true;
}

function hideLoading(element) {
    const originalContent = element.dataset.originalContent;
    if (originalContent) {
        element.innerHTML = originalContent;
        element.disabled = false;
    }
}

// File Upload Helper
function handleFileUpload(input, callback) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file size (10MB limit)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification('danger', 'Ukuran file terlalu besar. Maksimal 10MB.');
        return;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showNotification('danger', 'Tipe file tidak diizinkan. Hanya PDF, JPG, PNG, dan GIF.');
        return;
    }
    
    if (callback) {
        callback(file);
    }
}

// Search Helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Table Helper
function sortTable(table, column, direction = 'asc') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.cells[column].textContent.trim();
        const bText = b.cells[column].textContent.trim();
        
        if (direction === 'asc') {
            return aText.localeCompare(bText, 'id', { numeric: true });
        } else {
            return bText.localeCompare(aText, 'id', { numeric: true });
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Print Helper
function printElement(element) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Document</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        body { font-size: 12px; }
                        .btn { display: none !important; }
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${element.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Export Helper
function exportToCSV(data, filename) {
    const csvContent = convertToCSV(data);
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function convertToCSV(data) {
    if (!data.length) return '';
    
    const headers = Object.keys(data[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = data.map(row => {
        return headers.map(header => {
            const value = row[header] || '';
            return `"${value.toString().replace(/"/g, '""')}"`;
        }).join(',');
    });
    
    return [csvHeaders, ...csvRows].join('\n');
}

// Initialize on page load
window.addEventListener('load', function() {
    // Add loaded class for CSS animations
    document.body.classList.add('loaded');
    
    // Initialize any lazy-loaded content
    const lazyElements = document.querySelectorAll('[data-lazy]');
    lazyElements.forEach(function(element) {
        const lazyFunction = element.dataset.lazy;
        if (typeof window[lazyFunction] === 'function') {
            window[lazyFunction](element);
        }
    });
});
