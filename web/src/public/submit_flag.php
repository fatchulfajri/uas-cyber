<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/docker.php';

if (!isset($_SESSION['user'])) {
    die("Akses ditolak: Silakan login terlebih dahulu.");
}

$user = $_SESSION['user'];
$user_id = (int) $user['id'];
// Cast to int: challenge_id is later interpolated into a query string, so this
// closes a SQL-injection vector while keeping the falsy check below intact.
$challenge_id = (int) ($_POST['challenge_id'] ?? 0);
$flag = $_POST['flag'] ?? '';

if (!$challenge_id || empty($flag)) {
    die("Missing required fields");
}

// Check if already solved (application level)
$check_solved = $conn->prepare("SELECT id FROM submissions WHERE user_id = ? AND challenge_id = ?");
$check_solved->bind_param("ii", $user_id, $challenge_id);
$check_solved->execute();
$solved_result = $check_solved->get_result();

if ($solved_result->num_rows > 0) {
    die("ALREADY_SOLVED");
}

// Get challenge info
$challenge_stmt = $conn->prepare("SELECT * FROM challenges WHERE id = ?");
$challenge_stmt->bind_param("i", $challenge_id);
$challenge_stmt->execute();
$challenge_data = $challenge_stmt->get_result()->fetch_assoc();

if (!$challenge_data) {
    die("Challenge not found");
}

// Validate flag
if ($flag === $challenge_data['flag']) {
    // Correct flag - insert submission
    $insert_stmt = $conn->prepare("INSERT INTO submissions (user_id, challenge_id, score, submit_time) VALUES (?, ?, ?, NOW())");
    $insert_stmt->bind_param("iii", $user_id, $challenge_id, $challenge_data['points']);

    if ($insert_stmt->execute()) {
        // Update solve count
        $conn->query("UPDATE challenges SET solves = solves + 1 WHERE id = $challenge_id");

        // Reclaim the user's running container for this challenge (if any).
        // The container ID is read from the DB — never from the client.
        $inst_stmt = $conn->prepare(
            "SELECT * FROM active_instances
             WHERE user_id = ? AND challenge_id = ? AND status = 'running'"
        );
        $inst_stmt->bind_param("ii", $user_id, $challenge_id);
        $inst_stmt->execute();
        $instance = $inst_stmt->get_result()->fetch_assoc();
        if ($instance) {
            stop_instance($conn, $instance);
        }

        echo "CORRECT";
    } else {
        // Check for duplicate entry error (database level protection)
        if ($conn->errno === 1062) {
            die("ALREADY_SOLVED");
        }
        echo "ERROR: " . $conn->error;
    }
} else {
    echo "INCORRECT";
}
?>
