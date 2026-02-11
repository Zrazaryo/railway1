// Dashboard JavaScript untuk Sistem Arsip Dokumen

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard charts
    initializeCharts();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize real-time updates
    initializeRealTimeUpdates();
    
    // Initialize file upload drag and drop
    initializeFileUpload();
});

// Initialize Charts
function initializeCharts() {
    // Monthly Documents Chart
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        // Sample data - replace with actual data from server
        const monthlyData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Dokumen',
                data: [12, 19, 3, 5, 2, 3, 15, 8, 12, 6, 9, 11],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: monthlyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        const categoryData = {
            labels: ['Paspor', 'Visa', 'Izin Tinggal', 'Laporan', 'Lainnya'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ],
                borderWidth: 0
            }]
        };
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: categoryData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

// Initialize Bootstrap Tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Real-time Updates
function initializeRealTimeUpdates() {
    // Update statistics every 30 seconds
    setInterval(updateStatistics, 30000);
    
    // Update recent activities every 60 seconds
    setInterval(updateRecentActivities, 60000);
}

// Update Statistics
async function updateStatistics() {
    try {
        const response = await fetch('api/dashboard_stats.php');
        const data = await response.json();
        
        if (data.success) {
            updateStatCards(data.stats);
        }
    } catch (error) {
        console.error('Error updating statistics:', error);
    }
}

// Update Recent Activities
async function updateRecentActivities() {
    try {
        const response = await fetch('api/recent_activities.php');
        const data = await response.json();
        
        if (data.success) {
            updateActivityList(data.activities);
        }
    } catch (error) {
        console.error('Error updating activities:', error);
    }
}

// Update Stat Cards
function updateStatCards(stats) {
    const cards = document.querySelectorAll('.card-body .h5');
    if (cards.length >= 4) {
        cards[0].textContent = formatNumber(stats.total_documents);
        cards[1].textContent = formatNumber(stats.today_documents);
        cards[2].textContent = formatNumber(stats.total_categories);
        cards[3].textContent = formatNumber(stats.total_users);
    }
}

// Update Activity List
function updateActivityList(activities) {
    const activityContainer = document.querySelector('.activity-list');
    if (!activityContainer || !activities.length) return;
    
    let html = '';
    activities.forEach(activity => {
        html += `
            <div class="activity-item mb-3">
                <div class="d-flex align-items-center">
                    <div class="activity-icon me-3">
                        <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                    </div>
                    <div class="activity-content flex-grow-1">
                        <div class="activity-text">
                            <strong>${escapeHtml(activity.full_name)}</strong>
                            ${escapeHtml(activity.description)}
                        </div>
                        <small class="text-muted">
                            ${formatDateTime(activity.created_at)}
                        </small>
                    </div>
                </div>
            </div>
        `;
    });
    
    activityContainer.innerHTML = html;
}

// File Upload Drag and Drop
function initializeFileUpload() {
    const uploadArea = document.querySelector('.file-upload-area');
    if (!uploadArea) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    uploadArea.addEventListener('drop', handleDrop, false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    e.currentTarget.classList.add('dragover');
}

function unhighlight(e) {
    e.currentTarget.classList.remove('dragover');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        handleFiles(files);
    }
}

function handleFiles(files) {
    // Handle file upload
    console.log('Files dropped:', files);
    // Add your file upload logic here
}

// Utility Functions
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
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

// Search and Filter Functions
function performSearch() {
    const searchTerm = document.getElementById('searchInput').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    // Add loading state
    showLoadingState();
    
    // Perform AJAX search
    fetch('api/search_documents.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            search: searchTerm,
            category: categoryFilter,
            date: dateFilter
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingState();
        if (data.success) {
            updateDocumentList(data.documents);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoadingState();
        showError('Terjadi kesalahan saat mencari dokumen');
        console.error('Search error:', error);
    });
}

function updateDocumentList(documents) {
    const tbody = document.querySelector('#documentTable tbody');
    if (!tbody) return;
    
    let html = '';
    documents.forEach(doc => {
        html += `
            <tr>
                <td>${escapeHtml(doc.document_number)}</td>
                <td>${escapeHtml(doc.title)}</td>
                <td><span class="badge bg-primary">${escapeHtml(doc.category_name || 'Tanpa Kategori')}</span></td>
                <td>${escapeHtml(doc.created_by_name)}</td>
                <td>${formatDateTime(doc.created_at)}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewDocument(${doc.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="downloadDocument(${doc.id})">
                            <i class="fas fa-download"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDocument(${doc.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${doc.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Loading States
function showLoadingState() {
    const button = document.querySelector('#searchButton');
    if (button) {
        button.innerHTML = '<span class="loading-spinner me-2"></span>Mencari...';
        button.disabled = true;
    }
}

function hideLoadingState() {
    const button = document.querySelector('#searchButton');
    if (button) {
        button.innerHTML = '<i class="fas fa-search me-2"></i>Cari';
        button.disabled = false;
    }
}

// Error Handling
function showError(message) {
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

function showSuccess(message) {
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
}

// Document Actions
function viewDocument(id) {
    window.open(`documents/view.php?id=${id}`, '_blank');
}

function downloadDocument(id) {
    window.open(`documents/download.php?id=${id}`, '_blank');
}

function editDocument(id) {
    window.location.href = `documents/edit.php?id=${id}`;
}

function deleteDocument(id) {
    if (confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
        fetch(`api/delete_document.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Dokumen berhasil dihapus');
                location.reload();
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('Terjadi kesalahan saat menghapus dokumen');
            console.error('Delete error:', error);
        });
    }
}

// Auto-refresh functionality
function setupAutoRefresh() {
    // Refresh every 5 minutes
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 300000);
}

// Initialize auto-refresh on page load
document.addEventListener('DOMContentLoaded', setupAutoRefresh);
