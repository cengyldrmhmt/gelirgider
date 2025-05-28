<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/CreditCardController.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Wallet.php';
require_once __DIR__ . '/../../models/Tag.php';

$creditCardController = new CreditCardController();
$categoryModel = new Category();
$walletModel = new Wallet();
$tagModel = new Tag();

// Kredi kartı ID'sini al
$cardId = isset($_GET['card_id']) ? (int)$_GET['card_id'] : 0;

if ($cardId <= 0) {
    header('Location: /gelirgider/app/views/credit-cards/index.php');
    exit;
}

// Kredi kartı bilgilerini al
$creditCard = $creditCardController->getCreditCard($cardId);
if (!$creditCard || $creditCard['user_id'] != $_SESSION['user_id']) {
    header('Location: /gelirgider/app/views/credit-cards/index.php');
    exit;
}

$categories = $categoryModel->getAll($_SESSION['user_id']);
$wallets = $walletModel->getAll($_SESSION['user_id']);
$tags = $tagModel->getAll($_SESSION['user_id']);

// Header'ı dahil et
include __DIR__ . '/../layouts/header.php';

// Sidebar'ı dahil et
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<!-- Credit Card Transaction Add Content -->
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shopping-cart text-primary"></i> Harcama Ekle
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/gelirgider/app/views/dashboard/index.php">Ana Sayfa</a></li>
                    <li class="breadcrumb-item"><a href="/gelirgider/app/views/credit-cards/index.php">Kredi Kartları</a></li>
                    <li class="breadcrumb-item active">Harcama Ekle</li>
                </ol>
            </nav>
        </div>
        <a href="/gelirgider/app/views/credit-cards/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
    </div>

    <!-- Credit Card Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow" style="border-left: 4px solid <?php echo $creditCard['color']; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3" style="background-color: <?php echo $creditCard['color']; ?>">
                            <i class="fas fa-credit-card text-white"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($creditCard['name']); ?></h5>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($creditCard['bank_name'] ?? ''); ?>
                                <?php if ($creditCard['card_number_last4']): ?>
                                    **** <?php echo $creditCard['card_number_last4']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="ms-auto">
                            <div class="text-end">
                                <div class="text-muted small">Kullanılabilir Limit</div>
                                <div class="h5 text-success mb-0">
                                    <?php echo number_format($creditCard['real_available_limit'], 2); ?> ₺
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Transaction Form -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus"></i> Yeni Harcama Ekle
                    </h6>
                </div>
                <div class="card-body">
                    <form id="addTransactionForm" method="POST" action="/gelirgider/app/controllers/CreditCardController.php?action=addTransaction">
                        <input type="hidden" name="credit_card_id" value="<?php echo $cardId; ?>">
                        <input type="hidden" name="type" value="purchase">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tutar *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="amount" step="0.01" required>
                                    <span class="input-group-text">₺</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İşlem Tarihi</label>
                                <input type="datetime-local" class="form-control" name="transaction_date" value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="description" placeholder="Harcama açıklaması">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mağaza/İşyeri</label>
                            <input type="text" class="form-control" name="merchant_name" placeholder="Harcama yapılan yer">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Para Birimi</label>
                                <select class="form-select" name="currency">
                                    <option value="TRY" selected>TRY</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Taksit Sayısı</label>
                            <div class="row">
                                <div class="col-md-8">
                                    <select class="form-select" name="installment_count" id="installmentSelect">
                                        <option value="1">Peşin</option>
                                        <option value="2">2 Taksit</option>
                                        <option value="3">3 Taksit</option>
                                        <option value="4">4 Taksit</option>
                                        <option value="6">6 Taksit</option>
                                        <option value="8">8 Taksit</option>
                                        <option value="9">9 Taksit</option>
                                        <option value="12">12 Taksit</option>
                                        <option value="18">18 Taksit</option>
                                        <option value="24">24 Taksit</option>
                                        <option value="custom">Manuel Giriş</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control d-none" name="custom_installment" id="customInstallment" min="1" max="60" placeholder="Taksit sayısı">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ödeme Cüzdanı</label>
                            <select class="form-select" name="payment_wallet_id">
                                <option value="">Ödeme zamanı geldiğinde seçilecek</option>
                                <?php foreach ($wallets as $wallet): ?>
                                    <option value="<?php echo $wallet['id']; ?>">
                                        <?php echo htmlspecialchars($wallet['name']); ?> 
                                        (<?php echo number_format($wallet['real_balance'], 2); ?> <?php echo $wallet['currency']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Taksitli ödemelerde her taksit bu cüzdandan çekilecek (İsteğe Bağlı)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tag'ler</label>
                            <select class="form-select" name="tags[]" multiple>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo $tag['id']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Birden fazla tag seçebilirsiniz (İsteğe Bağlı)</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/gelirgider/app/views/credit-cards/index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Harcamayı Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
.avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Taksit seçimi için custom input gösterme/gizleme
    const installmentSelect = document.getElementById('installmentSelect');
    const customInstallment = document.getElementById('customInstallment');
    
    installmentSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customInstallment.classList.remove('d-none');
            customInstallment.required = true;
        } else {
            customInstallment.classList.add('d-none');
            customInstallment.required = false;
            customInstallment.value = '';
        }
    });
    
    // Form submit işlemi
    document.getElementById('addTransactionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Custom installment kontrolü
        if (installmentSelect.value === 'custom') {
            formData.set('installment_count', customInstallment.value);
        }
        
        fetch('/gelirgider/app/controllers/CreditCardController.php?action=addTransaction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Harcama başarıyla eklendi!');
                window.location.href = '/gelirgider/app/views/credit-cards/index.php';
            } else {
                alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?> 