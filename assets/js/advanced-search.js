/**
 * Advanced Search JavaScript - Standar untuk semua halaman
 * Mendukung pencarian client-side dan server-side
 */

class AdvancedSearch {
    constructor(config = {}) {
        this.config = {
            modalId: 'advancedSearchModal',
            formId: 'advancedSearchForm',
            tableId: null,
            searchType: 'client', // 'client' atau 'server'
            searchUrl: null,
            searchFields: ['full_name', 'birth_date', 'passport_number'],
            dataAttributes: {
                'full_name': 'name',
                'birth_date': 'birthDate', 
                'passport_number': 'passport'
            },
            ...config
        };
        
        this.init();
    }
    
    init() {
        // Bind events
        this.bindEvents();
    }
    
    bindEvents() {
        // Enter key support in modal fields
        const modal = document.getElementById(this.config.modalId);
        if (modal) {
            const inputs = modal.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.performSearch();
                    }
                });
            });
        }
    }
    
    performSearch() {
        if (this.config.searchType === 'server') {
            this.performServerSearch();
        } else {
            this.performClientSearch();
        }
    }
    
    performServerSearch() {
        const formData = this.getFormData();
        const params = new URLSearchParams();
        
        // Add current URL parameters
        const currentParams = new URLSearchParams(window.location.search);
        for (const [key, value] of currentParams) {
            if (!this.config.searchFields.includes(key)) {
                params.append(key, value);
            }
        }
        
        // Add search parameters
        for (const [key, value] of Object.entries(formData)) {
            if (value.trim()) {
                params.append(key, value.trim());
            }
        }
        
        // Redirect with new parameters
        const newUrl = window.location.pathname + '?' + params.toString();
        window.location.href = newUrl;
    }
    
    performClientSearch() {
        const formData = this.getFormData();
        const tableBody = document.querySelector(`#${this.config.tableId} tbody`);
        
        if (!tableBody) {
            console.error('Table not found:', this.config.tableId);
            return;
        }
        
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(tr => {
            let match = true;
            
            // Check each search field
            for (const [fieldName, value] of Object.entries(formData)) {
                if (value.trim()) {
                    const dataAttr = this.config.dataAttributes[fieldName];
                    if (dataAttr) {
                        const rowValue = (tr.dataset[dataAttr] || '').toLowerCase();
                        const searchValue = value.trim().toLowerCase();
                        
                        if (fieldName === 'birth_date') {
                            // Exact match for dates
                            if (rowValue !== searchValue) {
                                match = false;
                                break;
                            }
                        } else {
                            // Partial match for text fields
                            if (!rowValue.includes(searchValue)) {
                                match = false;
                                break;
                            }
                        }
                    }
                }
            }
            
            tr.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        
        // Close modal
        this.closeModal();
        
        // Show result message
        this.showSearchResult(visibleCount, formData);
    }
    
    getFormData() {
        const form = document.getElementById(this.config.formId);
        const formData = {};
        
        this.config.searchFields.forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                formData[fieldName] = input.value || '';
            }
        });
        
        return formData;
    }
    
    closeModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById(this.config.modalId));
        if (modal) {
            modal.hide();
        }
    }
    
    showSearchResult(count, searchData) {
        const hasSearchTerms = Object.values(searchData).some(value => value.trim());
        
        if (hasSearchTerms) {
            if (count === 0) {
                this.showAlert('Tidak ada dokumen yang sesuai dengan kriteria pencarian.', 'warning');
            } else {
                this.showAlert(`Ditemukan ${count} dokumen yang sesuai kriteria.`, 'success');
            }
        }
    }
    
    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at top of content
        const container = document.querySelector('.container-fluid') || document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    resetSearch() {
        const form = document.getElementById(this.config.formId);
        if (form) {
            form.reset();
        }
        
        // Show all rows if client-side search
        if (this.config.searchType === 'client' && this.config.tableId) {
            const tableBody = document.querySelector(`#${this.config.tableId} tbody`);
            if (tableBody) {
                const rows = tableBody.querySelectorAll('tr');
                rows.forEach(tr => {
                    tr.style.display = '';
                });
            }
        }
    }
}

// Global functions for backward compatibility
function performAdvancedSearch() {
    if (window.advancedSearchInstance) {
        window.advancedSearchInstance.performSearch();
    }
}

function performAdvancedSearchPemusnahan() {
    if (window.advancedSearchPemusnahanInstance) {
        window.advancedSearchPemusnahanInstance.performSearch();
    }
}

function resetAdvancedSearch() {
    if (window.advancedSearchInstance) {
        window.advancedSearchInstance.resetSearch();
    }
}

// Auto-initialize based on page
document.addEventListener('DOMContentLoaded', function() {
    // Detect page type and initialize appropriate search
    const currentPath = window.location.pathname;
    
    if (currentPath.includes('staff/dashboard.php')) {
        window.advancedSearchInstance = new AdvancedSearch({
            tableId: 'staffDocsTable',
            searchType: 'client'
        });
    } else if (currentPath.includes('documents/pemusnahan.php')) {
        window.advancedSearchPemusnahanInstance = new AdvancedSearch({
            modalId: 'advancedSearchModal',
            formId: 'advancedSearchForm',
            searchType: 'server'
        });
    } else if (currentPath.includes('lockers/detail.php')) {
        window.advancedSearchInstance = new AdvancedSearch({
            searchType: 'server'
        });
    }
});