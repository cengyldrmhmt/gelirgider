document.addEventListener('DOMContentLoaded', function() {
    initializeSettingsTabs();
    initializeSettingsForms();
    initializeColorPickers();
    initializeFileUploads();
});

// Tab İşlemleri
function initializeSettingsTabs() {
    const tabLinks = document.querySelectorAll('.settings-tabs .nav-link');
    const tabContents = document.querySelectorAll('.settings-tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Aktif tab'ı güncelle
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // İlgili içeriği göster
            const targetId = this.getAttribute('href').substring(1);
            tabContents.forEach(content => {
                content.style.display = content.id === targetId ? 'block' : 'none';
            });
        });
    });
}

// Form İşlemleri
function initializeSettingsForms() {
    const forms = document.querySelectorAll('.settings-form');
    forms.forEach(form => {
        form.addEventListener('submit', handleSettingsSubmit);
        
        // Form değişikliklerini izle
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                form.dataset.changed = 'true';
            });
        });
    });
}

// Renk Seçici İşlemleri
function initializeColorPickers() {
    const colorPickers = document.querySelectorAll('.settings-color-picker');
    colorPickers.forEach(picker => {
        picker.addEventListener('input', function(e) {
            const preview = document.querySelector(this.dataset.preview);
            if (preview) {
                preview.style.backgroundColor = e.target.value;
            }
        });
    });
}

// Dosya Yükleme İşlemleri
function initializeFileUploads() {
    const fileUploads = document.querySelectorAll('.settings-file-upload');
    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const preview = document.querySelector(upload.dataset.preview);
        
        if (input && preview) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
}

// Event Handlers
function handleSettingsSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    showLoading();
    
    fetch('/gelirgider/settings/save', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.dataset.changed = 'false';
        } else {
            showNotification(data.message, 'error');
        }
        hideLoading();
    })
    .catch(error => {
        console.error('Ayarlar kaydedilirken hata:', error);
        showNotification('Ayarlar kaydedilirken bir hata oluştu', 'error');
        hideLoading();
    });
}

// Yardımcı Fonksiyonlar
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'settings-loading';
    loading.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.settings-loading');
    if (loading) {
        loading.remove();
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.notification-container');
    if (container) {
        container.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Sayfa Değişiklik Kontrolü
window.addEventListener('beforeunload', function(e) {
    const forms = document.querySelectorAll('.settings-form[data-changed="true"]');
    if (forms.length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
}); 