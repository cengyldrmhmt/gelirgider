<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new BudgetController();
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
                    <h3 class="card-title">Yeni Bütçe Ekle</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form action="/gelirgider/app/views/budgets/add.php" method="POST">
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="category_id" class="form-control">
                                <option value="">Tüm Kategoriler</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cüzdan</label>
                            <select name="wallet_id" class="form-control">
                                <option value="">Tüm Cüzdanlar</option>
                                <?php foreach ($wallets as $wallet): ?>
                                <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Bütçe Tutarı</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Periyot</label>
                            <select name="period" class="form-control" required>
                                <option value="daily">Günlük</option>
                                <option value="weekly">Haftalık</option>
                                <option value="monthly">Aylık</option>
                                <option value="yearly">Yıllık</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Başlangıç Tarihi</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Bitiş Tarihi</label>
                            <input type="date" name="end_date" class="form-control">
                            <small class="form-text text-muted">Opsiyonel. Boş bırakılırsa süresiz olarak ayarlanır.</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="/gelirgider/app/views/budgets/index.php" class="btn btn-secondary">İptal</a>
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