<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../controllers/ScheduledPaymentController.php';
require_once __DIR__ . '/../../controllers/CategoryController.php';
require_once __DIR__ . '/../../controllers/WalletController.php';

$scheduledPaymentController = new ScheduledPaymentController();
$categoryController = new CategoryController();
$walletController = new WalletController();

$payments = $scheduledPaymentController->index();

include '../layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planlı Ödemeler</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/gelirgider/public/css/scheduled_payments/style.css">
</head>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Planlanan Ödemeler</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fas fa-plus"></i> Yeni Planlanan Ödeme
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Açıklama</th>
                                    <th>Kategori</th>
                                    <th>Cüzdan</th>
                                    <th>Tür</th>
                                    <th>Tutar</th>
                                    <th>Sıklık</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Son İşlem</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['description']) ?></td>
                                    <td><?= htmlspecialchars($payment['category_name']) ?></td>
                                    <td><?= htmlspecialchars($payment['wallet_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $payment['type'] === 'income' ? 'success' : 'danger' ?>">
                                            <?= $payment['type'] === 'income' ? 'Gelir' : 'Gider' ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($payment['amount'], 2) ?> <?= $payment['currency'] ?></td>
                                    <td><?= ucfirst($payment['frequency']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($payment['start_date'])) ?></td>
                                    <td><?= $payment['end_date'] ? date('d.m.Y', strtotime($payment['end_date'])) : '-' ?></td>
                                    <td><?= $payment['last_processed_date'] ? date('d.m.Y', strtotime($payment['last_processed_date'])) : '-' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $payment['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $payment['is_active'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info edit-payment" 
                                                    data-id="<?= $payment['id'] ?>"
                                                    data-description="<?= htmlspecialchars($payment['description']) ?>"
                                                    data-category-id="<?= $payment['category_id'] ?>"
                                                    data-wallet-id="<?= $payment['wallet_id'] ?>"
                                                    data-type="<?= $payment['type'] ?>"
                                                    data-amount="<?= $payment['amount'] ?>"
                                                    data-frequency="<?= $payment['frequency'] ?>"
                                                    data-start-date="<?= $payment['start_date'] ?>"
                                                    data-end-date="<?= $payment['end_date'] ?>"
                                                    data-is-active="<?= $payment['is_active'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-<?= $payment['is_active'] ? 'warning' : 'success' ?> toggle-payment" 
                                                    data-id="<?= $payment['id'] ?>"
                                                    data-is-active="<?= $payment['is_active'] ?>">
                                                <i class="fas fa-<?= $payment['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-payment" data-id="<?= $payment['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Planlanan Ödeme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPaymentForm">
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id" required>
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tür</label>
                        <select class="form-select" name="type" required>
                            <option value="expense">Gider</option>
                            <option value="income">Gelir</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sıklık</label>
                        <select class="form-select" name="frequency" required>
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly">Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="savePayment">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Planlanan Ödeme Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cüzdan</label>
                        <select class="form-select" name="wallet_id" required>
                            <option value="">Seçiniz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tür</label>
                        <select class="form-select" name="type" required>
                            <option value="expense">Gider</option>
                            <option value="income">Gelir</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sıklık</label>
                        <select class="form-select" name="frequency" required>
                            <option value="daily">Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly">Aylık</option>
                            <option value="yearly">Yıllık</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="updatePayment">Güncelle</button>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="/gelirgider/public/js/scheduled_payments/script.js"></script>
</body>
</html> 