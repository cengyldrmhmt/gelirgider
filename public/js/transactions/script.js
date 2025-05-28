// Transactions JavaScript
$(document).ready(function() {
    console.log('Transactions page ready, initializing...');
    
    // Eğer script zaten çalıştırılmışsa, tekrar çalıştırma
    if (window.transactionsInitialized) {
        console.log('Transactions already initialized, skipping...');
        return;
    }
    window.transactionsInitialized = true;
    
    // Set default date range to current month
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    $('#dateFromFilter').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#dateToFilter').val(lastDayOfMonth.toISOString().split('T')[0]);
    
    // Initialize DataTable
    initializeAllTransactionsTable();
    
    // Load summary data
    loadSummaryData();
    
    // Load tags for filters
    loadTags();
});

var allTransactionsTable;

function initializeAllTransactionsTable() {
    console.log('Initializing All Transactions DataTable...');
    
    if ($.fn.DataTable.isDataTable('#allTransactionsTable')) {
        $('#allTransactionsTable').DataTable().destroy();
    }
    
    try {
        allTransactionsTable = $('#allTransactionsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/gelirgider/app/controllers/TransactionController.php?action=getAllTransactions',
                type: 'GET',
                data: function(d) {
                    d.source = $('#sourceFilter').val();
                    d.type = $('#typeFilter').val();
                    d.category_id = $('#categoryFilter').val();
                    d.tag_id = $('#tagFilter').val();
                    d.date_from = $('#dateFromFilter').val();
                    d.date_to = $('#dateToFilter').val();
                },
                dataSrc: function(json) {
                    if (json && json.success) {
                        return json.data || [];
                    } else {
                        showNotification('error', json ? json.message : 'İşlemler yüklenirken hata oluştu');
                        return [];
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error);
                    showNotification('error', 'İşlemler yüklenirken hata oluştu: ' + error);
                }
            },
            columns: [
                {
                    data: 'transaction_date',
                    render: function(data) {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('tr-TR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                },
                {
                    data: 'source_name',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        const sourceClass = row.source_type === 'wallet' ? 'source-wallet' : 'source-credit-card';
                        const sourceIcon = row.source_type === 'wallet' ? 'wallet' : 'credit-card';
                        const sourceText = row.source_type === 'wallet' ? 'Cüzdan' : 'Kredi Kartı';
                        
                        return `
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2" style="background-color: ${row.source_color || '#007bff'}">
                                    <i class="fas fa-${row.source_icon || sourceIcon} text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">${data}</div>
                                    <small class="badge ${sourceClass}">${sourceText}</small>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'type',
                    render: function(data) {
                        const types = {
                            'income': { text: 'Gelir', class: 'bg-success' },
                            'expense': { text: 'Gider', class: 'bg-danger' },
                            'purchase': { text: 'Satın Alma', class: 'bg-warning' },
                            'payment': { text: 'Ödeme', class: 'bg-info' },
                            'installment': { text: 'Taksit', class: 'bg-secondary' },
                            'fee': { text: 'Ücret', class: 'bg-dark' },
                            'interest': { text: 'Faiz', class: 'bg-danger' },
                            'refund': { text: 'İade', class: 'bg-success' }
                        };
                        const type = types[data] || { text: data || '-', class: 'bg-secondary' };
                        return `<span class="badge ${type.class}">${type.text}</span>`;
                    }
                },
                {
                    data: 'category_name',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `<span class="badge" style="background-color: ${row.category_color || '#6c757d'}">${data}</span>`;
                    }
                },
                {
                    data: 'tags',
                    render: function(data) {
                        if (!data || !data.length) return '-';
                        let tagsHtml = '';
                        data.forEach(function(tag) {
                            tagsHtml += `<span class="badge bg-info me-1">${tag.name}</span>`;
                        });
                        return tagsHtml;
                    }
                },
                {
                    data: 'description',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'amount',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        const isPositive = ['income', 'refund', 'payment'].includes(row.type);
                        const amountClass = isPositive ? 'positive' : 'negative';
                        const sign = isPositive ? '+' : '-';
                        return `<span class="transaction-amount ${amountClass}">${sign}${parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2})}</span>`;
                    }
                },
                {
                    data: 'currency',
                    render: function(data) {
                        return data || 'TRY';
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data, type, row) {
                        if (!data) return '-';
                        let editUrl = '/gelirgider/app/views/transactions/edit.php?id=' + data;
                        if (row.source_type === 'credit_card') {
                            editUrl = '/gelirgider/app/views/credit-cards/edit.php?id=' + data;
                        }
                        
                        return `
                            <div class="btn-group btn-group-sm">
                                <a href="${editUrl}" class="btn btn-outline-warning btn-sm" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteTransaction(${data}, '${row.source_type}')" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25,
            responsive: true,
            language: {
                "decimal": "",
                "emptyTable": "Tabloda herhangi bir veri mevcut değil",
                "info": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "infoEmpty": "Kayıt yok",
                "infoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
                "infoPostFix": "",
                "thousands": ".",
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
        showNotification('error', 'İşlemler yüklenirken hata oluştu: ' + error.message);
    }
    
    // Filter event listeners
    $('#sourceFilter, #typeFilter, #categoryFilter, #tagFilter, #dateFromFilter, #dateToFilter').on('change', function() {
        if (allTransactionsTable) {
            allTransactionsTable.ajax.reload();
        }
    });
}

function loadSummaryData() {
    $.ajax({
        url: '/gelirgider/app/controllers/TransactionController.php?action=getSummary',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                $('#monthlyIncome').text(parseFloat(data.monthly_income || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
                $('#monthlyExpense').text(parseFloat(data.monthly_expense || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
                
                const netAmount = (data.monthly_income || 0) - (data.monthly_expense || 0);
                const netClass = netAmount >= 0 ? 'text-success' : 'text-danger';
                $('#monthlyNet').html(`<span class="${netClass}">${netAmount.toLocaleString('tr-TR', {minimumFractionDigits: 2})} ₺</span>`);
                
                $('#totalTransactions').text((data.total_transactions || 0).toLocaleString('tr-TR'));
            }
        },
        error: function() {
            console.error('Summary data load error');
        }
    });
}

function refreshTransactions(showMessage = true) {
    if (allTransactionsTable) {
        allTransactionsTable.ajax.reload();
        loadSummaryData();
        if (showMessage) {
            showNotification('success', 'İşlemler yenilendi');
        }
    }
}

function clearFilters() {
    $('#sourceFilter, #typeFilter, #categoryFilter').val('');
    
    // Reset date filters to current month
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    $('#dateFromFilter').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#dateToFilter').val(lastDayOfMonth.toISOString().split('T')[0]);
    
    if (allTransactionsTable) {
        allTransactionsTable.ajax.reload();
    }
    showNotification('info', 'Filtreler temizlendi ve bu aya sıfırlandı');
}

function deleteTransaction(transactionId, sourceType) {
    if (confirm('Bu işlemi silmek istediğinizden emin misiniz?')) {
        const url = sourceType === 'wallet' 
            ? '/gelirgider/app/controllers/WalletController.php?action=deleteTransaction'
            : '/gelirgider/app/controllers/CreditCardController.php?action=deleteTransaction';
            
        $.ajax({
            url: url,
            type: 'POST',
            data: { id: transactionId, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'İşlem başarıyla silindi');
                    refreshTransactions(false); // showMessage = false
                    
                    // Uyarı gösterildikten sonra sayfa yenilenmesi - cüzdan bakiyelerini güncellemek için
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', 'İşlem silinirken hata oluştu');
            }
        });
    }
}

function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(notification);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}

function loadTags() {
    $.ajax({
        url: '/gelirgider/app/controllers/TagController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const tags = response.data || [];
                let filterOptions = '<option value="">Tüm Etiketler</option>';
                
                tags.forEach(function(tag) {
                    filterOptions += `<option value="${tag.id}">${tag.name}</option>`;
                });
                
                // Update tag filter
                $('#tagFilter').html(filterOptions);
            }
        },
        error: function() {
            console.error('Tags could not be loaded');
            showNotification('error', 'Etiketler yüklenirken hata oluştu');
        }
    });
} 