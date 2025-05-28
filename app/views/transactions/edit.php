<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$controller = new TransactionController();

// POST isteği ise güncelleme yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST verilerine ID'yi ekle
    $_POST['id'] = $id;
    $_POST['transaction_date'] = $_POST['date'] . ' ' . date('H:i:s'); // Date'i datetime'a çevir
    $controller->updateTransaction();
    exit; // updateTransaction redirect veya JSON döndürür
} else {
    // GET isteği ise form verilerini al
    $data = $controller->edit($id);
}

include '../layouts/header.php';
include '../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">İşlem Düzenle</h3>
                    <div class="card-tools">
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $data['transaction']['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>İşlem Tipi <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="income" <?= $data['transaction']['type'] == 'income' ? 'selected' : '' ?>>Gelir</option>
                                        <option value="expense" <?= $data['transaction']['type'] == 'expense' ? 'selected' : '' ?>>Gider</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tutar <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" 
                                           value="<?= $data['transaction']['amount'] ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                                        <?php foreach ($data['categories'] as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= $data['transaction']['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cüzdan <span class="text-danger">*</span></label>
                                    <select name="wallet_id" class="form-control" required>
                                        <option value="">Cüzdan Seçin</option>
                                        <?php foreach ($data['wallets'] as $wallet): ?>
                                            <option value="<?= $wallet['id'] ?>"
                                                    <?= $data['transaction']['wallet_id'] == $wallet['id'] ? 'selected' : '' ?>
                                                    <?= $wallet['is_default'] ? 'data-default="true"' : '' ?>>
                                                <?= htmlspecialchars($wallet['name']) ?> 
                                                (<?= $wallet['currency'] ?>) - 
                                                Bakiye: <?= number_format($wallet['real_balance'], 2) ?> <?= $wallet['currency'] ?>
                                                <?= $wallet['is_default'] ? ' - Varsayılan' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tarih <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control" 
                                           value="<?= date('Y-m-d', strtotime($data['transaction']['transaction_date'])) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Açıklama</label>
                                    <input type="text" name="description" class="form-control" 
                                           value="<?= htmlspecialchars($data['transaction']['description'] ?? '') ?>"
                                           placeholder="İşlem açıklaması">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Etiketler Bölümü -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Etiketler</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <select id="tagSelect" class="form-control me-2" style="max-width: 300px;">
                                            <option value="">Etiket Seçin</option>
                                            <?php foreach ($data['tags'] as $tag): ?>
                                                <option value="<?= $tag['id'] ?>" data-color="<?= $tag['color'] ?>">
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTag()">
                                            <i class="fas fa-plus"></i> Ekle
                                        </button>
                                    </div>
                                    <div id="selectedTags" class="d-flex flex-wrap gap-2">
                                        <!-- Seçili etiketler burada görünecek -->
                                        <?php foreach ($data['transaction_tags'] as $tag): ?>
                                            <span class="badge badge-tag" style="background-color: <?= $tag['color'] ?>; color: white;" data-id="<?= $tag['id'] ?>">
                                                <?= htmlspecialchars($tag['name']) ?>
                                                <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeTag(<?= $tag['id'] ?>)"></button>
                                                <input type="hidden" name="tags[]" value="<?= $tag['id'] ?>">
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Güncelle
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> İptal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-tag {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    border-radius: 1rem;
    display: inline-flex;
    align-items: center;
}

.badge-tag .btn-close {
    font-size: 0.75rem;
    margin-left: 0.25rem;
}
</style>

<script>
function addTag() {
    const select = document.getElementById('tagSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const tagId = selectedOption.value;
        const tagName = selectedOption.text;
        const tagColor = selectedOption.dataset.color || '#007bff';
        
        // Etiket zaten seçili mi kontrol et
        if (document.querySelector(`#selectedTags [data-id="${tagId}"]`)) {
            alert('Bu etiket zaten seçili!');
            return;
        }
        
        // Yeni etiket badge'i oluştur
        const badge = document.createElement('span');
        badge.className = 'badge badge-tag';
        badge.style.backgroundColor = tagColor;
        badge.style.color = 'white';
        badge.setAttribute('data-id', tagId);
        badge.innerHTML = `
            ${tagName}
            <button type="button" class="btn-close btn-close-white btn-sm ms-1" onclick="removeTag(${tagId})"></button>
            <input type="hidden" name="tags[]" value="${tagId}">
        `;
        
        document.getElementById('selectedTags').appendChild(badge);
        select.selectedIndex = 0; // Seçimi sıfırla
    }
}

function removeTag(tagId) {
    const badge = document.querySelector(`#selectedTags [data-id="${tagId}"]`);
    if (badge) {
        badge.remove();
    }
}

// Varsayılan cüzdan seçimi
document.addEventListener('DOMContentLoaded', function() {
    const walletSelect = document.querySelector('select[name="wallet_id"]');
    if (walletSelect && !walletSelect.value) {
        const defaultWallet = walletSelect.querySelector('option[data-default="true"]');
        if (defaultWallet) {
            defaultWallet.selected = true;
        }
    }
});
</script>

<?php include '../layouts/footer.php'; ?> 