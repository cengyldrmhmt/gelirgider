<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/FinancialGoalController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new FinancialGoalController();
$data = $controller->add();

if (isset($data['error'])) {
    $error = $data['error'];
}

$categories = $data['categories'] ?? [];
$wallets = $data['wallets'] ?? [];

require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Yeni Finansal Hedef</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="/gelirgider/app/views/financial_goals/add.php" method="POST">
                        <div class="form-group">
                            <label>Hedef Adı</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <select class="form-control" name="category_id" required>
                                <option value="">Kategori Seçin</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cüzdan</label>
                            <select class="form-control" name="wallet_id" required>
                                <option value="">Cüzdan Seçin</option>
                                <?php foreach ($wallets as $wallet): ?>
                                    <option value="<?= $wallet['id'] ?>"><?= htmlspecialchars($wallet['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Hedef Tutar</label>
                            <input type="number" class="form-control" name="target_amount" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Mevcut Tutar</label>
                            <input type="number" class="form-control" name="current_amount" step="0.01" value="0">
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
                            <label>Hedef Tarih</label>
                            <input type="date" class="form-control" name="target_date" required>
                        </div>

                        <div class="form-group">
                            <label>Açıklama</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="/gelirgider/app/views/financial_goals/index.php" class="btn btn-secondary">İptal</a>
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