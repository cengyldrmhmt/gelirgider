// Global variables
let isProcessing = false;
let tagUsageTable;

// Initialize page
$(document).ready(function() {
    console.log('Tags page initialized');
    
    // Initialize DataTable
    initializeTagUsageTable();
    
    // Initialize preview updates
    $('#addTagName, #addTagColor').on('input', updateAddPreview);
    $('#editTagName, #editTagColor').on('input', updateEditPreview);
    
    // Color preset handlers
    $(document).on('click', '.color-preset', function(e) {
        e.preventDefault();
        const color = $(this).data('color');
        const modal = $(this).closest('.modal');
        modal.find('input[name="color"]').val(color);
        
        if (modal.attr('id') === 'addTagModal') {
            updateAddPreview();
        } else {
            updateEditPreview();
        }
    });
    
    // Initial preview
    updateAddPreview();
});

function initializeTagUsageTable() {
    if ($.fn.DataTable.isDataTable('#tagUsageTable')) {
        $('#tagUsageTable').DataTable().destroy();
    }
    
    try {
        tagUsageTable = $('#tagUsageTable').DataTable({
            pageLength: 25,
            responsive: true,
            order: [[2, 'desc']], // Sort by usage count descending
            language: {
                "decimal": "",
                "emptyTable": "Tabloda herhangi bir veri mevcut değil",
                "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "infoEmpty": "Kayıt yok",
                "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
                "lengthMenu": "_MENU_ kayıt göster",
                "loadingRecords": "Yükleniyor...",
                "processing": "İşleniyor...",
                "search": "Ara:",
                "zeroRecords": "Eşleşen kayıt bulunamadı",
                "paginate": {
                    "first": "İlk",
                    "last": "Son",
                    "next": "Sonraki",
                    "previous": "Önceki"
                }
            }
        });
    } catch (error) {
        console.error('DataTable initialization error:', error);
    }
    
    // Filter event listener
    $('#usageFilter').off('change').on('change', function() {
        const filterValue = $(this).val();
        if (filterValue === 'used') {
            tagUsageTable.column(2).search('^(?!.*0 işlem).*$', true, false).draw();
        } else if (filterValue === 'unused') {
            tagUsageTable.column(2).search('0 işlem').draw();
        } else {
            tagUsageTable.column(2).search('').draw();
        }
    });
}

// Open add modal
function openAddModal() {
    if (isProcessing) return;
    
    // Reset form
    document.getElementById('addTagForm').reset();
    document.getElementById('addTagColor').value = '#007bff';
    updateAddPreview();
    
    // Show modal
    $('#addTagModal').modal('show');
}

// Open edit modal
function openEditModal(id, name, color) {
    if (isProcessing) return;
    
    // Set form values
    document.getElementById('editTagId').value = id;
    document.getElementById('editTagName').value = name;
    document.getElementById('editTagColor').value = color;
    
    // Update preview
    updateEditPreview();
    
    // Show modal
    $('#editTagModal').modal('show');
}

// Save new tag
function saveTag() {
    if (isProcessing) return;
    
    const name = document.getElementById('addTagName').value.trim();
    const color = document.getElementById('addTagColor').value;
    
    if (!name) {
        showMessage('error', 'Etiket adı gereklidir.');
        return;
    }
    
    // Check for duplicate names on frontend
    let duplicateFound = false;
    $('.tag-name').each(function() {
        if ($(this).text().toLowerCase() === name.toLowerCase()) {
            duplicateFound = true;
            return false; // break
        }
    });
    
    if (duplicateFound) {
        showMessage('error', 'Bu etiket adı zaten kullanılıyor.');
        return;
    }
    
    isProcessing = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('name', name);
    formData.append('color', color);
    
    // Send AJAX request
    fetch('/gelirgider/app/controllers/TagController.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#addTagModal').modal('hide');
            showMessage('success', 'Etiket başarıyla oluşturuldu');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage('error', data.message || 'Etiket eklenirken bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showMessage('error', 'Etiket eklenirken bir hata oluştu');
    })
    .finally(() => {
        isProcessing = false;
    });
}

// Update tag
function updateTag() {
    if (isProcessing) return;
    
    const id = document.getElementById('editTagId').value;
    const name = document.getElementById('editTagName').value.trim();
    const color = document.getElementById('editTagColor').value;
    
    if (!name) {
        showMessage('error', 'Etiket adı gereklidir.');
        return;
    }
    
    isProcessing = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('id', id);
    formData.append('name', name);
    formData.append('color', color);
    
    // Send AJAX request
    fetch('/gelirgider/app/controllers/TagController.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#editTagModal').modal('hide');
            showMessage('success', 'Etiket başarıyla güncellendi');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage('error', data.message || 'Etiket güncellenirken bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Update error:', error);
        showMessage('error', 'Etiket güncellenirken bir hata oluştu');
    })
    .finally(() => {
        isProcessing = false;
    });
}

// Delete tag
function deleteTag(id, name) {
    if (isProcessing) return;
    
    if (!confirm(`"${name}" etiketini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        return;
    }
    
    isProcessing = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('id', id);
    
    // Send AJAX request
    fetch('/gelirgider/app/controllers/TagController.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('success', 'Etiket başarıyla silindi');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage('error', data.message || 'Etiket silinirken bir hata oluştu');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showMessage('error', 'Etiket silinirken bir hata oluştu');
    })
    .finally(() => {
        isProcessing = false;
    });
}

// Update add preview
function updateAddPreview() {
    const name = document.getElementById('addTagName').value || 'Örnek Etiket';
    const color = document.getElementById('addTagColor').value;
    
    const preview = document.getElementById('addTagPreview');
    preview.textContent = name;
    preview.style.backgroundColor = color;
}

// Update edit preview
function updateEditPreview() {
    const name = document.getElementById('editTagName').value || 'Örnek Etiket';
    const color = document.getElementById('editTagColor').value;
    
    const preview = document.getElementById('editTagPreview');
    preview.textContent = name;
    preview.style.backgroundColor = color;
}

// View tag usage
function viewTagUsage(tagId) {
    // Show modal
    $('#tagUsageModal').modal('show');
    
    // Reset content
    document.getElementById('tagUsageContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-2">Kullanım detayları yükleniyor...</p>
        </div>
    `;
    
    // Fetch tag usage details
    fetch(`/gelirgider/app/controllers/TagController.php?action=getUsageDetails&id=${tagId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTagUsageDetails(data.data);
            } else {
                document.getElementById('tagUsageContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ${data.message || 'Kullanım detayları yüklenirken hata oluştu'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Usage details error:', error);
            document.getElementById('tagUsageContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Kullanım detayları yüklenirken hata oluştu
                </div>
            `;
        });
}

function displayTagUsageDetails(data) {
    const tag = data.tag;
    const walletTransactions = data.wallet_transactions || [];
    const creditCardTransactions = data.credit_card_transactions || [];
    
    // Update modal title
    document.getElementById('tagUsageModalTitle').textContent = `"${tag.name}" Etiket Kullanım Detayı`;
    
    let content = `
        <!-- Tag Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="tag-color-indicator me-3" 
                                  style="background-color: ${tag.color}; width: 24px; height: 24px; border-radius: 50%; display: inline-block; border: 1px solid #dee2e6;"></span>
                            <div>
                                <h5 class="mb-1">${tag.name}</h5>
                                <p class="mb-0 text-muted">Toplam ${data.total_usage} işlemde kullanılmış</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Usage Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-wallet fa-2x text-primary me-3"></i>
                            <div>
                                <h6 class="text-primary mb-1">Cüzdan İşlemleri</h6>
                                <h4 class="mb-0">${walletTransactions.length}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-credit-card fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="text-warning mb-1">Kredi Kartı İşlemleri</h6>
                                <h4 class="mb-0">${creditCardTransactions.length}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Wallet Transactions
    if (walletTransactions.length > 0) {
        content += `
            <div class="mb-4">
                <h6 class="text-primary mb-3"><i class="fas fa-wallet"></i> Cüzdan İşlemleri</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Açıklama</th>
                                <th>Kategori</th>
                                <th>Tutar</th>
                                <th>Tip</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        walletTransactions.forEach(transaction => {
            const amount = parseFloat(transaction.amount);
            const amountClass = transaction.type === 'income' ? 'text-success' : 'text-danger';
            const typeIcon = transaction.type === 'income' ? 'fa-arrow-up' : 'fa-arrow-down';
            const typeText = transaction.type === 'income' ? 'Gelir' : 'Gider';
            
            content += `
                <tr>
                    <td>${new Date(transaction.transaction_date).toLocaleDateString('tr-TR')}</td>
                    <td>${transaction.description || '-'}</td>
                    <td>${transaction.category_name || '-'}</td>
                    <td class="${amountClass}">₺${amount.toLocaleString('tr-TR', {minimumFractionDigits: 2})}</td>
                    <td>
                        <span class="badge bg-${transaction.type === 'income' ? 'success' : 'danger'}">
                            <i class="fas ${typeIcon}"></i> ${typeText}
                        </span>
                    </td>
                </tr>
            `;
        });
        
        content += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Credit Card Transactions
    if (creditCardTransactions.length > 0) {
        content += `
            <div class="mb-4">
                <h6 class="text-warning mb-3"><i class="fas fa-credit-card"></i> Kredi Kartı İşlemleri</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Açıklama</th>
                                <th>Kategori</th>
                                <th>Tutar</th>
                                <th>Kart</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        creditCardTransactions.forEach(transaction => {
            const amount = parseFloat(transaction.amount);
            
            content += `
                <tr>
                    <td>${new Date(transaction.transaction_date).toLocaleDateString('tr-TR')}</td>
                    <td>${transaction.description || '-'}</td>
                    <td>${transaction.category_name || '-'}</td>
                    <td class="text-danger">₺${amount.toLocaleString('tr-TR', {minimumFractionDigits: 2})}</td>
                    <td>${transaction.credit_card_name || '-'}</td>
                </tr>
            `;
        });
        
        content += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    if (walletTransactions.length === 0 && creditCardTransactions.length === 0) {
        content += `
            <div class="text-center py-4">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Bu etiket henüz hiçbir işlemde kullanılmamış</h5>
                <p class="text-muted">İşlem eklerken bu etiketi seçerek kullanmaya başlayabilirsiniz.</p>
            </div>
        `;
    }
    
    document.getElementById('tagUsageContent').innerHTML = content;
}

// Refresh usage stats
function refreshUsageStats() {
    if (tagUsageTable) {
        tagUsageTable.ajax.reload();
        showMessage('success', 'İstatistikler yenilendi');
    } else {
        location.reload();
    }
}

// Show message
function showMessage(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    const messageHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.getElementById('messageContainer').innerHTML = messageHtml;
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
} 