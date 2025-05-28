// Configure Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Global variables
let walletToDelete = null;
let walletTransactionsTable;

$(document).ready(function() {
    console.log('Wallet page ready, initializing...');
    
    // Initialize DataTable
    initializeWalletTransactionsTable();
});

function initializeWalletTransactionsTable() {
    console.log('Initializing Wallet Transactions DataTable...');
    
    if ($.fn.DataTable.isDataTable('#walletTransactionsTable')) {
        $('#walletTransactionsTable').DataTable().destroy();
    }
    
    try {
        walletTransactionsTable = $('#walletTransactionsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/gelirgider/app/controllers/WalletController.php?action=getAllTransactions',
                type: 'GET',
                data: function(d) {
                    d.wallet_id = $('#walletFilter').val();
                    d.type = $('#typeFilter').val();
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
                    data: 'wallet_name',
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `<div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2" style="background-color: ${row.wallet_color || '#007bff'}">
                                <i class="fas fa-${row.wallet_icon || 'wallet'} text-white"></i>
                            </div>
                            <span>${data}</span>
                        </div>`;
                    }
                },
                {
                    data: 'type',
                    render: function(data) {
                        const types = {
                            'income': { text: 'Gelir', class: 'bg-success' },
                            'expense': { text: 'Gider', class: 'bg-danger' },
                            'transfer': { text: 'Transfer', class: 'bg-info' }
                        };
                        const type = types[data] || { text: data || '-', class: 'bg-secondary' };
                        return `<span class="badge ${type.class}">${type.text}</span>`;
                    }
                },
                {
                    data: 'category_name',
                    render: function(data) {
                        return data || '-';
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
                        const isPositive = row.type === 'income';
                        const amountClass = isPositive ? 'positive' : 'negative';
                        const sign = isPositive ? '+' : '-';
                        return `<span class="transaction-amount ${amountClass}">${sign}${parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${row.currency || 'TRY'}</span>`;
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data, type, row) {
                        if (!data) return '-';
                        return `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-warning btn-sm" onclick="editTransaction(${data})" title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteTransaction(${data})" title="Sil">
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
    $('#walletFilter, #typeFilter').on('change', function() {
        if (walletTransactionsTable) {
            walletTransactionsTable.ajax.reload();
        }
    });
}

function saveWallet() {
    const formData = new FormData(document.getElementById('addWalletForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=create',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addWalletModal').modal('hide');
                showNotification('success', 'Cüzdan başarıyla eklendi');
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan eklenirken bir hata oluştu.');
        }
    });
}

function editWallet(walletId) {
    console.log('Editing wallet:', walletId);
    
    // Cüzdan bilgilerini al
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=get',
        type: 'GET',
        data: { id: walletId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const wallet = response.data;
                
                // Form alanlarını doldur
                $('#editWalletId').val(wallet.id);
                $('#editWalletName').val(wallet.name);
                $('#editWalletCurrency').val(wallet.currency);
                $('#editWalletBalance').val(wallet.real_balance);
                $('#editWalletType').val(wallet.type || 'cash');
                $('#editWalletColor').val(wallet.color || '#007bff');
                $('#editWalletIcon').val(wallet.icon || 'wallet');
                
                // Modal'ı göster
                $('#editWalletModal').modal('show');
            } else {
                showNotification('error', response.message || 'Cüzdan bilgileri alınırken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan bilgileri alınırken hata oluştu');
        }
    });
}

function updateWallet() {
    const formData = new FormData(document.getElementById('editWalletForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editWalletModal').modal('hide');
                showNotification('success', 'Cüzdan başarıyla güncellendi');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan güncellenirken bir hata oluştu.');
        }
    });
}

function depositMoney(walletId) {
    console.log('Deposit money to wallet:', walletId);
    
    // Cüzdan bilgilerini al
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=get',
        type: 'GET',
        data: { id: walletId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const wallet = response.data;
                
                // Form alanlarını doldur
                $('#depositWalletId').val(wallet.id);
                
                // Cüzdan adını para birimi ile birlikte göster
                let walletDisplayName = wallet.name + ' (' + wallet.currency + ')';
                
                // Eğer dövizli cüzdan ise kur bilgisini de ekle
                if (wallet.currency !== 'TRY') {
                    // Kur bilgisini sayfadan al (exchange rate badge'lerinden)
                    const exchangeRateElement = $(`[data-wallet-id="${walletId}"] .exchange-rate-info`);
                    if (exchangeRateElement.length > 0) {
                        const rateText = exchangeRateElement.text().trim();
                        if (rateText) {
                            walletDisplayName += ' - ' + rateText;
                        }
                    }
                }
                
                $('#depositWalletName').val(walletDisplayName);
                $('#depositCurrency').text(wallet.currency);
                
                // Form'u temizle
                $('#depositForm')[0].reset();
                $('#depositWalletId').val(wallet.id);
                $('#depositWalletName').val(walletDisplayName);
                
                // Modal'ı göster
                $('#depositModal').modal('show');
            } else {
                showNotification('error', response.message || 'Cüzdan bilgileri alınırken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan bilgileri alınırken hata oluştu');
        }
    });
}

function processDeposit() {
    const formData = new FormData(document.getElementById('depositForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=deposit',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#depositModal').modal('hide');
                showNotification('success', 'Para başarıyla yatırıldı');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Para yatırma işlemi başarısız oldu.');
        }
    });
}

function withdrawMoney(walletId) {
    console.log('Withdraw money from wallet:', walletId);
    
    // Cüzdan bilgilerini al
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=get',
        type: 'GET',
        data: { id: walletId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const wallet = response.data;
                
                // Form alanlarını doldur
                $('#withdrawWalletId').val(wallet.id);
                
                // Cüzdan adını para birimi ile birlikte göster
                let walletDisplayName = wallet.name + ' (' + wallet.currency + ')';
                
                // Eğer dövizli cüzdan ise kur bilgisini de ekle
                if (wallet.currency !== 'TRY') {
                    // Kur bilgisini sayfadan al
                    const exchangeRateElement = $(`[data-wallet-id="${walletId}"] .exchange-rate-info`);
                    if (exchangeRateElement.length > 0) {
                        const rateText = exchangeRateElement.text().trim();
                        if (rateText) {
                            walletDisplayName += ' - ' + rateText;
                        }
                    }
                }
                
                $('#withdrawWalletName').val(walletDisplayName);
                $('#withdrawCurrentBalance').val(parseFloat(wallet.real_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + wallet.currency);
                $('#withdrawCurrency').text(wallet.currency);
                
                // Form'u temizle
                $('#withdrawForm')[0].reset();
                $('#withdrawWalletId').val(wallet.id);
                $('#withdrawWalletName').val(walletDisplayName);
                $('#withdrawCurrentBalance').val(parseFloat(wallet.real_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + wallet.currency);
                
                // Modal'ı göster
                $('#withdrawModal').modal('show');
            } else {
                showNotification('error', response.message || 'Cüzdan bilgileri alınırken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan bilgileri alınırken hata oluştu');
        }
    });
}

function processWithdraw() {
    const formData = new FormData(document.getElementById('withdrawForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=withdraw',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#withdrawModal').modal('hide');
                showNotification('success', 'Para başarıyla çekildi');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Para çekme işlemi başarısız oldu.');
        }
    });
}

function transferMoney(sourceWalletId) {
    console.log('Transfer money from wallet:', sourceWalletId);
    
    // Cüzdan bilgilerini al
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=get',
        type: 'GET',
        data: { id: sourceWalletId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const wallet = response.data;
                
                // Form alanlarını doldur
                $('#transferSourceWalletId').val(wallet.id);
                
                // Kaynak cüzdan adını para birimi ile birlikte göster
                let walletDisplayName = wallet.name + ' (' + wallet.currency + ')';
                
                // Eğer dövizli cüzdan ise kur bilgisini de ekle
                if (wallet.currency !== 'TRY') {
                    const exchangeRateElement = $(`[data-wallet-id="${sourceWalletId}"] .exchange-rate-info`);
                    if (exchangeRateElement.length > 0) {
                        const rateText = exchangeRateElement.text().trim();
                        if (rateText) {
                            walletDisplayName += ' - ' + rateText;
                        }
                    }
                }
                
                $('#transferSourceWalletName').val(walletDisplayName);
                $('#transferCurrentBalance').val(parseFloat(wallet.real_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + wallet.currency);
                $('#transferCurrency').text(wallet.currency);
                
                // Hedef cüzdan seçeneklerini güncelle - kaynak cüzdanı gizle
                $('#transferTargetWallet option').each(function() {
                    const optionValue = $(this).val();
                    if (optionValue == sourceWalletId) {
                        $(this).hide().prop('disabled', true);
                    } else {
                        $(this).show().prop('disabled', false);
                    }
                });
                
                // İlk seçeneği (boş) seç
                $('#transferTargetWallet').val('');
                
                // Form'u temizle ama değerleri koru
                $('#transferForm')[0].reset();
                $('#transferSourceWalletId').val(wallet.id);
                $('#transferSourceWalletName').val(walletDisplayName);
                $('#transferCurrentBalance').val(parseFloat(wallet.real_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ' + wallet.currency);
                
                // Modal'ı göster
                $('#transferModal').modal('show');
            } else {
                showNotification('error', response.message || 'Cüzdan bilgileri alınırken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan bilgileri alınırken hata oluştu');
        }
    });
}

function processTransfer() {
    const formData = new FormData(document.getElementById('transferForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=transfer',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#transferModal').modal('hide');
                showNotification('success', 'Transfer başarıyla tamamlandı');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Transfer işlemi başarısız oldu.');
        }
    });
}

function viewTransactions(walletId) {
    $('#walletFilter').val(walletId);
    if (walletTransactionsTable) {
        walletTransactionsTable.ajax.reload();
    }
    
    // Scroll to transactions table
    $('html, body').animate({
        scrollTop: $('#walletTransactionsTable').offset().top - 100
    }, 1000);
    
    showNotification('info', 'İşlemler filtrelendi ve tabloya yönlendirildi.');
}

function editTransaction(transactionId) {
    console.log('Editing transaction:', transactionId);
    
    // İşlem bilgilerini al
    $.ajax({
        url: '/gelirgider/app/controllers/TransactionController.php?action=get',
        type: 'GET',
        data: { id: transactionId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const transaction = response.data;
                
                // Form alanlarını doldur
                $('#editTransactionId').val(transaction.id);
                $('#editTransactionWalletId').val(transaction.wallet_id);
                $('#editTransactionType').val(transaction.type);
                $('#editTransactionAmount').val(transaction.amount);
                $('#editTransactionDescription').val(transaction.description);
                $('#editTransactionCategoryId').val(transaction.category_id || '');
                
                // Tarih formatını düzenle
                if (transaction.transaction_date) {
                    const date = new Date(transaction.transaction_date);
                    const formattedDate = date.toISOString().slice(0, 16);
                    $('#editTransactionDate').val(formattedDate);
                }
                
                // Modal'ı göster
                $('#editTransactionModal').modal('show');
            } else {
                showNotification('error', response.message || 'İşlem bilgileri alınırken hata oluştu');
            }
        },
        error: function() {
            showNotification('error', 'İşlem bilgileri alınırken hata oluştu');
        }
    });
}

function updateTransaction() {
    const formData = new FormData(document.getElementById('editTransactionForm'));
    formData.append('ajax', '1');
    
    $.ajax({
        url: '/gelirgider/app/controllers/TransactionController.php?action=update',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editTransactionModal').modal('hide');
                showNotification('success', 'İşlem başarıyla güncellendi');
                if (walletTransactionsTable) {
                    walletTransactionsTable.ajax.reload();
                }
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'İşlem güncellenirken bir hata oluştu.');
        }
    });
}

function deleteTransaction(transactionId) {
    // Global değişkene transaction ID'yi kaydet
    window.transactionToDelete = transactionId;
    
    // Modal'ı göster
    $('#deleteTransactionModal').modal('show');
}

function confirmDeleteTransaction() {
    if (window.transactionToDelete) {
        // Butonu disable et
        $('#confirmDeleteTransactionBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Siliniyor...');
        
        $.ajax({
            url: '/gelirgider/app/controllers/WalletController.php?action=deleteTransaction',
            type: 'POST',
            data: { id: window.transactionToDelete, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                $('#deleteTransactionModal').modal('hide');
                
                if (response.success) {
                    showNotification('success', 'İşlem başarıyla silindi');
                    if (walletTransactionsTable) {
                        walletTransactionsTable.ajax.reload();
                    }
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                $('#deleteTransactionModal').modal('hide');
                showNotification('error', 'İşlem silinirken hata oluştu');
            },
            complete: function() {
                // Butonu eski haline getir
                $('#confirmDeleteTransactionBtn').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
                window.transactionToDelete = null;
            }
        });
    }
}

function refreshTransactions(showMessage = true) {
    if (walletTransactionsTable) {
        walletTransactionsTable.ajax.reload();
        if (showMessage) {
            showNotification('success', 'İşlemler yenilendi');
        }
    }
}

function updateExchangeRates() {
    showNotification('info', 'Döviz kurları güncelleniyor...');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=updateExchangeRates',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                // Sayfayı yenile ki güncel kurlar görünsün
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', response.message || 'Kur güncelleme başarısız');
            }
        },
        error: function() {
            showNotification('error', 'Döviz kurları güncellenirken hata oluştu');
        }
    });
}

function forceUpdateExchangeRates() {
    showNotification('warning', 'Önbellek temizleniyor ve kurlar zorla güncelleniyor...');
    
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=forceUpdateExchangeRates',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Debug Info:', response.debug);
                showNotification('success', response.message + (response.debug ? ` (USD: ${response.debug.usd_rate})` : ''));
                // Sayfayı yenile ki güncel kurlar görünsün
                setTimeout(() => {
                    location.reload();
                }, 2500);
            } else {
                showNotification('error', response.message || 'Zorla kur güncelleme başarısız');
            }
        },
        error: function() {
            showNotification('error', 'Döviz kurları zorla güncellenirken hata oluştu');
        }
    });
}

// Delete wallet functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-wallet').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            // Store wallet info for deletion
            walletToDelete = {
                id: id,
                name: name
            };
            
            // Set wallet name in modal
            $('#deleteWalletName').text(name);
            
            // Show delete confirmation modal
            $('#deleteWalletModal').modal('show');
        });
    });
    
    // Handle delete confirmation
    $('#confirmDeleteWallet').on('click', function() {
        if (walletToDelete) {
            // Disable button to prevent double-click
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Siliniyor...');
            
            $.ajax({
                url: '/gelirgider/app/controllers/WalletController.php?action=delete',
                type: 'POST',
                data: { id: walletToDelete.id, ajax: '1' },
                dataType: 'json',
                success: function(response) {
                    $('#deleteWalletModal').modal('hide');
                    
                    if (response.success) {
                        showNotification('success', `"${walletToDelete.name}" cüzdanı başarıyla silindi`);
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', response.message);
                        $('#confirmDeleteWallet').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
                    }
                },
                error: function() {
                    $('#deleteWalletModal').modal('hide');
                    showNotification('error', 'Cüzdan silinirken hata oluştu');
                    $('#confirmDeleteWallet').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
                }
            });
        }
    });
    
    // Reset delete button when modal is hidden
    $('#deleteWalletModal').on('hidden.bs.modal', function() {
        $('#confirmDeleteWallet').prop('disabled', false).html('<i class="fas fa-trash"></i> Evet, Sil');
        walletToDelete = null;
    });
});

function showNotification(type, message) {
    // Show notification based on type
    switch(type) {
        case 'success':
            toastr.success(message, 'Başarılı');
            break;
        case 'error':
            toastr.error(message, 'Hata');
            break;
        case 'warning':
            toastr.warning(message, 'Uyarı');
            break;
        case 'info':
        default:
            toastr.info(message, 'Bilgi');
            break;
    }
} 