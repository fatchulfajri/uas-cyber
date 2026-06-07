<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout settings
$session_timeout = 300; // 5 menit dalam detik

// Cek apakah user sudah login
if (isset($_SESSION['user'])) {
    // Cek last activity time
    if (isset($_SESSION['last_activity'])) {
        // Hitung selis waktu sejak aktivitas terakhir
        $inactive_time = time() - $_SESSION['last_activity'];

        // Jika melebihi timeout, logout user
        if ($inactive_time > $session_timeout) {
            session_unset();
            session_destroy();
            // Redirect ke login dengan pesan timeout
            header("Location: login.php?timeout=1");
            exit;
        }
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
} elseif (!isset($_SESSION['last_activity'])) {
    // Set last activity untuk pertama kali
    $_SESSION['last_activity'] = time();
}
?>
