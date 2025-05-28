<?php
session_start();
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/FinancialGoalController.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /gelirgider/app/views/auth/login.php');
    exit;
}

$controller = new FinancialGoalController();
$data = $controller->edit();

if (isset($data['error'])) {
    $error = $data['error'];
}

$goal = $data['goal'] ?? null;
$categories = $data['categories'] ?? [];
$wallets = $data['wallets'] ?? [];

if (!$goal) {
    header('Location: /gelirgider/app/views/financial_goals/index.php');
    exit;
}

require_once __DIR__ . '/../../views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Finansal Hedef Düzenle</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form action="/gelirgider/app/views/financial_goals/edit.php?id=<?= $goal['id'] ?>" method="POST">
                        <div class="form-group">
                            <label>Hedef Adı</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($goal['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <select class="form-control" name="category_id" required>
                                <option value="">Kategori Seçin</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $category['id'] == $goal['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cüzdan</label>
                            <select class="form-control" name="wallet_id" required>
                                <option value="">Cüzdan Seçin</option>
                                <?php foreach ($wallets as $wallet): ?>
                                    <option value="<?= $wallet['id'] ?>" <?= $wallet['id'] == $goal['wallet_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wallet['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Hedef Tutar</label>
                            <input type="number" class="form-control" name="target_amount" step="0.01" value="<?= $goal['target_amount'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Mevcut Tutar</label>
                            <input type="number" class="form-control" name="current_amount" step="0.01" value="<?= $goal['current_amount'] ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Para Birimi</label>
                            <select class="form-control" name="currency" required>
                                <option value="TRY" <?= $goal['currency'] == 'TRY' ? 'selected' : '' ?>>TRY</option>
                                <option value="USD" <?= $goal['currency'] == 'USD' ? 'selected' : '' ?>>USD</option>
                                <option value="EUR" <?= $goal['currency'] == 'EUR' ? 'selected' : '' ?>>EUR</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Hedef Tarih</label>
                            <input type="date" class="form-control" name="target_date" value="<?= $goal['target_date'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Açıklama</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($goal['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Güncelle</button>
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