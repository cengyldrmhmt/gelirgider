<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cüzdan Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-3">Cüzdan Ekle</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::generate()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tür</label>
                        <select class="form-select" name="type" required>
                            <option value="cash">Nakit</option>
                            <option value="bank">Banka</option>
                            <option value="credit_card">Kredi Kartı</option>
                            <option value="saving">Tasarruf</option>
                            <option value="investment">Yatırım</option>
                            <option value="crypto">Kripto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Para Birimi</label>
                        <input type="text" class="form-control" name="currency" value="TRY" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bakiye</label>
                        <input type="number" step="0.01" class="form-control" name="balance" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#0d6efd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <input type="text" class="form-control" name="icon" placeholder="fa-wallet, fa-bank vb.">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 