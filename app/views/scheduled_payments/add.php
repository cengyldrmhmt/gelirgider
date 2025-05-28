<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/ScheduledPaymentController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new ScheduledPaymentController();
$data = $controller->add();
$categories = $data['categories'];
$wallets = $data['wallets'];
$error = $data['error'] ?? null;

require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Yeni Planlanan Ödeme</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="/gelirgider/app/views/scheduled_payments/add.php" method="POST">
                        <div class="form-group">
                            <label>Açıklama</label>
                            <input type="text" class="form-control" name="description" required>
                        </div>

                        <div class="form-group">
                            <label>Tür</label>
                            <select class="form-control" name="type" required>
                                <option value="income">Gelir</option>
                                <option value="expense">Gider</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <select class="form-control" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cüzdan</label>
                            <select class="form-control" name="wallet_id" required>
                                <?php foreach ($wallets as $wallet): ?>
                                <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tutar</label>
                            <input type="number" class="form-control" name="amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Para Birimi</label>
                            <select class="form-control" name="currency" required>
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Sıklık</label>
                            <select class="form-control" name="frequency" required>
                                <option value="daily">Günlük</option>
                                <option value="weekly">Haftalık</option>
                                <option value="monthly">Aylık</option>
                                <option value="yearly">Yıllık</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label>Bitiş Tarihi (Opsiyonel)</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="isActive" name="is_active" checked>
                                <label class="custom-control-label" for="isActive">Aktif</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="/gelirgider/app/views/scheduled_payments/index.php" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../views/layouts/footer.php';
?> 