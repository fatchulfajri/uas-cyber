<?php

/**
 * Echo the user-supplied search term back into the page. This function is
 * INTENTIONALLY unsafe: the value is printed without htmlspecialchars(), which
 * creates the reflected XSS vulnerability that is the objective of this
 * challenge. The flag is never present in this source; it lives in flag.txt and
 * can only be retrieved by exploiting this sink to fetch and read that file.
 *
 * @param {string} $q Raw search term taken from the query string.
 * @returns {void}
 */
function reflect_query(string $q): void
{
    echo $q;
}

$q = $_GET['q'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge: Reflected XSS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    * {
        font-family: 'Inter', sans-serif;
    }
    </style>
    <script>
    /**
     * Install a success detector that wraps window.alert. When an injected
     * payload alerts a value matching the flag format, a success banner is
     * shown. This detector intentionally holds no flag value: the flag must
     * come from the payload's own fetch() of flag.txt.
     *
     * @returns {void}
     */
    function installXssDetector() {
        var nativeAlert = window.alert;

        /**
         * Replacement for window.alert that reports successful exploitation.
         *
         * @param {*} message Value the injected payload passed to alert.
         * @returns {*} The native alert return value.
         */
        window.alert = function(message) {
            var text = String(message == null ? '' : message);
            if (/CTF\{[^}]+\}/.test(text)) {
                var el = document.getElementById('xss-status');
                if (el) {
                    el.textContent = 'XSS berhasil! Flag berhasil dibaca dari flag.txt melalui exploit.';
                    el.classList.remove('hidden');
                }
                console.log('XSS solved: flag exfiltrated via fetch().');
            }
            return nativeAlert.apply(window, arguments);
        };
    }

    installXssDetector();
    </script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-1">Product Search</h1>
            <p class="text-sm text-gray-500 mb-5">Cari produk favoritmu di toko kami.</p>

            <form method="GET" class="flex gap-2 mb-5">
                <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>"
                    placeholder="Ketik kata kunci..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                    Cari
                </button>
            </form>

            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                Hasil pencarian untuk: <?php reflect_query($q); ?>
            </div>

            <div id="xss-status"
                class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg font-mono text-sm text-green-700">
            </div>

            <a href="../../index.php" class="mt-3 inline-block text-sm text-blue-600 hover:underline">← Kembali ke
                dashboard</a>
        </div>
    </div>
</body>

</html>