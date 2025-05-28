<?php
// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eğer kullanıcı giriş yapmamışsa sidebar'ı gösterme
if (!isset($_SESSION['user_id'])) {
    return;
}

// Mevcut sayfayı al
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/dashboard/index.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'transactions' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/transactions/index.php">
                    <i class="fas fa-exchange-alt"></i> İşlemler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'categories' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/categories/index.php">
                    <i class="fas fa-tags"></i> Kategoriler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'wallets' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/wallets/index.php">
                    <i class="fas fa-wallet"></i> Cüzdanlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'credit-cards' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/credit-cards/index.php">
                    <i class="fas fa-credit-card"></i> Kredi Kartları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'reports' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/reports/index.php">
                    <i class="fas fa-chart-bar"></i> Raporlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'analytics' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/analytics/index.php">
                    <i class="fas fa-chart-line"></i> Analizler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'financial_goals' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/financial_goals/index.php">
                    <i class="fas fa-bullseye"></i> Finansal Hedefler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'scheduled_payments' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/scheduled_payments/index.php">
                    <i class="fas fa-calendar-alt"></i> Planlanan Ödemeler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'payment_plans' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/payment_plans/index.php">
                    <i class="fas fa-calendar-check"></i> Gelişmiş Ödeme Planları
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'budgets' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/budgets/index.php">
                    <i class="fas fa-piggy-bank"></i> Bütçeler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'tags' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/tags/index.php">
                    <i class="fas fa-tag"></i> Etiketler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'profile' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/profile/index.php">
                    <i class="fas fa-user"></i> Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'settings' ? 'active' : ''; ?>" 
                   href="/gelirgider/app/views/settings/index.php">
                    <i class="fas fa-cog"></i> Ayarlar
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #ecf0f1;
    padding: 0.5rem 1rem;
    margin: 0.2rem 0;
    border-radius: 0.25rem;
}

.sidebar .nav-link:hover {
    color: #3498db;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link.active {
    color: #3498db;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link i {
    margin-right: 0.5rem;
    width: 1.25rem;
    text-align: center;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
        padding-top: 0;
    }
}
</style>

<!-- Main Content Wrapper -->
<div style="margin-left: 250px; padding: 20px;"> 