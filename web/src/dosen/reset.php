<?php
include '../config.php';
include '../session.php';

// Proteksi halaman
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'dosen') {
    die("Akses ditolak");
}

$conn->query("TRUNCATE submissions");
echo "RESET BERHASIL";