// Veritabanı yedeği alma fonksiyonu
function backupDatabase() {
    if (confirm('Veritabanı yedeği almak istediğinizden emin misiniz?')) {
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php?action=backupDatabase',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    toastr.success('Veritabanı yedeği başarıyla alındı');
                    // Yedeği indir
                    window.location.href = response.download_url;
                } else {
                    toastr.error(response.message || 'Veritabanı yedeği alınırken bir hata oluştu');
                }
            },
            error: function() {
                toastr.error('Veritabanı yedeği alınırken bir hata oluştu');
            }
        });
    }
}

// Sistem temizliği işlemini başlatan fonksiyon
function cleanupSystem() {
    if (confirm('Sistem temizliği yapmak istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
        $.ajax({
            url: '/gelirgider/app/controllers/AdminController.php?action=cleanupSystem',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    toastr.success('Sistem temizliği başarıyla tamamlandı');
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Sistem temizliği yapılırken bir hata oluştu');
                }
            },
            error: function() {
                toastr.error('Sistem temizliği yapılırken bir hata oluştu');
            }
        });
    }
}

// Kullanıcılar sekmesini gösteren fonksiyon
function showUsers() {
    $('#users-tab').tab('show');
}

// Ayarlar sekmesini gösteren fonksiyon
function showSettings() {
    $('#settings-tab').tab('show');
}

// Sistem bilgisi sekmesini gösteren fonksiyon
function showSystem() {
    $('#system-tab').tab('show');
}

// Dashboard verilerini yenileyen fonksiyon
function refreshDashboard() {
    $.ajax({
        url: '/gelirgider/app/controllers/AdminController.php?action=getDashboardData',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                // İstatistikleri güncelle
                updateStatistics(response.data);
                toastr.success('Dashboard başarıyla yenilendi');
            } else {
                toastr.error(response.message || 'Dashboard yenilenirken bir hata oluştu');
            }
        },
        error: function() {
            toastr.error('Dashboard yenilenirken bir hata oluştu');
        }
    });
}

// Dashboard istatistik kartlarını güncelleyen fonksiyon
function updateStatistics(data) {
    // Toplam kullanıcı
    $('.stat-card:nth-child(1) h3').text(data.total_users.toLocaleString());
    $('.stat-card:nth-child(1) small').text('Bugün: ' + data.new_users_today);
    
    // Toplam işlem
    $('.stat-card:nth-child(2) h3').text(data.total_transactions.toLocaleString());
    $('.stat-card:nth-child(2) small').text('KK: ' + data.total_cc_transactions.toLocaleString());
    
    // Toplam cüzdan
    $('.stat-card:nth-child(3) h3').text(data.total_wallets.toLocaleString());
    $('.stat-card:nth-child(3) small').text(data.total_wallet_balance.toLocaleString() + ' ₺');
    
    // Kredi kartı
    $('.stat-card:nth-child(4) h3').text(data.total_credit_cards.toLocaleString());
    $('.stat-card:nth-child(4) small').text('Kategori: ' + data.total_categories.toLocaleString());
} 