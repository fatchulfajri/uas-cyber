<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/session.php';

// Proteksi halaman
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'dosen') {
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#e74c3c;'>
            <h2>⚠️ Akses Ditolak</h2>
            <p>Halaman ini hanya dapat diakses oleh Dosen/Admin.</p>
          </div>";
    die();
}

// Handle tambah mahasiswa/dosen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_student') {
        $nim = $_POST['nim'];
        $nama = $_POST['nama'];
        $team = $_POST['team'];
        $password = $_POST['password'];

        $check = $conn->prepare("SELECT id FROM users WHERE nim = ?");
        $check->bind_param("s", $nim);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "NIM sudah terdaftar!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nim, nama, team, role, password) VALUES (?, ?, ?, 'mahasiswa', ?)");
            $stmt->bind_param("ssss", $nim, $nama, $team, $password);
            if ($stmt->execute()) {
                $success = "Mahasiswa berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan mahasiswa!";
            }
        }
    } elseif ($_POST['action'] === 'add_dosen') {
        $nim = $_POST['nim'];
        $nama = $_POST['nama'];
        $password = $_POST['password'];

        $check = $conn->prepare("SELECT id FROM users WHERE nim = ?");
        $check->bind_param("s", $nim);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "NIM sudah terdaftar!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nim, nama, team, role, password) VALUES (?, ?, 'DOSEN', 'dosen', ?)");
            $stmt->bind_param("sss", $nim, $nama, $password);
            if ($stmt->execute()) {
                $success = "Dosen berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan dosen!";
            }
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $user_id = $_POST['user_id'];
        $current_user_id = $_SESSION['user']['id'];

        if ($user_id == $current_user_id) {
            $error = "Tidak bisa menghapus akun sendiri!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success = "Akun berhasil dihapus!";
            } else {
                $error = "Gagal menghapus akun!";
            }
        }
    }
}

// Get all users for account management
$all_users = $conn->query("SELECT id, nim, nama, team, role FROM users ORDER BY role, nim");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - CTF Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-btn.active { background-color: white; color: #2563eb; border-bottom: 2px solid #2563eb; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Dashboard Dosen</h1>
                    <p class="text-xs text-gray-500">Dashboard Admin UAS Keamanan Siber</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-red-500 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="text-sm font-medium">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="mb-8">
            <?php
            $total_mahasiswa = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='mahasiswa'")->fetch_assoc()['c'];
            $total_dosen = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='dosen'")->fetch_assoc()['c'];
            $total_users = $total_mahasiswa + $total_dosen;

            // Score statistics
            $total_challenges = $conn->query("SELECT COUNT(*) as c FROM challenges")->fetch_assoc()['c'];
            $total_submissions = $conn->query("SELECT COUNT(*) as c FROM submissions")->fetch_assoc()['c'];
            $avg_score = $conn->query("SELECT AVG(score) as avg FROM submissions")->fetch_assoc()['avg'];
            $highest_score = $conn->query("SELECT MAX(total) as max FROM (SELECT SUM(score) as total FROM submissions GROUP BY user_id) as t")->fetch_assoc()['max'];
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Pengguna -->
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-50 rounded-lg">
                            <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $total_users ?></p>
                            <p class="text-sm text-gray-500">Total Pengguna</p>
                        </div>
                    </div>
                </div>

                <!-- Total Mahasiswa -->
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-50 rounded-lg">
                            <i data-lucide="graduation-cap" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $total_mahasiswa ?></p>
                            <p class="text-sm text-gray-500">Mahasiswa</p>
                        </div>
                    </div>
                </div>

                <!-- Total Dosen -->
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-50 rounded-lg">
                            <i data-lucide="user-cog" class="w-6 h-6 text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $total_dosen ?></p>
                            <p class="text-sm text-gray-500">Dosen</p>
                        </div>
                    </div>
                </div>

                <!-- Total Challenges -->
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-orange-50 rounded-lg">
                            <i data-lucide="flag" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $total_challenges ?></p>
                            <p class="text-sm text-gray-500">Challenges</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Score Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <!-- Total Submission -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm">Total Submission</p>
                            <p class="text-2xl font-bold mt-1"><?= $total_submissions ?></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <i data-lucide="send" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <!-- Rata-rata Poin -->
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm">Rata-rata Poin</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($avg_score ?? 0, 1) ?></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <i data-lucide="trending-up" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>

                <!-- Poin Tertinggi -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm">Poin Tertinggi</p>
                            <p class="text-2xl font-bold mt-1"><?= number_format($highest_score ?? 0) ?></p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-lg">
                            <i data-lucide="trophy" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex border-b border-gray-200">
                <button onclick="showTab('scoreboard')" class="tab-btn active flex-1 px-6 py-4 font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="trophy" class="w-5 h-5"></i>
                    Scoreboard
                </button>
                <button onclick="showTab('challenges')" class="tab-btn flex-1 px-6 py-4 font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="flag" class="w-5 h-5"></i>
                    Daftar Soal
                </button>
                <button onclick="showTab('accounts')" class="tab-btn flex-1 px-6 py-4 font-medium text-gray-500 hover:text-gray-700 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="users-cog" class="w-5 h-5"></i>
                    Manajemen Akun
                </button>
            </div>

            <!-- Scoreboard Tab -->
            <div id="scoreboard-tab" class="tab-content active p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Scoreboard Lengkap</h3>
                    <div class="flex gap-3">
                        <select id="teamFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Team</option>
                            <option value="A">Team A</option>
                            <option value="B">Team B</option>
                            <option value="C">Team C</option>
                            <option value="D">Team D</option>
                            <option value="E">Team E</option>
                            <option value="F">Team F</option>
                        </select>
                        <a href="export_csv.php" class="px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            Download Excel
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="scoreboardTable">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Peringkat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">NIM</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Team</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Solves</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="scoreboardBody">
                            <?php
                            $q = $conn->query("
                                SELECT u.nama, u.nim, u.team, SUM(s.score) as total_score, COUNT(s.challenge_id) as solves
                                FROM submissions s
                                JOIN users u ON s.user_id = u.id
                                WHERE u.role = 'mahasiswa'
                                GROUP BY u.id, u.nama, u.nim, u.team
                                ORDER BY total_score DESC, solves ASC
                            ");

                            $rank = 1;
                            while ($r = $q->fetch_assoc()):
                                $rank_badge = $rank;
                                if ($rank === 1) $rank_badge = '🥇';
                                elseif ($rank === 2) $rank_badge = '🥈';
                                elseif ($rank === 3) $rank_badge = '🥉';
                            ?>
                                <tr class="hover:bg-gray-50" data-team="<?= htmlspecialchars($r['team']) ?>">
                                    <td class="px-4 py-3 font-bold"><?= $rank_badge ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($r['nama']) ?></td>
                                    <td class="px-4 py-3 font-mono text-sm"><?= htmlspecialchars($r['nim']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-600 text-xs font-medium">
                                            <?= htmlspecialchars($r['team']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center"><?= $r['solves'] ?></td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-600"><?= number_format($r['total_score']) ?></td>
                                </tr>
                            <?php
                                $rank++;
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Challenges Tab -->
            <div id="challenges-tab" class="tab-content p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Soal CTF</h3>
                    <div class="flex gap-3">
                        <select id="categoryFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Kategori</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php
                    $challenges_list = $conn->query("SELECT * FROM challenges ORDER BY points ASC");
                    $categories = [
                        'easy' => ['name' => 'Easy', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-200'],
                        'medium' => ['name' => 'Medium', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200'],
                        'hard' => ['name' => 'Hard', 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'border' => 'border-red-200']
                    ];

                    while ($ch = $challenges_list->fetch_assoc()):
                        $cat = $categories[$ch['category']];
                        $solve_count = $conn->query("SELECT COUNT(*) as c FROM submissions WHERE challenge_id = " . $ch['id'])->fetch_assoc()['c'];
                    ?>
                        <div class="challenge-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all" data-category="<?= $ch['category'] ?>">
                            <!-- Card Header -->
                            <div class="p-5 border-b border-gray-100">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-2 py-1 rounded-md text-xs font-semibold <?= $cat['bg'] ?> <?= $cat['text'] ?>">
                                        <?= $cat['name'] ?>
                                    </span>
                                    <span class="px-2 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-700">
                                        <?= $ch['points'] ?> Poin
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($ch['title']) ?></h3>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($ch['description']) ?></p>
                            </div>

                            <!-- Card Stats -->
                            <div class="px-5 py-3 bg-gray-50 flex justify-between items-center text-sm">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="users" class="text-gray-400 w-4 h-4"></i>
                                    <span class="text-gray-600"><?= $solve_count ?> solves</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="flag" class="text-gray-400 w-4 h-4"></i>
                                    <span class="text-gray-600 font-mono text-xs">CTF{...}</span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Account Management Tab -->
            <div id="accounts-tab" class="tab-content p-6">
                <!-- Unified Add User Form -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                        Tambah Pengguna Baru
                    </h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                        <form method="POST" id="userForm">
                            <input type="hidden" name="action" id="actionField" value="add_student">

                            <!-- Role Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Pilih Role</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="role" value="mahasiswa" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500" onchange="toggleTeamField()">
                                        <span class="text-sm text-gray-700">Mahasiswa</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="role" value="dosen" class="w-4 h-4 text-purple-600 focus:ring-purple-500" onchange="toggleTeamField()">
                                        <span class="text-sm text-gray-700">Dosen</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">NIM</label>
                                    <input type="text" name="nim" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" name="nama" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Team Field (only for Mahasiswa) -->
                            <div class="mb-4" id="teamField">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                                <select name="team" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Team</option>
                                    <option value="A">Team A</option>
                                    <option value="B">Team B</option>
                                    <option value="C">Team C</option>
                                    <option value="D">Team D</option>
                                    <option value="E">Team E</option>
                                    <option value="F">Team F</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <button type="submit" id="submitBtn" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span id="submitText">Tambah Mahasiswa</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- User List -->
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-lucide="list" class="w-5 h-5"></i>
                        Daftar Semua Akun
                    </h3>

                    <!-- Filters -->
                    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
                        <div class="flex flex-wrap gap-4 items-center">
                            <!-- Search -->
                            <div class="flex-1 min-w-[200px]">
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    <input type="text" id="searchInput" placeholder="Cari NIM atau Nama..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Role Filter -->
                            <div>
                                <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Role</option>
                                    <option value="mahasiswa">Mahasiswa</option>
                                    <option value="dosen">Dosen</option>
                                </select>
                            </div>

                            <!-- Team Filter -->
                            <div>
                                <select id="teamFilterUser" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">Semua Team</option>
                                    <option value="A">Team A</option>
                                    <option value="B">Team B</option>
                                    <option value="C">Team C</option>
                                    <option value="D">Team D</option>
                                    <option value="E">Team E</option>
                                    <option value="F">Team F</option>
                                </select>
                            </div>

                            <!-- Reset Button -->
                            <button onclick="resetUserFilters()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                Reset
                            </button>
                        </div>

                        <!-- Result Count -->
                        <div class="mt-3 text-sm text-gray-500">
                            Menampilkan <span id="userCount" class="font-semibold text-gray-900">0</span> akun
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="w-full" id="userTable">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortUserTable('nim')">
                                        <div class="flex items-center gap-1">
                                            NIM
                                            <i data-lucide="chevrons-up-down" class="w-3 h-3"></i>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortUserTable('nama')">
                                        <div class="flex items-center gap-1">
                                            Nama
                                            <i data-lucide="chevrons-up-down" class="w-3 h-3"></i>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortUserTable('team')">
                                        <div class="flex items-center gap-1">
                                            Team
                                            <i data-lucide="chevrons-up-down" class="w-3 h-3"></i>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100 transition-colors" onclick="sortUserTable('role')">
                                        <div class="flex items-center gap-1">
                                            Role
                                            <i data-lucide="chevrons-up-down" class="w-3 h-3"></i>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="userTableBody">
                                <?php
                                $current_user_id = $_SESSION['user']['id'];
                                $all_users->data_seek(0);
                                while ($u = $all_users->fetch_assoc()):
                                    $is_current = $u['id'] == $current_user_id;
                                ?>
                                    <tr class="hover:bg-gray-50"
                                        data-nim="<?= htmlspecialchars(strtolower($u['nim'])) ?>"
                                        data-nama="<?= htmlspecialchars(strtolower($u['nama'])) ?>"
                                        data-role="<?= htmlspecialchars($u['role']) ?>"
                                        data-team="<?= htmlspecialchars($u['team']) ?>">
                                        <td class="px-4 py-3 font-mono text-sm"><?= htmlspecialchars($u['nim']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($u['nama']) ?></td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $u['role'] === 'dosen' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600' ?>">
                                                <?= htmlspecialchars($u['team']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $u['role'] === 'dosen' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' ?>">
                                                <?= htmlspecialchars($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if (!$is_current): ?>
                                                <form method="POST" onsubmit="return confirm('Hapus akun ini?')" class="inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">Anda</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Team filter
        document.getElementById('teamFilter').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('#scoreboardBody tr');

            rows.forEach(row => {
                if (filter === '' || row.dataset.team === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Toggle team field and update submit button based on role
        function toggleTeamField() {
            const role = document.querySelector('input[name="role"]:checked').value;
            const teamField = document.getElementById('teamField');
            const teamSelect = document.querySelector('select[name="team"]');
            const actionField = document.getElementById('actionField');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');

            if (role === 'mahasiswa') {
                teamField.style.display = 'block';
                teamSelect.required = true;
                actionField.value = 'add_student';
                submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                submitText.textContent = 'Tambah Mahasiswa';
            } else {
                teamField.style.display = 'none';
                teamSelect.required = false;
                teamSelect.value = '';
                actionField.value = 'add_dosen';
                submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700');
                submitText.textContent = 'Tambah Dosen';
            }
        }

        // User table filtering and sorting
        let currentSort = { column: null, direction: 'asc' };
        let originalRows = [];

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleTeamField();
            initUserTable();
        });

        function initUserTable() {
            const tableBody = document.getElementById('userTableBody');
            originalRows = Array.from(tableBody.querySelectorAll('tr'));
            updateUserCount();

            // Add event listeners for filters
            document.getElementById('searchInput').addEventListener('input', filterUserTable);
            document.getElementById('roleFilter').addEventListener('change', filterUserTable);
            document.getElementById('teamFilterUser').addEventListener('change', filterUserTable);
        }

        function filterUserTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const teamFilter = document.getElementById('teamFilterUser').value;
            const tableBody = document.getElementById('userTableBody');

            let visibleCount = 0;
            originalRows.forEach(row => {
                const nim = row.dataset.nim;
                const nama = row.dataset.nama;
                const role = row.dataset.role;
                const team = row.dataset.team;

                const matchesSearch = nim.includes(search) || nama.includes(search);
                const matchesRole = !roleFilter || role === roleFilter;
                const matchesTeam = !teamFilter || team === teamFilter;

                if (matchesSearch && matchesRole && matchesTeam) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateUserCount(visibleCount);
        }

        function sortUserTable(column) {
            const tableBody = document.getElementById('userTableBody');
            const rows = Array.from(tableBody.querySelectorAll('tr'));

            // Toggle direction if clicking same column
            if (currentSort.column === column) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = column;
                currentSort.direction = 'asc';
            }

            rows.sort((a, b) => {
                let aVal, bVal;

                switch(column) {
                    case 'nim':
                        aVal = a.dataset.nim;
                        bVal = b.dataset.nim;
                        break;
                    case 'nama':
                        aVal = a.dataset.nama;
                        bVal = b.dataset.nama;
                        break;
                    case 'team':
                        aVal = a.dataset.team;
                        bVal = b.dataset.team;
                        break;
                    case 'role':
                        aVal = a.dataset.role;
                        bVal = b.dataset.role;
                        break;
                }

                if (currentSort.direction === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });

            // Reorder rows in DOM
            rows.forEach(row => tableBody.appendChild(row));
        }

        function resetUserFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('roleFilter').value = '';
            document.getElementById('teamFilterUser').value = '';
            filterUserTable();
        }

        function updateUserCount(count = null) {
            if (count === null) {
                count = originalRows.length;
            }
            document.getElementById('userCount').textContent = count;
        }

        // Category filter for challenges
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('.challenge-card');

            cards.forEach(card => {
                if (filter === '' || card.dataset.category === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
