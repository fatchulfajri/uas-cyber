<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/session.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_team = $user['team'];

// Get scoreboard data - individual rankings by team
$q = $conn->query("
    SELECT u.nim, SUM(s.score) as total_score, COUNT(s.challenge_id) as solves
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE u.role = 'mahasiswa' AND u.team = '$user_team'
    GROUP BY u.id, u.nim
    ORDER BY total_score DESC, solves ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard - CTF Competition</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Title -->
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Student Scoreboard</h1>
                    <p class="text-xs text-gray-500">Papan Skor UAS Keamanan Siber</p>
                </div>

                <!-- Back Button -->
                <a href="index.php" class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-blue-600 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Kembali</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Papan Peringkat</h2>
            <p class="text-gray-500">Daftar peringkat peserta berdasarkan poin yang diperoleh</p>
        </div>

        <!-- Scoreboard Table -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">NIM</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Solves</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Poin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    $has_data = false;
                    while ($r = $q->fetch_assoc()):
                        $has_data = true;
                        $nim = htmlspecialchars($r['nim']);
                        $total = number_format($r['total_score']);
                        $solves = $r['solves'];
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-mono text-gray-900"><?= $nim ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-sm font-medium">
                                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                    <?= $solves ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-lg font-bold text-blue-600"><?= $total ?></span>
                            </td>
                        </tr>
                    <?php
                    endwhile;

                    if (!$has_data):
                    ?>
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                <i data-lucide="trophy" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                <p class="text-lg font-medium">Belum ada data</p>
                                <p class="text-sm">Jadilah yang pertama menyelesaikan challenge!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-400">
        © 2026 UAS Keamanan Siber.
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
