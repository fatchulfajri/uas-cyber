<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/session.php';

// Proteksi halaman
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'dosen') {
    die("Akses ditolak");
}

// Truncate submissions table
$conn->query("TRUNCATE submissions");

// Redirect back to dashboard with success message
header("Location: dashboard.php?reset=1");
exit;
?>
