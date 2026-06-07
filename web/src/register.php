<?php
include 'config.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $team = $_POST['team'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($nim) || empty($nama) || empty($team) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!in_array($team, ['A', 'B', 'C', 'D', 'E', 'F'])) {
        $error = "Pilih team yang valid!";
    } else {
        // Check if NIM already exists
        $check = $conn->prepare("SELECT id FROM users WHERE nim = ?");
        $check->bind_param("s", $nim);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "NIM sudah terdaftar!";
        } else {
            // Insert new user with role 'mahasiswa'
            $stmt = $conn->prepare("INSERT INTO users (nim, nama, team, role, password) VALUES (?, ?, ?, 'mahasiswa', ?)");
            $stmt->bind_param("ssss", $nim, $nama, $team, $password);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
                // Clear form values
                $nim = $nama = $team = $password = '';
            } else {
                $error = "Registrasi gagal! Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - CTF Competition</title>
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

        .register-container {
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

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.875rem;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #3b82f6;
        }

        .register-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .register-header {
            margin-bottom: 24px;
        }

        .register-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }

        .register-header p {
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

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .success-message::before {
            content: "✓";
            font-size: 1.1rem;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
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

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder,
        .form-group select:placeholder-shown {
            color: #9ca3af;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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
            margin-top: 6px;
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

        .login-link {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .login-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 32px;
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .team-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .team-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            background: #f3f4f6;
            border-radius: 20px;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .logo-section h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo-section">
        <h1>🎯 CTF Competition</h1>
        <p>Cyber Security Challenge Platform</p>
    </div>

    <a href="login.php" class="back-link">← Kembali ke Login</a>

    <div class="register-card">
        <div class="register-header">
            <h2>Daftar</h2>
            <p>Buat akun untuk bergabung</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nim">NIM</label>
                <input type="text" id="nim" name="nim" placeholder="Masukkan NIM" required autocomplete="username" value="<?php echo htmlspecialchars($nim ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required autocomplete="name" value="<?php echo htmlspecialchars($nama ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="team">Team</label>
                    <select id="team" name="team" required>
                        <option value="">Pilih Team</option>
                        <option value="A" <?php echo (isset($team) && $team === 'A') ? 'selected' : ''; ?>>Team A</option>
                        <option value="B" <?php echo (isset($team) && $team === 'B') ? 'selected' : ''; ?>>Team B</option>
                        <option value="C" <?php echo (isset($team) && $team === 'C') ? 'selected' : ''; ?>>Team C</option>
                        <option value="D" <?php echo (isset($team) && $team === 'D') ? 'selected' : ''; ?>>Team D</option>
                        <option value="E" <?php echo (isset($team) && $team === 'E') ? 'selected' : ''; ?>>Team E</option>
                        <option value="F" <?php echo (isset($team) && $team === 'F') ? 'selected' : ''; ?>>Team F</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Buat password" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-primary">Daftar Sekarang</button>
        </form>

        <div class="divider">
            <span>atau</span>
        </div>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <div class="footer">
        © 2026 CTF Competition. All rights reserved.
    </div>
</div>

</body>
</html>
