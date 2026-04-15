#!/usr/local/bin/php
<?php
session_start();
require_once "db.php";
require_once __DIR__ . "/includes/dashboard_helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$name = $_SESSION["name"];

$calendarFeatureEnabled = false;
$todayMultiplier = 1.00;
$todayBusyMinutes = 0;
$todayBusyBlocks = 0;

if (file_exists(__DIR__ . "/calendar_busyness.php")) {
    require_once "calendar_busyness.php";

    if (function_exists("updateTodayBusyness") && function_exists("getTodayBusyness")) {
        $calendarFeatureEnabled = true;
        updateTodayBusyness($conn, $user_id);

        $todayBusyness = getTodayBusyness($conn, $user_id);
        if ($todayBusyness) {
            $todayMultiplier = isset($todayBusyness["multiplier"]) ? (float)$todayBusyness["multiplier"] : 1.00;
            $todayBusyMinutes = isset($todayBusyness["busy_minutes"]) ? (int)$todayBusyness["busy_minutes"] : 0;
            $todayBusyBlocks = isset($todayBusyness["busy_blocks"]) ? (int)$todayBusyness["busy_blocks"] : 0;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_habit"])) {
    $habit_id = (int)$_POST["habit_id"];

    if ($habit_id > 0) {
        $deleteStmt = $conn->prepare("
            DELETE FROM habits
            WHERE habit_id = ? AND user_id = ?
        ");
        $deleteStmt->bind_param("ii", $habit_id, $user_id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

$userStmt = $conn->prepare("SELECT xp, level FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

$totalXp = 0;
$currentLevel = 1;

if ($userResult->num_rows === 1) {
    $userData = $userResult->fetch_assoc();
    $totalXp = (int)$userData["xp"];

    $levelData = calculateLevelData($totalXp);
    $currentLevel = $levelData["level"];

    $_SESSION["xp"] = $totalXp;
    $_SESSION["level"] = $currentLevel;

    $syncStmt = $conn->prepare("UPDATE users SET level = ? WHERE user_id = ?");
    $syncStmt->bind_param("ii", $currentLevel, $user_id);
    $syncStmt->execute();
    $syncStmt->close();
}
$userStmt->close();

$levelData = calculateLevelData($totalXp);
$xpIntoLevel = $levelData["xp_into_level"];
$xpNeeded = $levelData["xp_needed"];
$progressPercent = $levelData["progress_percent"];

$habitStmt = $conn->prepare("
    SELECT habit_id, habit_name, description, weight, is_active, created_at, last_completed_date
    FROM habits
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$habitStmt->bind_param("i", $user_id);
$habitStmt->execute();
$habits = $habitStmt->get_result();

$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Habit Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<div class="container page-wrap">
    <?php include __DIR__ . "/includes/dashboard_header.php"; ?>

    <?php if (isset($_SESSION["calendar_error"])): ?>
        <div class="alert alert-warning mt-3" role="alert">
            <?php
                echo htmlspecialchars($_SESSION["calendar_error"]);
                unset($_SESSION["calendar_error"]);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["google_connected"])): ?>
        <div class="calendar-toast" id="calendarToast">
            <div class="calendar-toast-left">
                <div class="calendar-toast-icon">✓</div>
                <div>
                    <div class="calendar-toast-title">Google Calendar connected</div>
                    <div class="calendar-toast-subtitle">Your schedule can now affect daily XP multipliers.</div>
                </div>
            </div>
            <button type="button" class="calendar-toast-close" id="closeCalendarToast" aria-label="Close">×</button>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . "/includes/dashboard_level_card.php"; ?>
    <?php include __DIR__ . "/includes/dashboard_busyness_card.php"; ?>
    <?php include __DIR__ . "/includes/dashboard_habit_list.php"; ?>
</div>

<?php include __DIR__ . "/includes/delete_modal.php"; ?>

<script src="js/dashboard.js"></script>
</body>
</html>
<?php
$habitStmt->close();
?>