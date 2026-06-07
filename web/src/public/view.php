<?php
require_once __DIR__ . '/../config/database.php';

$result = mysqli_query($conn, "SELECT * FROM comments");

echo "<h1>Komentar Mahasiswa</h1>";

while ($row = mysqli_fetch_assoc($result)) {
    echo $row['comment'];
    echo "<hr>";
}
?>