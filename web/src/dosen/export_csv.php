<?php
include '../config.php';
include '../session.php';

// Proteksi halaman
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'dosen') {
    die("Akses ditolak");
}

// Get all students with their scores
$q = $conn->query("
    SELECT u.nama, u.nim, u.team,
           SUM(s.score) as total_score,
           COUNT(s.challenge_id) as solves
    FROM users u
    LEFT JOIN submissions s ON s.user_id = u.id
    WHERE u.role = 'mahasiswa'
    GROUP BY u.id, u.nama, u.nim, u.team
    ORDER BY u.team, total_score DESC
");

// Set headers for CSV download (Excel compatible)
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="ctf_scoreboard_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV with UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// CSV Header
echo "No,Nama Lengkap,NIM,Team,Jumlah Solve,Total Poin\n";

// CSV Data
$no = 1;
while ($r = $q->fetch_assoc()) {
    $nama = htmlspecialchars($r['nama']);
    $nim = htmlspecialchars($r['nim']);
    $team = htmlspecialchars($r['team']);
    $solves = $r['solves'] ?? 0;
    $score = $r['total_score'] ?? 0;

    echo "{$no},\"{$nama}\",{$nim},{$team},{$solves},{$score}\n";
    $no++;
}
?>
