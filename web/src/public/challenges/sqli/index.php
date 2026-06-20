<?php

/**
 * Open (and lazily seed) the throwaway SQLite database for this challenge. A
 * local SQLite file is used instead of the platform MySQL database so that
 * destructive injection payloads can never reach or damage real CTF data.
 *
 * @param {string} $path Filesystem path of the SQLite database file.
 * @returns {PDO} Connection to the seeded users database.
 */
function open_user_db(string $path): PDO
{
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    $db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)');
    if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $db->exec("INSERT INTO users (username, password) VALUES ('admin', 'S3cr3tAdminP4ss!')");
    }
    return $db;
}

/**
 * Authenticate a user against the SQLite users table. This function is
 * INTENTIONALLY vulnerable to SQL injection: the query is assembled with raw
 * string concatenation and no escaping or prepared statements, which is the
 * objective of the challenge.
 *
 * @param {PDO} $db Open SQLite connection.
 * @param {string} $username Raw username supplied by the login form.
 * @param {string} $password Raw password supplied by the login form.
 * @returns {bool} True when the resulting query matches at least one row.
 */
function vulnerable_login(PDO $db, string $username, string $password): bool
{
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $db->query($query);
    return $result ? (bool) $result->fetch(PDO::FETCH_ASSOC) : false;
}

$FLAG = 'CTF{sql_1nj3ct10n_byp4ss_m4st3r}';

$loggedIn = false;
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = open_user_db(sys_get_temp_dir() . '/ctf_sqli_users.sqlite');
    $loggedIn = vulnerable_login($db, $username, $password);

    if (!$loggedIn) {
        $error = 'Login gagal. Coba pikirkan apa yang terjadi jika input mengandung tanda kutip.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge: Web SQL Injection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> * { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h1 class="text-xl font-bold text-gray-900">Acme Corp — Admin Login</h1>
                <p class="text-sm text-gray-500 mt-1">Restricted area. Authorized staff only.</p>
            </div>

            <?php if ($loggedIn): ?>
                <div class="p-6">
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                        <p class="font-semibold mb-1">✅ Login bypassed!</p>
                        <p class="text-sm">Berikut flag-nya:</p>
                        <code class="mt-2 block bg-white border border-green-200 rounded px-3 py-2 font-mono text-sm text-green-700"><?= htmlspecialchars($FLAG) ?></code>
                    </div>
                    <a href="../../index.php" class="mt-4 inline-block text-sm text-blue-600 hover:underline">← Kembali ke dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" class="p-6 space-y-4">
                    <?php if ($error): ?>
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" autocomplete="off"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="sqli-password" autocomplete="off"
                                   class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="button" onclick="togglePassword(this, 'sqli-password')" aria-label="Tampilkan atau sembunyikan password"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                        Masuk
                    </button>
                    <p class="text-xs text-gray-400 text-center pt-2">Tujuan: lewati autentikasi tanpa mengetahui password.</p>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        /**
         * Toggle a password field between hidden and visible and swap the eye icon.
         *
         * @param {HTMLButtonElement} btn The toggle button that was clicked.
         * @param {string} inputId The id of the password input to toggle.
         * @returns {void}
         */
        function togglePassword(btn, inputId) {
            const input = document.getElementById(inputId);
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            btn.innerHTML = '<i data-lucide="' + (reveal ? 'eye-off' : 'eye') + '" class="w-5 h-5"></i>';
            lucide.createIcons();
        }

        lucide.createIcons();
    </script>
</body>
</html>
