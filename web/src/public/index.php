<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/session.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

// Get user's solved challenges
$user_id = $user['id'];
$solved_challenges = [];
$result = $conn->query("SELECT challenge_id FROM submissions WHERE user_id = $user_id");
while ($row = $result->fetch_assoc()) {
    $solved_challenges[] = $row['challenge_id'];
}

// Get all challenges with solve counts
$challenges = [];
$challenge_result = $conn->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM submissions WHERE challenge_id = c.id) as solve_count
    FROM challenges c
    ORDER BY c.points ASC
");

// Get total users for percentage calculation
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'mahasiswa'")->fetch_assoc()['count'];

while ($row = $challenge_result->fetch_assoc()) {
    $challenges[] = $row;
}

// Group challenges by category
$categories = [
    'easy' => ['name' => 'Easy', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-200'],
    'medium' => ['name' => 'Medium', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200'],
    'hard' => ['name' => 'Hard', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'border' => 'border-red-200']
];

// Get user stats
$user_points = 0;
$user_solves = count($solved_challenges);
if ($user_solves > 0) {
    $points_result = $conn->query("SELECT SUM(score) as total FROM submissions WHERE user_id = $user_id");
    $user_points = $points_result->fetch_assoc()['total'] ?? 0;
}

// Which challenges are container-backed (spawnable) vs. direct-submit only.
require_once __DIR__ . '/../config/challenge_map.php';

// Pre-load the user's currently running instances so a page refresh keeps the
// "instance running" panel visible instead of resetting to the Start button.
$running_instances = [];
$ri = $conn->prepare(
    "SELECT challenge_id, port, expires_at FROM active_instances
     WHERE user_id = ? AND status = 'running' AND expires_at > NOW()"
);
$ri->bind_param("i", $user_id);
$ri->execute();
$ri_res = $ri->get_result();
while ($row = $ri_res->fetch_assoc()) {
    $running_instances[(int) $row['challenge_id']] = $row;
}

// Host students use to reach instances (mirrors spawn.php::instance_host()).
$instance_host = getenv('CTF_INSTANCE_HOST');
if (!$instance_host) {
    $instance_host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTF Challenges - Cyber Security Platform</title>
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
                    <h1 class="text-lg font-bold text-gray-900">CTF Platform</h1>
                    <p class="text-xs text-gray-500">Cyber Security Challenges</p>
                </div>

                <!-- User Info -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user['nama'] ?? 'User') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($user['nim']) ?></p>
                    </div>
                    <div class="flex items-center gap-2 bg-blue-50 px-3 py-2 rounded-lg">
                        <i data-lucide="trophy" class="text-blue-500 w-5 h-5"></i>
                        <div>
                            <p class="text-xs text-gray-500">Points</p>
                            <p class="text-sm font-bold text-blue-600"><?= number_format($user_points) ?></p>
                        </div>
                    </div>
                    <a href="logout.php" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-6 mb-8 text-white">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold mb-1">Selamat Datang, <?= htmlspecialchars($user['nama'] ?? 'User') ?>!</h2>
                    <p class="text-blue-100">Selesaikan challenges dan kumpulkan poin sebanyak-banyaknya</p>
                </div>
                <div class="flex gap-6">
                    <div class="text-center">
                        <p class="text-3xl font-bold"><?= $user_solves ?></p>
                        <p class="text-blue-100 text-sm">Solved</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold"><?= number_format($user_points) ?></p>
                        <p class="text-blue-100 text-sm">Points</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <button class="px-4 py-3 text-blue-600 border-b-2 border-blue-600 font-semibold">Challenges</button>
            <a href="scoreboard.php" class="px-4 py-3 text-gray-500 hover:text-gray-700 font-semibold">Scoreboard</a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap gap-2 mb-6" style="list-style: none;">
            <button onclick="filterChallenges('all')" class="filter-btn active px-4 py-2 rounded-lg font-medium transition-all" data-filter="all">All</button>
            <button onclick="filterChallenges('easy')" class="filter-btn px-4 py-2 rounded-lg font-medium transition-all" data-filter="easy">Easy</button>
            <button onclick="filterChallenges('medium')" class="filter-btn px-4 py-2 rounded-lg font-medium transition-all" data-filter="medium">Medium</button>
            <button onclick="filterChallenges('hard')" class="filter-btn px-4 py-2 rounded-lg font-medium transition-all" data-filter="hard">Hard</button>
        </div>

        <!-- Challenges Grid -->
        <div id="challenges-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($challenges as $challenge):
                $is_solved = in_array($challenge['id'], $solved_challenges);
                $cat = $categories[$challenge['category']];
                $solve_percent = $total_users > 0 ? round(($challenge['solve_count'] / $total_users) * 100) : 0;
            ?>
                <div class="challenge-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all" data-category="<?= $challenge['category'] ?>">
                    <!-- Card Header -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex justify-between items-start mb-3">
                            <span class="px-2 py-1 rounded-md text-xs font-semibold <?= $cat['bg'] ?> <?= $cat['text'] ?>">
                                <?= $cat['name'] ?>
                            </span>
                            <?php if ($is_solved): ?>
                                <span class="flex items-center gap-1 text-green-600 text-sm font-semibold">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Solved
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($challenge['title']) ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($challenge['description']) ?></p>
                    </div>

                    <!-- Card Stats -->
                    <div class="px-5 py-3 bg-gray-50 flex justify-center items-center text-sm">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" class="text-gray-400 w-4 h-4"></i>
                            <span class="text-gray-600"><?= $challenge['solve_count'] ?> solves</span>
                        </div>
                        <div class="w-px h-4 bg-gray-300 mx-2"></div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="bar-chart-2" class="text-gray-400 w-4 h-4"></i>
                            <span class="text-gray-600"><?= $solve_percent ?>%</span>
                        </div>
                    </div>

                    <!-- Card Action -->
                    <?php
                        $cid_int     = (int) $challenge['id'];
                        $spawnable   = isset($CHALLENGE_IMAGES[$cid_int]);
                        $web_url     = $WEB_CHALLENGES[$cid_int] ?? '';
                        $running     = $running_instances[$cid_int] ?? null;
                        $inst256     = $running ? ('http://' . $instance_host . ':' . (int) $running['port']) : '';
                        $remaining   = $running ? max(0, strtotime($running['expires_at']) - time()) : 0;
                        $title_attr  = htmlspecialchars($challenge['title'], ENT_QUOTES);
                    ?>
                    <div class="p-4 card-action"
                         id="action-<?= $cid_int ?>"
                         data-challenge="<?= $cid_int ?>"
                         data-title="<?= $title_attr ?>"
                         data-spawnable="<?= $spawnable ? 1 : 0 ?>"
                         data-running="<?= $running ? 1 : 0 ?>"
                         data-url="<?= htmlspecialchars($inst256, ENT_QUOTES) ?>"
                         data-weburl="<?= htmlspecialchars($web_url, ENT_QUOTES) ?>"
                         data-remaining="<?= (int) $remaining ?>">
                        <?php if ($is_solved): ?>
                            <button class="w-full py-3 bg-green-50 text-green-600 rounded-lg font-semibold flex items-center justify-center gap-2">
                                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                                Completed
                            </button>
                        <?php elseif ($spawnable): ?>
                            <div class="flex gap-2">
                                <button class="start-btn flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i data-lucide="play" class="w-4 h-4"></i>
                                    <span>Open Instance</span>
                                </button>
                                <button class="submit-flag-btn flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i data-lucide="flag" class="w-4 h-4"></i>
                                    Submit Flag
                                </button>
                            </div>
                        <?php elseif ($web_url !== ''): ?>
                            <div class="flex gap-2">
                                <a href="<?= htmlspecialchars($web_url, ENT_QUOTES) ?>" target="_blank" rel="noopener"
                                   class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                    Open Challenge
                                </a>
                                <button class="submit-flag-btn flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i data-lucide="flag" class="w-4 h-4"></i>
                                    Submit Flag
                                </button>
                            </div>
                        <?php else: ?>
                            <button class="start-btn w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                                <span>Submit Flag</span>
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Flag Submission Modal -->
    <div id="challenge-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 id="modal-title" class="text-xl font-bold text-gray-900">Submit Flag</h3>
                    <button onclick="closeModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                    </button>
                </div>
            </div>
            <form id="flag-form" class="p-6">
                <input type="hidden" name="challenge_id" id="modal-challenge-id">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Flag Format</label>
                    <p class="text-sm text-gray-500 mb-3">Format: <code class="bg-gray-100 px-2 py-1 rounded">CTF{...}</code></p>
                    <input type="text" name="flag" id="modal-flag" class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="CTF{flag_here}" required autocomplete="off">
                </div>
                <div id="submit-error" class="hidden mb-4 p-3 bg-red-50 text-red-600 rounded-lg text-sm"></div>
                <div id="submit-success" class="hidden mb-4 p-3 bg-green-50 text-green-600 rounded-lg text-sm"></div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                    Submit Flag
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('flag-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const errorDiv = document.getElementById('submit-error');
            const successDiv = document.getElementById('submit-success');
            const submitBtn = e.target.querySelector('button[type="submit"]');

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const response = await fetch('submit_flag.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();

                if (result.trim() === 'CORRECT') {
                    successDiv.textContent = '🎉 Correct! Challenge solved!';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => {
                        closeModal();
                        location.reload();
                    }, 1500);
                } else if (result.trim() === 'ALREADY_SOLVED') {
                    errorDiv.textContent = '⚠️ You already solved this challenge!';
                    errorDiv.classList.remove('hidden');
                } else if (result.trim() === 'INCORRECT') {
                    errorDiv.textContent = '❌ Incorrect flag. Try again!';
                    errorDiv.classList.remove('hidden');
                } else {
                    errorDiv.textContent = '⚠️ Error: ' + result;
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                errorDiv.textContent = '⚠️ Network error. Please try again.';
                errorDiv.classList.remove('hidden');
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Flag';
        });
    </script>

    <script>
        function filterChallenges(category) {
            const cards = document.querySelectorAll('.challenge-card');
            const buttons = document.querySelectorAll('.filter-btn');

            buttons.forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });

            const activeBtn = document.querySelector(`[data-filter="${category}"]`);
            activeBtn.classList.add('active', 'bg-blue-600', 'text-white');
            activeBtn.classList.remove('bg-gray-100', 'text-gray-700');

            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function openChallengeModal(challengeId, title) {
            document.getElementById('modal-challenge-id').value = challengeId;
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-flag').value = '';
            document.getElementById('submit-error').classList.add('hidden');
            document.getElementById('submit-success').classList.add('hidden');
            document.getElementById('challenge-modal').classList.remove('hidden');
            document.getElementById('challenge-modal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('challenge-modal').classList.add('hidden');
            document.getElementById('challenge-modal').classList.remove('flex');
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Close modal on backdrop click
        document.getElementById('challenge-modal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('challenge-modal')) closeModal();
        });

        // Initialize filter buttons style
        document.querySelectorAll('.filter-btn').forEach(btn => {
            if (btn.classList.contains('active')) {
                btn.classList.add('bg-blue-600', 'text-white');
            } else {
                btn.classList.add('bg-gray-100', 'text-gray-700');
            }
        });
    </script>

    <script>
        /**
         * Build the markup for a running container instance: a "View Instance"
         * link and a "Submit Flag" button side by side, with the remaining-time
         * countdown beneath them.
         *
         * @param {string} url Public URL of the spawned ttyd instance.
         * @param {number} remaining Seconds left before the instance self-destructs.
         * @returns {string} HTML string for the running-state panel.
         */
        function runningPanelHTML(url, remaining) {
            const mmss = fmtTime(remaining);
            return `
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <a href="${url}" target="_blank" rel="noopener"
                           class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="terminal" class="w-4 h-4"></i>
                            View Instance
                        </a>
                        <button class="submit-flag-btn flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="flag" class="w-4 h-4"></i>
                            Submit Flag
                        </button>
                    </div>
                    <p class="text-xs text-center text-gray-500">
                        Instance aktif &middot; hancur otomatis dalam
                        <span class="countdown font-mono font-semibold text-gray-700">${mmss}</span>
                    </p>
                </div>`;
        }

        /**
         * Format a duration in seconds as m:ss.
         *
         * @param {number} s Duration in seconds.
         * @returns {string} Formatted time string such as "25:00".
         */
        function fmtTime(s) {
            s = Math.max(0, parseInt(s, 10) || 0);
            const m = Math.floor(s / 60);
            const ss = (s % 60).toString().padStart(2, '0');
            return `${m}:${ss}`;
        }

        /**
         * Swap a card's action area into the running state, start its countdown,
         * and wire the panel's "Submit Flag" button to the shared flag modal.
         *
         * @param {HTMLElement} actionEl The card-action container element.
         * @param {string} url Public URL of the spawned ttyd instance.
         * @param {number} remaining Seconds left before the instance expires.
         * @returns {void}
         */
        function mountRunningPanel(actionEl, url, remaining) {
            actionEl.dataset.running = '1';
            actionEl.dataset.url = url;
            actionEl.dataset.remaining = remaining;
            actionEl.innerHTML = runningPanelHTML(url, remaining);
            lucide.createIcons();
            startCountdown(actionEl, remaining);

            const submitBtn = actionEl.querySelector('.submit-flag-btn');
            if (submitBtn) submitBtn.addEventListener('click', () =>
                openChallengeModal(actionEl.dataset.challenge, actionEl.dataset.title));
        }

        /**
         * Run a per-card countdown that disables the instance link when time runs out.
         *
         * @param {HTMLElement} actionEl The card-action container element.
         * @param {number} remaining Seconds left before the instance expires.
         * @returns {void}
         */
        function startCountdown(actionEl, remaining) {
            const span = actionEl.querySelector('.countdown');
            let left = parseInt(remaining, 10) || 0;
            if (actionEl._timer) clearInterval(actionEl._timer);
            actionEl._timer = setInterval(() => {
                left -= 1;
                if (left <= 0) {
                    clearInterval(actionEl._timer);
                    if (span) span.textContent = '0:00';
                    const link = actionEl.querySelector('a[target="_blank"]');
                    if (link) {
                        link.classList.add('pointer-events-none', 'opacity-50');
                        link.querySelector('i')?.remove();
                        link.childNodes.forEach(n => { if (n.nodeType === 3) n.textContent = ' Instance expired'; });
                    }
                    return;
                }
                if (span) span.textContent = fmtTime(left);
            }, 1000);
        }

        /**
         * Handle a click on a container challenge's "Open Instance" button by
         * requesting a new instance from spawn.php and mounting the running panel.
         * Non-container challenges fall back to the flag-submission modal.
         *
         * @param {HTMLElement} actionEl The card-action container element.
         * @param {HTMLButtonElement} btn The button that was clicked.
         * @returns {Promise<void>}
         */
        async function startChallenge(actionEl, btn) {
            const spawnable = actionEl.dataset.spawnable === '1';
            const challengeId = actionEl.dataset.challenge;
            const title = actionEl.dataset.title;

            if (!spawnable) {
                openChallengeModal(challengeId, title);
                return;
            }

            btn.disabled = true;
            const label = btn.querySelector('span');
            const oldLabel = label ? label.textContent : '';
            if (label) label.textContent = 'Starting...';

            try {
                const fd = new FormData();
                fd.append('challenge_id', challengeId);
                const res = await fetch('spawn.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.ok) {
                    mountRunningPanel(actionEl, data.url, data.ttl_remaining);
                    return;
                }

                if (data.spawnable === false) {
                    openChallengeModal(challengeId, title);
                } else {
                    alert('Gagal memulai challenge: ' + (data.error || 'unknown error'));
                }
            } catch (err) {
                alert('Network error saat memulai challenge.');
            }

            btn.disabled = false;
            if (label) label.textContent = oldLabel;
        }

        /**
         * Initialize every challenge card: restore running instances on refresh and
         * bind the appropriate click handlers for each card type.
         *
         * @returns {void}
         */
        function initChallengeCards() {
            document.querySelectorAll('.card-action').forEach(actionEl => {
                if (actionEl.dataset.running === '1') {
                    mountRunningPanel(actionEl, actionEl.dataset.url,
                        parseInt(actionEl.dataset.remaining, 10) || 0);
                    return;
                }
                const btn = actionEl.querySelector('.start-btn');
                if (btn) btn.addEventListener('click', () => startChallenge(actionEl, btn));

                const submitBtn = actionEl.querySelector('.submit-flag-btn');
                if (submitBtn) submitBtn.addEventListener('click', () =>
                    openChallengeModal(actionEl.dataset.challenge, actionEl.dataset.title));
            });
        }

        document.addEventListener('DOMContentLoaded', initChallengeCards);
    </script>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
