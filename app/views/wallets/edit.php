<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cüzdan Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-3">Cüzdan Düzenle</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::generate()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($wallet['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tür</label>
                        <select class="form-select" name="type" required>
                            <option value="cash" <?= $wallet['type']==='cash'?'selected':'' ?>>Nakit</option>
                            <option value="bank" <?= $wallet['type']==='bank'?'selected':'' ?>>Banka</option>
                            <option value="credit_card" <?= $wallet['type']==='credit_card'?'selected':'' ?>>Kredi Kartı</option>
                            <option value="saving" <?= $wallet['type']==='saving'?'selected':'' ?>>Tasarruf</option>
                            <option value="investment" <?= $wallet['type']==='investment'?'selected':'' ?>>Yatırım</option>
                            <option value="crypto" <?= $wallet['type']==='crypto'?'selected':'' ?>>Kripto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" class="form-control" name="currency" value="<?= htmlspecialchars($wallet['currency']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bakiye</label>
                        <input type="number" step="0.01" class="form-control" name="balance" value="<?= htmlspecialchars($wallet['balance']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="<?= htmlspecialchars($wallet['color']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <input type="text" class="form-control" name="icon" value="<?= htmlspecialchars($wallet['icon']) ?>" placeholder="fa-wallet, fa-bank vb.">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 