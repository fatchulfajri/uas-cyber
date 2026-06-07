<?php
include 'config.php';
session_start();

$error = '';

// Cek apakah user di-logout karena timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Session Anda telah berakhir karena tidak aktif selama 5 menit. Silakan login kembali.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim  = $_POST['nim'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE nim = ? AND password = ?");
    $stmt->bind_param("ss", $nim, $pass);
    $stmt->execute();
    $q = $stmt->get_result();

    if ($q && $q->num_rows > 0) {
        $_SESSION['user'] = $q->fetch_assoc();
        header("Location: index.php");
        exit;
    } else {
        $error = "NIM atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CTF Competition</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-section h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .logo-section p {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .login-header {
            margin-bottom: 24px;
        }

        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .login-header p {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message::before {
            content: "⚠";
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            font-size: 0.95rem;
            font-family: inherit;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #111827;
            transition: all 0.2s;
            background: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: #3b82f6;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 12px;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .register-link {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }

            .logo-section h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    
    <div class="logo-section">
        <h2>Cyber Security CTF Platform</h2>
        <p>Ujian Akhir Semester MK Keamanan Siber</p>
    </div>

    <div class="login-card">
        <div class="login-header">
            <h2>Login</h2>
            <p>Masuk untuk memulai challenges</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-primary">Masuk</button>
        </form>

        <div class="divider">
            <span>atau</span>
        </div>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>

    <div class="footer">
        © 2026 UAS Keamanan Siber.
    </div>
</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>
