// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Hızlı İşlemler Modal İşlemleri
    const quickIncomeModal = document.getElementById('quickIncomeModal');
    const quickExpenseModal = document.getElementById('quickExpenseModal');

    if (quickIncomeModal) {
        quickIncomeModal.addEventListener('show.bs.modal', function() {
            // Modal açıldığında yapılacak işlemler
        });
    }

    if (quickExpenseModal) {
        quickExpenseModal.addEventListener('show.bs.modal', function() {
            // Modal açıldığında yapılacak işlemler
        });
    }

    // Akıllı Öneriler Animasyonu
    const suggestions = document.querySelectorAll('#smartSuggestions .alert');
    suggestions.forEach((suggestion, index) => {
        suggestion.style.opacity = '0';
        suggestion.style.transform = 'translateY(20px)';
        setTimeout(() => {
            suggestion.style.transition = 'all 0.3s ease-out';
            suggestion.style.opacity = '1';
            suggestion.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Kartlar için hover efekti
    const cards = document.querySelectorAll('.hover-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

function showNotification(type, message) {
    // Simple notification system
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(notification);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}

function exportChart() {
    // Chart export functionality
    showNotification('info', 'Grafik indirme özelliği yakında eklenecek.');
}

// Document ready function
$(document).ready(function() {
    // Load wallets
    loadWallets();
    
    // Load wallet options for quick forms
    loadWalletOptions();
    
    // Load categories for quick forms
    loadCategoryOptions();
    
    // Load tags for quick forms
    loadTagOptions();
    
    // Initialize charts
    initializeCharts();
    
    // Load dashboard data on page load
    refreshDashboardData();
    
    // Auto-refresh data every 30 seconds
    setInterval(function() {
        refreshDashboardData();
    }, 30000);
});

function loadWallets() {
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayWallets(response.data);
            }
        },
        error: function() {
            $('#walletsContainer').html('<p class="text-muted">Cüzdanlar yüklenemedi</p>');
        }
    });
}

function displayWallets(wallets) {
    let html = '';
    wallets.forEach(function(wallet) {
        const balanceClass = wallet.real_balance >= 0 ? 'text-success' : 'text-danger';
        
        // Get currency symbol
        let currencySymbol = '₺';
        switch(wallet.currency) {
            case 'USD':
                currencySymbol = '$';
                break;
            case 'EUR':
                currencySymbol = '€';
                break;
            case 'GBP':
                currencySymbol = '£';
                break;
            case 'TRY':
            default:
                currencySymbol = '₺';
                break;
        }
        
        html += `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div>
                    <strong>${wallet.name}</strong>
                    <br>
                    <small class="text-muted">${wallet.currency}</small>
                </div>
                <div class="text-end">
                    <span class="${balanceClass}">${parseFloat(wallet.real_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${currencySymbol}</span>
                </div>
            </div>
        `;
    });
    $('#walletsContainer').html(html);
}

function loadWalletOptions() {
    $.ajax({
        url: '/gelirgider/app/controllers/WalletController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Cüzdan Seçin</option>';
                response.data.forEach(function(wallet) {
                    options += `<option value="${wallet.id}">${wallet.name} (${wallet.currency})</option>`;
                });
                $('#quickIncomeForm select[name="wallet_id"]').html(options);
                $('#quickExpenseForm select[name="wallet_id"]').html(options);
            }
        }
    });
}

function loadCategoryOptions() {
    $.ajax({
        url: '/gelirgider/app/controllers/CategoryController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let incomeOptions = '<option value="">Kategori Seçin (İsteğe Bağlı)</option>';
                let expenseOptions = '<option value="">Kategori Seçin (İsteğe Bağlı)</option>';
                
                response.data.forEach(function(category) {
                    const option = `<option value="${category.id}">${category.name}</option>`;
                    if (category.type === 'income') {
                        incomeOptions += option;
                    } else if (category.type === 'expense') {
                        expenseOptions += option;
                    }
                });
                
                $('#quickIncomeForm select[name="category_id"]').html(incomeOptions);
                $('#quickExpenseForm select[name="category_id"]').html(expenseOptions);
            }
        }
    });
}

function loadTagOptions() {
    $.ajax({
        url: '/gelirgider/app/controllers/TagController.php?action=getAll',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '';
                response.data.forEach(function(tag) {
                    options += `<option value="${tag.id}">${tag.name}</option>`;
                });
                $('#quickIncomeTransactionTags, #quickExpenseTransactionTags').html(options);
                
                // Initialize Select2
                $('#quickIncomeTransactionTags, #quickExpenseTransactionTags').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Etiket seçin (İsteğe bağlı)',
                    allowClear: true
                });
            }
        }
    });
}

function saveQuickTransaction(type) {
    const formId = type === 'income' ? '#quickIncomeForm' : '#quickExpenseForm';
    const formData = new FormData($(formId)[0]);
    formData.append('ajax', '1');
    
    // Determine the correct URL based on transaction type
    const url = type === 'income' 
        ? '/gelirgider/app/controllers/WalletController.php?action=deposit'
        : '/gelirgider/app/controllers/WalletController.php?action=withdraw';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $(formId.replace('Form', 'Modal')).modal('hide');
                $(formId)[0].reset();
                
                // Reset Select2 fields
                $('#quickIncomeTransactionTags, #quickExpenseTransactionTags').val([]).trigger('change');
                
                // Show success message
                showNotification('success', 'İşlem başarıyla eklendi!');
                
                // Refresh dashboard data
                refreshDashboardData();
            } else {
                showNotification('error', response.message || 'Bir hata oluştu.');
            }
        },
        error: function() {
            showNotification('error', 'İşlem eklenirken bir hata oluştu.');
        }
    });
}

function refreshDashboardData() {
    // Refresh summary cards
    $.ajax({
        url: '/gelirgider/app/controllers/DashboardController.php?action=getSummary',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#monthlyIncome').text(parseFloat(response.data.total_income).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
                $('#monthlyExpense').text(parseFloat(response.data.total_expense).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
                $('#totalBalance').text(parseFloat(response.data.net_balance).toLocaleString('tr-TR', {minimumFractionDigits: 2}) + ' ₺');
            }
        }
    });
    
    // Refresh wallets
    loadWallets();
}

function initializeCharts() {
    // Monthly Trend Chart
    const ctx1 = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz'],
            datasets: [{
                label: 'Gelir',
                data: [12000, 15000, 13000, 17000, 14000, 16000],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.3
            }, {
                label: 'Gider',
                data: [8000, 9000, 10000, 11000, 9500, 10500],
                borderColor: '#e74a3b',
                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('tr-TR') + ' ₺';
                        }
                    }
                }
            }
        }
    });

    // Category Chart
    const ctx2 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Market', 'Faturalar', 'Ulaşım', 'Eğlence', 'Diğer'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
} 