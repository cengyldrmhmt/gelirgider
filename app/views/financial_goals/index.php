<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../controllers/FinancialGoalController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$financialGoalController = new FinancialGoalController();
$categoryController = new CategoryController();
$walletController = new WalletController();

$goals = $financialGoalController->index();

// Get categories and wallets data properly
$categoryData = $categoryController->index();
$categories = $categoryData['categories'] ?? [];

$walletData = $walletController->index();
$wallets = $walletData['wallets'] ?? [];

include '../layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finansal Hedefler</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Finansal Hedefler</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                            <i class="fas fa-plus"></i> Yeni Hedef
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($goals as $goal): ?>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><?php echo htmlspecialchars($goal['title']); ?></h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item edit-goal" href="#" 
                                               data-id="<?php echo $goal['id']; ?>"
                                               data-title="<?php echo htmlspecialchars($goal['title']); ?>"
                                               data-description="<?php echo htmlspecialchars($goal['description']); ?>"
                                               data-target-amount="<?php echo $goal['target_amount']; ?>"
                                               data-current-amount="<?php echo $goal['current_amount']; ?>"
                                               data-target-date="<?php echo $goal['target_date']; ?>"
                                               data-category-id="<?php echo $goal['category_id']; ?>"
                                               data-wallet-id="<?php echo $goal['wallet_id']; ?>"
                                               data-status="<?php echo $goal['status']; ?>">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <a class="dropdown-item delete-goal" href="#" data-id="<?php echo $goal['id']; ?>">
                                                <i class="fas fa-trash"></i> Sil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted"><?php echo htmlspecialchars($goal['description']); ?></p>
                                    <div class="progress mb-3">
                                        <?php
                                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                                        $progress = min(100, max(0, $progress));
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%">
                                            <?php echo number_format($progress, 1); ?>%
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Hedef</small>
                                            <p class="mb-0"><?php echo number_format($goal['target_amount'], 2); ?> <?php echo $goal['currency']; ?></p>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Mevcut</small>
                                            <p class="mb-0"><?php echo number_format($goal['current_amount'], 2); ?> <?php echo $goal['currency']; ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">Hedef Tarih</small>
                                        <p class="mb-0"><?php echo date('d.m.Y', strtotime($goal['target_date'])); ?></p>
                                    </div>
                                    <?php if ($goal['category_name']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Kategori</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($goal['category_name']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($goal['wallet_name']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Cüzdan</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($goal['wallet_name']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Durum</small>
                                        <p class="mb-0">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($goal['status']) {
                                                case 'completed':
                                                    $statusClass = 'success';
                                                    $statusText = 'Tamamlandı';
                                                    break;
                                                case 'in_progress':
                                                    $statusClass = 'primary';
                                                    $statusText = 'Devam Ediyor';
                                                    break;
                                                default:
                                                    $statusClass = 'warning';
                                                    $statusText = 'Planlandı';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Finansal Hedef</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addGoalForm">
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef Tutar</label>
                        <input type="number" class="form-control" name="target_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mevcut Tutar</label>
                        <input type="number" class="form-control" name="current_amount" step="0.01" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef Tarih</label>
                        <input type="date" class="form-control" name="target_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Cüzdan Seçin (İsteğe Bağlı)</option>
                            <?php foreach ($wallets as $wallet): ?>
                                <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?> (<?= $wallet['currency'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status">
                            <option value="planned">Planlandı</option>
                            <option value="in_progress">Devam Ediyor</option>
                            <option value="completed">Tamamlandı</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveGoal">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Goal Modal -->
<div class="modal fade" id="editGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finansal Hedef Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editGoalForm">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Başlık</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef Tutar</label>
                        <input type="number" class="form-control" name="target_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mevcut Tutar</label>
                        <input type="number" class="form-control" name="current_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hedef Tarih</label>
                        <input type="date" class="form-control" name="target_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id">
                            <option value="">Kategori Seçin (İsteğe Bağlı)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id">
                            <option value="">Cüzdan Seçin (İsteğe Bağlı)</option>
                            <?php foreach ($wallets as $wallet): ?>
                                <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?> (<?= $wallet['currency'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status">
                            <option value="planned">Planlandı</option>
                            <option value="in_progress">Devam Ediyor</option>
                            <option value="completed">Tamamlandı</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="updateGoal">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="/gelirgider/public/js/financial_goals/script.js"></script>

<?php include '../layouts/footer.php'; ?> 