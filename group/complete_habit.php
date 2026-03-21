#!/usr/local/bin/php
<?php
ob_start();
session_start();
require_once "db.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

function sendJson($data) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION["user_id"])) {
    sendJson([
        "success" => false,
        "message" => "Not logged in."
    ]);
}

$user_id = (int)$_SESSION["user_id"];

function xpReward($weight) {
    return ((int)$weight) * 10;
}

function xpRequiredForLevel($level) {
    $baseXp = 100;
    $growthRate = 1.20;

    if ($level <= 1) {
        return $baseXp;
    }

    return (int) round($baseXp * pow($growthRate, $level - 1));
}

function calculateLevelData($totalXp) {
    $level = 1;
    $xpRemaining = (int)$totalXp;

    while ($xpRemaining >= xpRequiredForLevel($level)) {
        $xpRemaining -= xpRequiredForLevel($level);
        $level++;
    }

    $xpNeededThisLevel = xpRequiredForLevel($level);

    return [
        "level" => $level,
        "xp_into_level" => $xpRemaining,
        "xp_needed" => $xpNeededThisLevel,
        "progress_percent" => $xpNeededThisLevel > 0
            ? round(($xpRemaining / $xpNeededThisLevel) * 100, 2)
            : 0
    ];
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJson([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}

$habit_id = isset($_POST["habit_id"]) ? (int)$_POST["habit_id"] : 0;

if ($habit_id <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid habit."
    ]);
}

$today = date("Y-m-d");

try {
    $habitStmt = $conn->prepare("
        SELECT habit_id, weight, last_completed_date, is_active
        FROM habits
        WHERE habit_id = ? AND user_id = ?
        LIMIT 1
    ");

    if (!$habitStmt) {
        throw new Exception("Failed to prepare habit query.");
    }

    $habitStmt->bind_param("ii", $habit_id, $user_id);
    $habitStmt->execute();
    $habitResult = $habitStmt->get_result();

    if ($habitResult->num_rows !== 1) {
        $habitStmt->close();
        sendJson([
            "success" => false,
            "message" => "Habit not found."
        ]);
    }

    $habit = $habitResult->fetch_assoc();
    $habitStmt->close();

    if ((int)$habit["is_active"] !== 1) {
        sendJson([
            "success" => false,
            "message" => "Habit is inactive."
        ]);
    }

    $userStmt = $conn->prepare("SELECT xp FROM users WHERE user_id = ? LIMIT 1");
    if (!$userStmt) {
        throw new Exception("Failed to prepare user query.");
    }

    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userStmt->close();

    $currentXp = (int)$userData["xp"];

    if ($habit["last_completed_date"] === $today) {
        $levelData = calculateLevelData($currentXp);

        sendJson([
            "success" => false,
            "already_completed" => true,
            "message" => "Already completed today.",
            "reward_xp" => 0,
            "total_xp" => $currentXp,
            "level" => $levelData["level"],
            "xp_into_level" => $levelData["xp_into_level"],
            "xp_needed" => $levelData["xp_needed"],
            "progress_percent" => $levelData["progress_percent"]
        ]);
    }

    $rewardXp = xpReward((int)$habit["weight"]);
    $newXp = $currentXp + $rewardXp;

    $levelData = calculateLevelData($newXp);
    $newLevel = (int)$levelData["level"];

    $updateUserStmt = $conn->prepare("
        UPDATE users
        SET xp = ?, level = ?
        WHERE user_id = ?
    ");
    if (!$updateUserStmt) {
        throw new Exception("Failed to prepare user update.");
    }

    $updateUserStmt->bind_param("iii", $newXp, $newLevel, $user_id);
    $updateUserStmt->execute();
    $updateUserStmt->close();

    $updateHabitStmt = $conn->prepare("
        UPDATE habits
        SET last_completed_date = ?
        WHERE habit_id = ? AND user_id = ?
    ");
    if (!$updateHabitStmt) {
        throw new Exception("Failed to prepare habit update.");
    }

    $updateHabitStmt->bind_param("sii", $today, $habit_id, $user_id);
    $updateHabitStmt->execute();
    $updateHabitStmt->close();

    $_SESSION["xp"] = $newXp;
    $_SESSION["level"] = $newLevel;

    sendJson([
        "success" => true,
        "message" => "Habit completed.",
        "reward_xp" => $rewardXp,
        "total_xp" => $newXp,
        "level" => $newLevel,
        "xp_into_level" => $levelData["xp_into_level"],
        "xp_needed" => $levelData["xp_needed"],
        "progress_percent" => $levelData["progress_percent"]
    ]);

} catch (Throwable $e) {
    sendJson([
        "success" => false,
        "message" => "Server error.",
        "debug" => $e->getMessage()
    ]);
}