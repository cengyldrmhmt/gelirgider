<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <h2 class="mb-3">Kategori Ekle</h2>
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
                        <label class="form-label">Tip</label>
                        <select class="form-select" name="type" required>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Renk</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#0d6efd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İkon</label>
                        <input type="text" class="form-control" name="icon" placeholder="fa-shopping-cart, fa-home vb.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Üst Kategori (ID)</label>
                        <input type="text" class="form-control" name="parent_id" placeholder="Varsa üst kategori ID">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html> 