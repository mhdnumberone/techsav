/**
 * TechSavvyGenLtd Admin Panel JavaScript
 * Comprehensive admin functionality with AJAX, charts, and modern interactions
 */

// ============================================
// GLOBAL CONFIGURATION
// ============================================
window.AdminPanel = {
    config: {
        apiUrl: window.SITE_CONFIG?.apiUrl || '/api',
        siteUrl: window.SITE_CONFIG?.siteUrl || '',
        adminUrl: window.SITE_CONFIG?.adminUrl || '/admin',
        uploadsUrl: window.SITE_CONFIG?.uploadsUrl || '/uploads',
        assetsUrl: window.SITE_CONFIG?.assetsUrl || '/assets',
        language: window.SITE_CONFIG?.language || 'en',
        isRTL: window.SITE_CONFIG?.isRTL || false,
        itemsPerPage: 10,
        maxFileSize: 5 * 1024 * 1024, // 5MB
        allowedImageTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        autoRefreshInterval: 300000, // 5 minutes
        debounceDelay: 300
    },
    
    texts: {
        en: {
            loading: 'Loading...',
            saving: 'Saving...',
            deleting: 'Deleting...',
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            confirm: 'Confirm',
            cancel: 'Cancel',
            ok: 'OK',
            yes: 'Yes',
            no: 'No',
            delete_confirm: 'Are you sure you want to delete this item?',
            delete_multiple_confirm: 'Are you sure you want to delete the selected items?',
            network_error: 'Network error. Please check your connection.',
            validation_error: 'Please check the form for errors.',
            file_too_large: 'File size exceeds the maximum limit.',
            invalid_file_type: 'Invalid file type.',
            select_items: 'Please select at least one item.',
            operation_success: 'Operation completed successfully.',
            operation_failed: 'Operation failed. Please try again.',
            changes_not_saved: 'You have unsaved changes. Are you sure you want to leave?'
        },
        ar: {
            loading: 'جاري التحميل...',
            saving: 'جاري الحفظ...',
            deleting: 'جاري الحذف...',
            success: 'نجح',
            error: 'خطأ',
            warning: 'تحذير',
            confirm: 'تأكيد',
            cancel: 'إلغاء',
            ok: 'موافق',
            yes: 'نعم',
            no: 'لا',
            delete_confirm: 'هل أنت متأكد من حذف هذا العنصر؟',
            delete_multiple_confirm: 'هل أنت متأكد من حذف العناصر المحددة؟',
            network_error: 'خطأ في الشبكة. يرجى التحقق من الاتصال.',
            validation_error: 'يرجى التحقق من الأخطاء في النموذج.',
            file_too_large: 'حجم الملف يتجاوز الحد الأقصى المسموح.',
            invalid_file_type: 'نوع الملف غير صالح.',
            select_items: 'يرجى اختيار عنصر واحد على الأقل.',
            operation_success: 'تمت العملية بنجاح.',
            operation_failed: 'فشلت العملية. يرجى المحاولة مرة أخرى.',
            changes_not_saved: 'لديك تغييرات غير محفوظة. هل أنت متأكد من المغادرة؟'
        }
    },
    
    // Get localized text
    getText: function(key) {
        const lang = this.config.language;
        return this.texts[lang]?.[key] || this.texts.en[key] || key;
    },
    
    // State management
    state: {
        hasUnsavedChanges: false,
        currentModal: null,
        activeRequests: new Set(),
        charts: new Map(),
        timers: new Map()
    }
};

// ============================================
// UTILITY FUNCTIONS
// ============================================
const Utils = {
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat(AdminPanel.config.language === 'ar' ? 'ar-SA' : 'en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2
        }).format(amount);
    },
    
    // Format date
    formatDate: function(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            ...options
        };
        
        return new Intl.DateTimeFormat(
            AdminPanel.config.language === 'ar' ? 'ar-SA' : 'en-US',
            defaultOptions
        ).format(new Date(date));
    },
    
    // Format file size
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Generate unique ID
    generateId: function() {
        return 'admin_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },
    
    // Validate email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Sanitize HTML
    sanitizeHTML: function(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    },
    
    // Parse JSON safely
    parseJSON: function(str, defaultValue = null) {
        try {
            return JSON.parse(str);
        } catch (e) {
            return defaultValue;
        }
    },
    
    // Deep clone object
    deepClone: function(obj) {
        return JSON.parse(JSON.stringify(obj));
    }
};

// ============================================
// API HANDLER
// ============================================
const API = {
    // Make API request
    request: async function(url, options = {}) {
        const requestId = Utils.generateId();
        AdminPanel.state.activeRequests.add(requestId);
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        // Add CSRF token for non-GET requests
        if (options.method && options.method !== 'GET') {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                            document.querySelector('input[name="csrf_token"]')?.value;
            if (csrfToken) {
                defaultOptions.headers['X-CSRF-Token'] = csrfToken;
            }
        }
        
        const config = { ...defaultOptions, ...options };
        
        try {
            UI.showLoader();
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
            
        } catch (error) {
            console.error('API request failed:', error);
            UI.showAlert('error', AdminPanel.getText('network_error'));
            throw error;
            
        } finally {
            AdminPanel.state.activeRequests.delete(requestId);
            if (AdminPanel.state.activeRequests.size === 0) {
                UI.hideLoader();
            }
        }
    },
    
    // GET request
    get: function(endpoint, params = {}) {
        const url = new URL(AdminPanel.config.apiUrl + endpoint, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        return this.request(url.toString());
    },
    
    // POST request
    post: function(endpoint, data = {}) {
        return this.request(AdminPanel.config.apiUrl + endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    // PUT request
    put: function(endpoint, data = {}) {
        return this.request(AdminPanel.config.apiUrl + endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    // DELETE request
    delete: function(endpoint) {
        return this.request(AdminPanel.config.apiUrl + endpoint, {
            method: 'DELETE'
        });
    },
    
    // Upload file
    uploadFile: async function(endpoint, file, additionalData = {}) {
        const formData = new FormData();
        formData.append('file', file);
        
        Object.keys(additionalData).forEach(key => {
            formData.append(key, additionalData[key]);
        });
        
        return this.request(AdminPanel.config.apiUrl + endpoint, {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set Content-Type for FormData
        });
    }
};

// ============================================
// UI MANAGER
// ============================================
const UI = {
    // Show loading indicator
    showLoader: function() {
        let loader = document.getElementById('admin-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'admin-loader';
            loader.className = 'admin-loader';
            loader.innerHTML = `
                <div class="admin-loader-backdrop">
                    <div class="admin-loader-spinner">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">${AdminPanel.getText('loading')}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    },
    
    // Hide loading indicator
    hideLoader: function() {
        const loader = document.getElementById('admin-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    },
    
    // Show alert using SweetAlert2
    showAlert: function(type, message, title = null) {
        const config = {
            icon: type,
            text: message,
            confirmButtonColor: '#667eea',
            timer: type === 'success' ? 3000 : undefined,
            timerProgressBar: true
        };
        
        if (title) {
            config.title = title;
        }
        
        if (typeof Swal !== 'undefined') {
            return Swal.fire(config);
        } else {
            // Fallback to regular alert
            alert((title ? title + ': ' : '') + message);
            return Promise.resolve();
        }
    },
    
    // Show confirmation dialog
    showConfirm: function(message, title = AdminPanel.getText('confirm')) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: AdminPanel.getText('yes'),
                cancelButtonText: AdminPanel.getText('cancel')
            });
        } else {
            return Promise.resolve({ isConfirmed: confirm(message) });
        }
    },
    
    // Show toast notification
    showToast: function(type, message) {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: AdminPanel.config.isRTL ? 'top-start' : 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            
            Toast.fire({
                icon: type,
                title: message
            });
        } else {
            // Fallback notification
            this.showAlert(type, message);
        }
    },
    
    // Update counter with animation
    animateCounter: function(element, start, end, duration = 1000) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            element.textContent = current.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    },
    
    // Highlight element
    highlight: function(element, duration = 2000) {
        element.classList.add('highlight');
        setTimeout(() => {
            element.classList.remove('highlight');
        }, duration);
    },
    
    // Auto-resize textarea
    autoResizeTextarea: function(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    },
    
    // Update page title with notification count
    updatePageTitle: function(count = 0) {
        const baseTitle = document.title.replace(/^\(\d+\)\s/, '');
        document.title = count > 0 ? `(${count}) ${baseTitle}` : baseTitle;
    }
};

// ============================================
// FORM MANAGER
// ============================================
const FormManager = {
    // Initialize form
    init: function(form) {
        this.setupValidation(form);
        this.setupAutoSave(form);
        this.setupFileUploads(form);
        this.trackChanges(form);
    },
    
    // Setup form validation
    setupValidation: function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                UI.showAlert('error', AdminPanel.getText('validation_error'));
            }
        });
    },
    
    // Validate single field
    validateField: function(field) {
        const value = field.value.trim();
        const errors = [];
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            errors.push('This field is required');
        }
        
        // Email validation
        if (field.type === 'email' && value && !Utils.validateEmail(value)) {
            errors.push('Please enter a valid email address');
        }
        
        // Number validation
        if (field.type === 'number') {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);
            
            if (min && numValue < parseFloat(min)) {
                errors.push(`Value must be at least ${min}`);
            }
            if (max && numValue > parseFloat(max)) {
                errors.push(`Value must be at most ${max}`);
            }
        }
        
        // File validation
        if (field.type === 'file' && field.files.length > 0) {
            const file = field.files[0];
            if (file.size > AdminPanel.config.maxFileSize) {
                errors.push(AdminPanel.getText('file_too_large'));
            }
            if (field.accept && !AdminPanel.config.allowedImageTypes.includes(file.type)) {
                errors.push(AdminPanel.getText('invalid_file_type'));
            }
        }
        
        this.displayFieldErrors(field, errors);
        return errors.length === 0;
    },
    
    // Validate entire form
    validateForm: function(form) {
        const fields = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    // Display field errors
    displayFieldErrors: function(field, errors) {
        const errorContainer = field.parentNode.querySelector('.field-errors');
        
        if (errors.length > 0) {
            field.classList.add('is-invalid');
            if (errorContainer) {
                errorContainer.innerHTML = errors.map(error => 
                    `<div class="invalid-feedback d-block">${error}</div>`
                ).join('');
            }
        } else {
            field.classList.remove('is-invalid');
            if (errorContainer) {
                errorContainer.innerHTML = '';
            }
        }
    },
    
    // Clear field error
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const errorContainer = field.parentNode.querySelector('.field-errors');
        if (errorContainer) {
            errorContainer.innerHTML = '';
        }
    },
    
    // Setup auto-save
    setupAutoSave: function(form) {
        if (!form.hasAttribute('data-autosave')) return;
        
        const debouncedSave = Utils.debounce(() => {
            this.autoSave(form);
        }, 2000);
        
        form.addEventListener('input', debouncedSave);
    },
    
    // Auto-save form data
    autoSave: function(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const key = `autosave_${form.id || 'form'}`;
        
        try {
            localStorage.setItem(key, JSON.stringify(data));
            this.showAutoSaveIndicator();
        } catch (e) {
            console.warn('Auto-save failed:', e);
        }
    },
    
    // Show auto-save indicator
    showAutoSaveIndicator: function() {
        let indicator = document.getElementById('autosave-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'autosave-indicator';
            indicator.className = 'autosave-indicator';
            indicator.innerHTML = '<i class="fas fa-save"></i> Saved';
            document.body.appendChild(indicator);
        }
        
        indicator.classList.add('show');
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 2000);
    },
    
    // Setup file uploads
    setupFileUploads: function(form) {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFileSelect(e.target);
            });
            
            // Drag and drop support
            const dropZone = input.closest('.file-upload-area');
            if (dropZone) {
                this.setupDragAndDrop(dropZone, input);
            }
        });
    },
    
    // Handle file selection
    handleFileSelect: function(input) {
        const files = input.files;
        if (files.length === 0) return;
        
        const file = files[0];
        const preview = input.parentNode.querySelector('.file-preview');
        
        if (preview && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-thumbnail" style="max-width: 200px;">`;
            };
            reader.readAsDataURL(file);
        }
        
        // Show file info
        const fileInfo = input.parentNode.querySelector('.file-info');
        if (fileInfo) {
            fileInfo.innerHTML = `
                <div class="file-details">
                    <strong>${file.name}</strong><br>
                    <small>${Utils.formatFileSize(file.size)}</small>
                </div>
            `;
        }
    },
    
    // Setup drag and drop
    setupDragAndDrop: function(dropZone, input) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            });
        });
        
        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                this.handleFileSelect(input);
            }
        });
    },
    
    // Track form changes
    trackChanges: function(form) {
        const originalData = new FormData(form);
        let hasChanges = false;
        
        form.addEventListener('input', () => {
            const currentData = new FormData(form);
            hasChanges = !this.compareFormData(originalData, currentData);
            AdminPanel.state.hasUnsavedChanges = hasChanges;
        });
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (AdminPanel.state.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = AdminPanel.getText('changes_not_saved');
                return e.returnValue;
            }
        });
    },
    
    // Compare form data
    compareFormData: function(formData1, formData2) {
        const entries1 = Array.from(formData1.entries()).sort();
        const entries2 = Array.from(formData2.entries()).sort();
        
        return JSON.stringify(entries1) === JSON.stringify(entries2);
    },
    
    // Reset form changes tracking
    resetChangesTracking: function() {
        AdminPanel.state.hasUnsavedChanges = false;
    }
};

// ============================================
// TABLE MANAGER
// ============================================
const TableManager = {
    // Initialize data table
    init: function(table) {
        this.setupSorting(table);
        this.setupFiltering(table);
        this.setupBulkActions(table);
        this.setupRowActions(table);
    },
    
    // Setup column sorting
    setupSorting: function(table) {
        const headers = table.querySelectorAll('th[data-sortable]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
            
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    },
    
    // Sort table
    sortTable: function(table, header) {
        const column = header.dataset.sortable;
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = !header.classList.contains('sort-asc');
        
        // Reset all headers
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
            const icon = th.querySelector('i');
            if (icon) icon.className = 'fas fa-sort text-muted';
        });
        
        // Sort rows
        rows.sort((a, b) => {
            const aVal = a.querySelector(`td[data-${column}]`)?.dataset[column] || 
                        a.cells[header.cellIndex]?.textContent || '';
            const bVal = b.querySelector(`td[data-${column}]`)?.dataset[column] || 
                        b.cells[header.cellIndex]?.textContent || '';
            
            const comparison = aVal.localeCompare(bVal, undefined, { numeric: true });
            return isAscending ? comparison : -comparison;
        });
        
        // Update header
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        const icon = header.querySelector('i');
        if (icon) {
            icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'}`;
        }
        
        // Re-append rows
        rows.forEach(row => tbody.appendChild(row));
    },
    
    // Setup filtering
    setupFiltering: function(table) {
        const filterInputs = document.querySelectorAll('[data-table-filter]');
        
        filterInputs.forEach(input => {
            const debouncedFilter = Utils.debounce(() => {
                this.filterTable(table, input);
            }, AdminPanel.config.debounceDelay);
            
            input.addEventListener('input', debouncedFilter);
        });
    },
    
    // Filter table
    filterTable: function(table, input) {
        const filter = input.value.toLowerCase();
        const column = input.dataset.tableFilter;
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            if (column === 'all') {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            } else {
                const cell = row.querySelector(`td[data-${column}]`);
                if (cell) {
                    const text = cell.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            }
        });
        
        this.updateRowCount(table);
    },
    
    // Update visible row count
    updateRowCount: function(table) {
        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])').length;
        const counter = document.querySelector(`[data-table-count="${table.id}"]`);
        if (counter) {
            counter.textContent = visibleRows;
        }
    },
    
    // Setup bulk actions
    setupBulkActions: function(table) {
        const selectAll = table.querySelector('input[type="checkbox"][data-select-all]');
        const rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        const bulkActions = document.querySelectorAll('[data-bulk-action]');
        
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                this.updateBulkActionsState();
            });
        }
        
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectAllState(table);
                this.updateBulkActionsState();
            });
        });
        
        bulkActions.forEach(action => {
            action.addEventListener('click', (e) => {
                e.preventDefault();
                this.executeBulkAction(table, action);
            });
        });
    },
    
    // Update select all state
    updateSelectAllState: function(table) {
        const selectAll = table.querySelector('input[type="checkbox"][data-select-all]');
        const rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        const checkedBoxes = table.querySelectorAll('tbody input[type="checkbox"]:checked');
        
        if (selectAll) {
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < rowCheckboxes.length;
            selectAll.checked = checkedBoxes.length === rowCheckboxes.length && rowCheckboxes.length > 0;
        }
    },
    
    // Update bulk actions state
    updateBulkActionsState: function() {
        const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
        const bulkActions = document.querySelectorAll('[data-bulk-action]');
        const countDisplay = document.querySelector('[data-selected-count]');
        
        const hasSelection = checkedBoxes.length > 0;
        
        bulkActions.forEach(action => {
            action.disabled = !hasSelection;
        });
        
        if (countDisplay) {
            countDisplay.textContent = checkedBoxes.length;
        }
    },
    
    // Execute bulk action
    executeBulkAction: async function(table, actionButton) {
        const checkedBoxes = table.querySelectorAll('tbody input[type="checkbox"]:checked');
        const action = actionButton.dataset.bulkAction;
        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        
        if (ids.length === 0) {
            UI.showAlert('warning', AdminPanel.getText('select_items'));
            return;
        }
        
        const confirmResult = await UI.showConfirm(
            AdminPanel.getText('delete_multiple_confirm')
        );
        
        if (!confirmResult.isConfirmed) return;
        
        try {
            const response = await API.post('/bulk-action', {
                action: action,
                ids: ids
            });
            
            if (response.success) {
                UI.showToast('success', AdminPanel.getText('operation_success'));
                // Reload table or remove rows
                this.refreshTable(table);
            } else {
                UI.showAlert('error', response.message || AdminPanel.getText('operation_failed'));
            }
        } catch (error) {
            UI.showAlert('error', AdminPanel.getText('operation_failed'));
        }
    },
    
    // Setup row actions
    setupRowActions: function(table) {
        const actionButtons = table.querySelectorAll('[data-row-action]');
        
        actionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.executeRowAction(button);
            });
        });
    },
    
    // Execute row action
    executeRowAction: async function(button) {
        const action = button.dataset.rowAction;
        const id = button.dataset.id;
        const confirmAction = button.dataset.confirm;
        
        if (confirmAction) {
            const confirmResult = await UI.showConfirm(AdminPanel.getText('delete_confirm'));
            if (!confirmResult.isConfirmed) return;
        }
        
        try {
            const response = await API.post(`/${action}`, { id: id });
            
            if (response.success) {
                UI.showToast('success', AdminPanel.getText('operation_success'));
                
                if (action === 'delete') {
                    // Remove row with animation
                    const row = button.closest('tr');
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                UI.showAlert('error', response.message || AdminPanel.getText('operation_failed'));
            }
        } catch (error) {
            UI.showAlert('error', AdminPanel.getText('operation_failed'));
        }
    },
    
    // Refresh table
    refreshTable: function(table) {
        // This would typically reload the table data via AJAX
        // For now, we'll just reload the page
        window.location.reload();
    }
};

// ============================================
// CHART MANAGER
// ============================================
const ChartManager = {
    // Create chart
    createChart: function(canvas, config) {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded');
            return null;
        }
        
        const chartId = canvas.id || Utils.generateId();
        
        // Destroy existing chart
        if (AdminPanel.state.charts.has(chartId)) {
            AdminPanel.state.charts.get(chartId).destroy();
        }
        
        const chart = new Chart(canvas, {
            ...config,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                ...config.options
            }
        });
        
        AdminPanel.state.charts.set(chartId, chart);
        return chart;
    },
    
    // Update chart data
    updateChart: function(chartId, newData) {
        const chart = AdminPanel.state.charts.get(chartId);
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    },
    
    // Destroy chart
    destroyChart: function(chartId) {
        const chart = AdminPanel.state.charts.get(chartId);
        if (chart) {
            chart.destroy();
            AdminPanel.state.charts.delete(chartId);
        }
    },
    
    // Create common chart types
    createLineChart: function(canvas, labels, datasets) {
        return this.createChart(canvas, {
            type: 'line',
            data: { labels, datasets },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    createBarChart: function(canvas, labels, datasets) {
        return this.createChart(canvas, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    createDoughnutChart: function(canvas, labels, data, backgroundColor) {
        return this.createChart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: backgroundColor || [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            }
        });
    }
};

// ============================================
// MODAL MANAGER
// ============================================
const ModalManager = {
    // Show modal
    show: function(modalId, data = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Populate modal with data
        if (Object.keys(data).length > 0) {
            this.populateModal(modal, data);
        }
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        AdminPanel.state.currentModal = bsModal;
        return bsModal;
    },
    
    // Hide current modal
    hide: function() {
        if (AdminPanel.state.currentModal) {
            AdminPanel.state.currentModal.hide();
            AdminPanel.state.currentModal = null;
        }
    },
    
    // Populate modal with data
    populateModal: function(modal, data) {
        Object.keys(data).forEach(key => {
            const element = modal.querySelector(`[name="${key}"], #${key}, .${key}`);
            if (element) {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = !!data[key];
                } else {
                    element.value = data[key] || '';
                }
            }
        });
    },
    
    // Clear modal form
    clearModal: function(modal) {
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            // Clear validation errors
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.remove();
            });
        }
    }
};

// ============================================
// NOTIFICATION MANAGER
// ============================================
const NotificationManager = {
    // Initialize notifications
    init: function() {
        this.checkForNotifications();
        this.setupAutoRefresh();
    },
    
    // Check for new notifications
    checkForNotifications: async function() {
        try {
            const response = await API.get('/notifications/check');
            if (response.success) {
                this.updateNotificationCount(response.count);
                if (response.notifications.length > 0) {
                    this.showNewNotifications(response.notifications);
                }
            }
        } catch (error) {
            console.warn('Failed to check notifications:', error);
        }
    },
    
    // Update notification count
    updateNotificationCount: function(count) {
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        });
        
        UI.updatePageTitle(count);
    },
    
    // Show new notifications
    showNewNotifications: function(notifications) {
        notifications.forEach(notification => {
            UI.showToast('info', notification.message);
        });
    },
    
    // Setup auto-refresh
    setupAutoRefresh: function() {
        const refreshInterval = setInterval(() => {
            this.checkForNotifications();
        }, AdminPanel.config.autoRefreshInterval);
        
        AdminPanel.state.timers.set('notificationRefresh', refreshInterval);
    },
    
    // Mark notification as read
    markAsRead: async function(notificationId) {
        try {
            await API.post('/notifications/mark-read', { id: notificationId });
            this.checkForNotifications(); // Refresh count
        } catch (error) {
            console.warn('Failed to mark notification as read:', error);
        }
    }
};

// ============================================
// DASHBOARD MANAGER
// ============================================
const DashboardManager = {
    // Initialize dashboard
    init: function() {
        this.loadStatistics();
        this.loadCharts();
        this.setupAutoRefresh();
    },
    
    // Load statistics
    loadStatistics: async function() {
        try {
            const response = await API.get('/dashboard/stats');
            if (response.success) {
                this.updateStatistics(response.stats);
            }
        } catch (error) {
            console.warn('Failed to load statistics:', error);
        }
    },
    
    // Update statistics with animation
    updateStatistics: function(stats) {
        Object.keys(stats).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                const currentValue = parseInt(element.textContent) || 0;
                const newValue = stats[key];
                UI.animateCounter(element, currentValue, newValue);
            }
        });
    },
    
    // Load charts
    loadCharts: async function() {
        try {
            const response = await API.get('/dashboard/charts');
            if (response.success) {
                this.renderCharts(response.charts);
            }
        } catch (error) {
            console.warn('Failed to load charts:', error);
        }
    },
    
    // Render charts
    renderCharts: function(chartsData) {
        // Revenue chart
        const revenueCanvas = document.getElementById('revenueChart');
        if (revenueCanvas && chartsData.revenue) {
            ChartManager.createLineChart(
                revenueCanvas,
                chartsData.revenue.labels,
                chartsData.revenue.datasets
            );
        }
        
        // Orders chart
        const ordersCanvas = document.getElementById('ordersChart');
        if (ordersCanvas && chartsData.orders) {
            ChartManager.createBarChart(
                ordersCanvas,
                chartsData.orders.labels,
                chartsData.orders.datasets
            );
        }
        
        // Status chart
        const statusCanvas = document.getElementById('statusChart');
        if (statusCanvas && chartsData.status) {
            ChartManager.createDoughnutChart(
                statusCanvas,
                chartsData.status.labels,
                chartsData.status.data
            );
        }
    },
    
    // Setup auto-refresh
    setupAutoRefresh: function() {
        const refreshInterval = setInterval(() => {
            this.loadStatistics();
        }, AdminPanel.config.autoRefreshInterval);
        
        AdminPanel.state.timers.set('dashboardRefresh', refreshInterval);
    }
};

// ============================================
// SIDEBAR MANAGER
// ============================================
const SidebarManager = {
    // Initialize sidebar
    init: function() {
        this.setupToggle();
        this.setupNavigation();
        this.setActiveItem();
    },
    
    // Setup sidebar toggle
    setupToggle: function() {
        const toggleBtn = document.querySelector('[data-sidebar-toggle]');
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    },
    
    // Setup navigation
    setupNavigation: function() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                if (link.dataset.ajax) {
                    e.preventDefault();
                    this.loadPage(link.href);
                }
            });
        });
    },
    
    // Set active navigation item
    setActiveItem: function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    },
    
    // Load page via AJAX
    loadPage: async function(url) {
        try {
            UI.showLoader();
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const html = await response.text();
                document.querySelector('.admin-content').innerHTML = html;
                history.pushState(null, '', url);
                this.setActiveItem();
                // Re-initialize components
                this.initializePageComponents();
            }
        } catch (error) {
            console.error('Failed to load page:', error);
            UI.showAlert('error', AdminPanel.getText('network_error'));
        } finally {
            UI.hideLoader();
        }
    },
    
    // Initialize components for loaded page
    initializePageComponents: function() {
        // Re-initialize all managers for the new content
        this.initializePage();
    }
};

// ============================================
// INITIALIZATION
// ============================================
const AdminInit = {
    // Initialize entire admin panel
    init: function() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeComponents();
            this.setupGlobalEventListeners();
            this.initializePageSpecific();
        });
    },
    
    // Initialize all components
    initializeComponents: function() {
        // Initialize managers
        SidebarManager.init();
        NotificationManager.init();
        
        // Initialize forms
        document.querySelectorAll('form').forEach(form => {
            FormManager.init(form);
        });
        
        // Initialize tables
        document.querySelectorAll('table[data-table]').forEach(table => {
            TableManager.init(table);
        });
        
        // Initialize textareas
        document.querySelectorAll('textarea[data-auto-resize]').forEach(textarea => {
            textarea.addEventListener('input', () => UI.autoResizeTextarea(textarea));
            UI.autoResizeTextarea(textarea); // Initial resize
        });
        
        // Initialize tooltips
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Initialize popovers
        if (typeof bootstrap !== 'undefined') {
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        }
    },
    
    // Setup global event listeners
    setupGlobalEventListeners: function() {
        // Handle AJAX form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.dataset.ajax) {
                e.preventDefault();
                this.handleAjaxForm(form);
            }
        });
        
        // Handle AJAX links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-ajax]');
            if (link) {
                e.preventDefault();
                this.handleAjaxLink(link);
            }
        });
        
        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                ModalManager.hide();
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', Utils.throttle(() => {
            this.handleResize();
        }, 250));
        
        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                NotificationManager.checkForNotifications();
            }
        });
    },
    
    // Handle AJAX form submission
    handleAjaxForm: async function(form) {
        if (!FormManager.validateForm(form)) {
            UI.showAlert('error', AdminPanel.getText('validation_error'));
            return;
        }
        
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        
        try {
            const response = await API.request(url, {
                method: 'POST',
                body: formData,
                headers: {} // Let browser set Content-Type for FormData
            });
            
            if (response.success) {
                UI.showToast('success', response.message || AdminPanel.getText('operation_success'));
                ModalManager.hide();
                FormManager.resetChangesTracking();
                
                // Refresh page or update UI
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (response.reload) {
                    window.location.reload();
                }
            } else {
                UI.showAlert('error', response.message || AdminPanel.getText('operation_failed'));
            }
        } catch (error) {
            UI.showAlert('error', AdminPanel.getText('operation_failed'));
        }
    },
    
    // Handle AJAX link
    handleAjaxLink: async function(link) {
        const action = link.dataset.action;
        const confirmMessage = link.dataset.confirm;
        
        if (confirmMessage) {
            const result = await UI.showConfirm(confirmMessage);
            if (!result.isConfirmed) return;
        }
        
        try {
            const response = await API.get(link.href);
            
            if (response.success) {
                UI.showToast('success', response.message || AdminPanel.getText('operation_success'));
                
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else if (response.reload) {
                    window.location.reload();
                }
            } else {
                UI.showAlert('error', response.message || AdminPanel.getText('operation_failed'));
            }
        } catch (error) {
            UI.showAlert('error', AdminPanel.getText('operation_failed'));
        }
    },
    
    // Handle window resize
    handleResize: function() {
        // Update chart sizes
        AdminPanel.state.charts.forEach(chart => {
            chart.resize();
        });
    },
    
    // Initialize page-specific functionality
    initializePageSpecific: function() {
        const body = document.body;
        
        // Dashboard page
        if (body.classList.contains('admin-dashboard')) {
            DashboardManager.init();
        }
        
        // Add other page-specific initializations here
    }
};

// ============================================
// GLOBAL FUNCTIONS (for backward compatibility)
// ============================================

// Common functions used in templates
window.showAlert = UI.showAlert;
window.showConfirm = UI.showConfirm;
window.showToast = UI.showToast;
window.showModal = ModalManager.show;
window.hideModal = ModalManager.hide;

// Export main object
window.AdminPanel = AdminPanel;
window.AdminUtils = Utils;
window.AdminAPI = API;
window.AdminUI = UI;

// Initialize on load
AdminInit.init();

// Add CSS for loading indicator
const loaderStyles = `
<style>
.admin-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.admin-loader-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    backdrop-filter: blur(2px);
    display: flex;
    justify-content: center;
    align-items: center;
}

.admin-loader-spinner {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.loading-text {
    margin-top: 1rem;
    font-weight: 500;
    color: #667eea;
}

.autosave-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.autosave-indicator.show {
    opacity: 1;
    transform: translateY(0);
}

.highlight {
    animation: highlight 2s ease;
}

@keyframes highlight {
    0% { background-color: transparent; }
    50% { background-color: rgba(102, 126, 234, 0.2); }
    100% { background-color: transparent; }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', loaderStyles);