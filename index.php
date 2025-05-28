<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: app/views/auth/login.php');
    exit;
}

// If logged in, redirect to dashboard
header('Location: app/views/dashboard/index.php');
exit; 