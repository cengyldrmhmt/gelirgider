<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

// Debug için hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Dosya yollarını kontrol edelim
$basePath = __DIR__ . '/../../';
require_once $basePath . 'core/Database.php';
require_once $basePath . 'controllers/WalletController.php';
require_once $basePath . 'models/Category.php';
require_once $basePath . 'models/ExchangeRate.php';
require_once __DIR__ . '/../layouts/header.php';

$controller = new WalletController();
$data = $controller->index();

$categoryModel = new Category();
$categories = $categoryModel->getAll($_SESSION['user_id']);

$exchangeRateModel = new ExchangeRate();

include '../layouts/sidebar.php';

// CSS dosyasını ekle (JS dosyasını kaldırıyoruz)
echo '<link rel="stylesheet" href="/gelirgider/public/css/wallets/style.css">';
?>

<!-- Include Toastr CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<!-- Custom CSS -->
<style>
.wallet-item {
    transition: transform 0.2s;
}

.wallet-item:hover {
    transform: translateY(-5px);
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

/* DataTable custom styles */
#walletTransactionsTable {
    font-size: 0.875rem;
}

#walletTransactionsTable th {
    background-color: #f8f9fc;
    border-color: #e3e6f0;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #5a5c69;
}

.transaction-amount {
    font-weight: 600;
}

.transaction-amount.positive {
    color: #1cc88a;
}

.transaction-amount.negative {
    color: #e74a3b;
}

.gap-2 {
    gap: 0.5rem;
}

/* Dropdown z-index fix */
.dropdown-menu {
    z-index: 1051 !important;
}

.dropdown-toggle {
    z-index: 1050 !important;
}

.card .dropdown {
    position: relative;
    z-index: 1;
}

.exchange-rate {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.exchange-rate .rate {
    color: #28a745;
    font-weight: 600;
}
</style>

<script>
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

// jQuery yüklendiğinden emin ol
$(document).ready(function() {
    console.log('Wallet page ready, initializing...');
    
    // Initialize DataTable
    initializeWalletTransactionsTable();
    
    // Transaction type change handler for category filtering
    $('#editTransactionType').on('change', function() {
        const selectedType = $(this).val();
        const categorySelect = $('#editTransactionCategory');
        
        // Show all options first
        categorySelect.find('option').show();
        
        // Hide options that don't match the selected type
        if (selectedType) {
            categorySelect.find('option[data-type]').each(function() {
                const optionType = $(this).data('type');
                if (optionType && optionType !== selectedType) {
                    $(this).hide();
                }
            });
        }
        
        // Reset selection if current selection is hidden
        const currentSelection = categorySelect.val();
        if (currentSelection) {
            const currentOption = categorySelect.find('option[value="' + currentSelection + '"]');
            if (currentOption.is(':hidden')) {
                categorySelect.val('');
            }
        }
    });
});

let walletTransactionsTable;

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
                    data: 'balance_after',
                    render: function(data, type, row) {
                        if (data === null || data === undefined) return '-';
                        return `${parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${row.currency || 'TRY'}`;
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

function refreshTransactions(showMessage = true) {
    if (walletTransactionsTable) {
        walletTransactionsTable.ajax.reload();
        if (showMessage) {
            showNotification('success', 'İşlemler yenilendi');
        }
    }
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
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=get',
        type: 'GET',
        data: { id: walletId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const wallet = response.data;
                
                $('#editWalletId').val(wallet.id);
                $('#editWalletName').val(wallet.name);
                $('#editWalletCurrency').val(wallet.currency);
                $('#editWalletBalance').val(wallet.balance);
                $('#editWalletType').val(wallet.type || 'cash');
                $('#editWalletColor').val(wallet.color);
                $('#editWalletIcon').val(wallet.icon);
                
                $('#editWalletModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan bilgileri yüklenirken hata oluştu.');
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
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Cüzdan güncellenirken bir hata oluştu.');
        }
    });
}

function deleteWallet(walletId) {
    // Find wallet name from DOM
    const walletCard = document.querySelector(`[onclick="deleteWallet(${walletId})"]`).closest('.card');
    const walletName = walletCard.querySelector('.card-title').textContent.trim();
    
    // Store wallet info
    walletToDelete = {
        id: walletId,
        name: walletName
    };
    
    // Set wallet name in modal
    $('#deleteWalletName').text(walletName);
    
    // Show delete confirmation modal
    $('#deleteWalletModal').modal('show');
}

// Handle wallet delete confirmation
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

function depositMoney(walletId) {
    $('#depositWalletId').val(walletId);
    $('#depositModal').modal('show');
}

function saveDeposit() {
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
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Para yatırma işlemi sırasında hata oluştu.');
        }
    });
}

function withdrawMoney(walletId) {
    $('#withdrawWalletId').val(walletId);
    $('#withdrawModal').modal('show');
}

function saveWithdraw() {
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
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Para çekme işlemi sırasında hata oluştu.');
        }
    });
}

function transferMoney(sourceWalletId) {
    $('#transferSourceWalletId').val(sourceWalletId);
    
    // Remove source wallet from target options
    $('#transferModal select[name="target_wallet_id"] option').show();
    $('#transferModal select[name="target_wallet_id"] option[value="' + sourceWalletId + '"]').hide();
    
    $('#transferModal').modal('show');
}

function saveTransfer() {
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
                location.reload();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Transfer işlemi sırasında hata oluştu.');
        }
    });
}

function viewTransactions(walletId) {
    $('#walletFilter').val(walletId);
    walletTransactionsTable.ajax.reload();
    
    // Scroll to transactions table
    $('html, body').animate({
        scrollTop: $('#walletTransactionsTable').offset().top - 100
    }, 1000);
    
    showNotification('info', 'İşlemler filtrelendi ve tabloya yönlendirildi.');
}

function editTransaction(transactionId) {
    $.ajax({
        url: '/gelirgider/app/controllers/TransactionController.php?action=get&id=' + transactionId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const transaction = response.data;
                
                $('#editTransactionId').val(transaction.id);
                $('#editTransactionWallet').val(transaction.wallet_id);
                $('#editTransactionCategory').val(transaction.category_id);
                $('#editTransactionType').val(transaction.type);
                $('#editTransactionAmount').val(transaction.amount);
                $('#editTransactionDescription').val(transaction.description);
                $('#editTransactionDate').val(transaction.transaction_date.split(' ')[0]);
                $('#editTransactionNotes').val(transaction.notes);
                
                $('#editTransactionModal').modal('show');
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'İşlem bilgileri yüklenirken hata oluştu.');
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
                walletTransactionsTable.ajax.reload();
                // Reload page to update balances
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'İşlem güncellenirken hata oluştu.');
        }
    });
}

function deleteTransaction(transactionId) {
    if (confirm('Bu işlemi silmek istediğinizden emin misiniz?')) {
        $.ajax({
            url: '/gelirgider/app/controllers/WalletController.php?action=deleteTransaction',
            type: 'POST',
            data: { id: transactionId, ajax: '1' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'İşlem başarıyla silindi');
                    walletTransactionsTable.ajax.reload();
                                    // Reload page to update balances
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
</script>

<?php include '../layouts/footer.php'; ?> 