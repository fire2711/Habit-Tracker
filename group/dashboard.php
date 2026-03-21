#!/usr/local/bin/php
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

function difficultyLabel($weight) {
    if ($weight == 1) return "Easy";
    if ($weight == 2) return "Medium";
    return "Hard";
}

function difficultyClass($weight) {
    if ($weight == 1) return "difficulty-easy";
    if ($weight == 2) return "difficulty-medium";
    return "difficulty-hard";
}

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
        "progress_percent" => $xpNeededThisLevel > 0 ? ($xpRemaining / $xpNeededThisLevel) * 100 : 0
    ];
}

/* ADD HABIT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_habit"])) {
    $habit_name = trim($_POST["habit_name"]);
    $description = trim($_POST["description"]);
    $weight = (int)$_POST["weight"];

    if ($weight < 1) $weight = 1;
    if ($weight > 3) $weight = 3;

    if (!empty($habit_name)) {
        $stmt = $conn->prepare("
            INSERT INTO habits (user_id, habit_name, description, weight)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("issi", $user_id, $habit_name, $description, $weight);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

/* UPDATE HABIT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_habit"])) {
    $habit_id = (int)$_POST["habit_id"];
    $habit_name = trim($_POST["habit_name"]);
    $description = trim($_POST["description"]);
    $weight = (int)$_POST["weight"];

    if ($weight < 1) $weight = 1;
    if ($weight > 3) $weight = 3;

    if ($habit_id > 0 && !empty($habit_name)) {
        $stmt = $conn->prepare("
            UPDATE habits
            SET habit_name = ?, description = ?, weight = ?
            WHERE habit_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiii", $habit_name, $description, $weight, $habit_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

/* DELETE HABIT */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_habit"])) {
    $habit_id = (int)$_POST["habit_id"];

    if ($habit_id > 0) {
        $stmt = $conn->prepare("
            DELETE FROM habits
            WHERE habit_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $habit_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

/* USER DATA */
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

/* EDIT MODE */
$editHabit = null;

if (isset($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];

    if ($edit_id > 0) {
        $editStmt = $conn->prepare("
            SELECT habit_id, habit_name, description, weight
            FROM habits
            WHERE habit_id = ? AND user_id = ?
            LIMIT 1
        ");
        $editStmt->bind_param("ii", $edit_id, $user_id);
        $editStmt->execute();
        $editResult = $editStmt->get_result();

        if ($editResult->num_rows === 1) {
            $editHabit = $editResult->fetch_assoc();
        }

        $editStmt->close();
    }
}

/* HABITS */
$stmt = $conn->prepare("
    SELECT habit_id, habit_name, description, weight, is_active, created_at, last_completed_date
    FROM habits
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$habits = $stmt->get_result();

$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Habit Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<div class="container page-wrap">

    <div class="glass-card hero-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="hero-title">Welcome back, <?php echo htmlspecialchars($name); ?> 👾</div>
                <p class="hero-subtitle">Track habits, earn XP, level up, and build consistency.</p>
            </div>
            <div class="top-actions d-flex gap-2">
                <a href="history.php" class="btn btn-history">History</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="glass-card level-hud mb-4">
        <div class="level-hud-top">
            <div>
                <div class="hud-label">Your Level</div>
                <div class="level-display">
                    <span class="level-badge" id="levelBadge">LV <?php echo $currentLevel; ?></span>
                </div>
            </div>

            <div class="hud-total-xp">
                <div class="hud-label">Total XP</div>
                <div class="hud-total-xp-value" id="totalXpValue"><?php echo $totalXp; ?> XP</div>
            </div>
        </div>

        <div class="xp-progress-wrap">
            <div class="xp-progress-text">
                <span id="xpProgressText"><?php echo $xpIntoLevel; ?>/<?php echo $xpNeeded; ?> XP</span>
                <span class="small-muted">to next level</span>
            </div>

            <div class="progress xp-progress-bar">
                <div
                    class="progress-bar"
                    id="xpProgressBar"
                    role="progressbar"
                    style="width: <?php echo $progressPercent; ?>%">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="glass-card section-card">
                <div class="section-title"><?php echo $editHabit ? "Edit Habit" : "Add New Habit"; ?></div>

                <form method="post" action="dashboard.php">
                    <?php if ($editHabit): ?>
                        <input type="hidden" name="update_habit" value="1">
                        <input type="hidden" name="habit_id" value="<?php echo (int)$editHabit["habit_id"]; ?>">
                    <?php else: ?>
                        <input type="hidden" name="add_habit" value="1">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Habit Name</label>
                        <input
                            type="text"
                            name="habit_name"
                            class="form-control"
                            placeholder="Ex: Workout, Read, Drink Water"
                            value="<?php echo $editHabit ? htmlspecialchars($editHabit["habit_name"]) : ""; ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Describe the habit..."
                        ><?php echo $editHabit ? htmlspecialchars($editHabit["description"]) : ""; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Difficulty</label>
                        <div class="modern-select-wrap">
                            <select name="weight" class="form-select modern-select" required>
                                <option value="1" <?php echo ($editHabit && (int)$editHabit["weight"] === 1) ? "selected" : ""; ?>>Easy</option>
                                <option value="2" <?php echo ($editHabit && (int)$editHabit["weight"] === 2) ? "selected" : ""; ?>>Medium</option>
                                <option value="3" <?php echo ($editHabit && (int)$editHabit["weight"] === 3) ? "selected" : ""; ?>>Hard</option>
                            </select>
                            <span class="select-chevron">⌄</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <?php echo $editHabit ? "Save Changes" : "Add Habit"; ?>
                        </button>

                        <?php if ($editHabit): ?>
                            <a href="dashboard.php" class="btn action-btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="glass-card section-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">Your Habits</div>
                    <span class="small-muted"><?php echo $habits->num_rows; ?> total</span>
                </div>

                <?php if ($habits->num_rows > 0): ?>
                    <div class="habit-list">
                        <?php while ($habit = $habits->fetch_assoc()): ?>
                            <?php
                                $isCompletedToday = ($habit["last_completed_date"] === $today);
                                $reward = xpReward((int)$habit["weight"]);
                            ?>
                            <div class="habit-item <?php echo $isCompletedToday ? 'habit-complete' : ''; ?>">
                                <div class="habit-top">
                                    <div class="habit-main">
                                        <div class="habit-name-row">
                                            <div class="habit-name"><?php echo htmlspecialchars($habit["habit_name"]); ?></div>
                                            <span class="difficulty-badge <?php echo difficultyClass((int)$habit["weight"]); ?>">
                                                <?php echo difficultyLabel((int)$habit["weight"]); ?>
                                            </span>
                                        </div>

                                        <p class="habit-desc">
                                            <?php echo !empty($habit["description"]) ? htmlspecialchars($habit["description"]) : "No description provided."; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="habit-meta">
                                    <span class="xp-chip">⚡ +<?php echo $reward; ?> XP</span>
                                    <span>Created: <?php echo htmlspecialchars($habit["created_at"]); ?></span>
                                </div>

                                <div class="habit-actions mt-3">
                                    <?php if ($isCompletedToday): ?>
                                        <button type="button" class="btn btn-complete done-btn habit-complete-btn" disabled>
                                            ✓ Completed Today
                                        </button>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="btn btn-complete w-100 habit-complete-btn"
                                            data-habit-id="<?php echo (int)$habit["habit_id"]; ?>"
                                        >
                                            ✓ Mark Complete
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="habit-manage mt-3">
                                    <a href="dashboard.php?edit=<?php echo (int)$habit["habit_id"]; ?>" class="btn action-btn-edit">
                                        Edit
                                    </a>

                                    <button
                                        type="button"
                                        class="btn action-btn-delete open-delete-modal-btn"
                                        data-habit-id="<?php echo (int)$habit["habit_id"]; ?>"
                                        data-habit-name="<?php echo htmlspecialchars($habit["habit_name"]); ?>"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h5 class="mb-2">No habits yet</h5>
                        <p class="mb-0">Create your first habit and start leveling up.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="delete-modal-overlay" id="deleteModalOverlay">
    <div class="delete-modal-box">
        <div class="delete-modal-title">Delete Habit?</div>
        <p class="delete-modal-text" id="deleteModalText">Are you sure you want to delete this habit?</p>

        <form method="post" action="dashboard.php" class="delete-modal-actions">
            <input type="hidden" name="delete_habit" value="1">
            <input type="hidden" name="habit_id" id="deleteHabitIdInput" value="">

            <button type="button" class="btn action-btn-secondary" id="closeDeleteModalBtn">Cancel</button>
            <button type="submit" class="btn action-btn-delete-solid">Delete</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const levelBadge = document.getElementById("levelBadge");
    const totalXpValue = document.getElementById("totalXpValue");
    const xpProgressText = document.getElementById("xpProgressText");
    const xpProgressBar = document.getElementById("xpProgressBar");

    const deleteModalOverlay = document.getElementById("deleteModalOverlay");
    const deleteHabitIdInput = document.getElementById("deleteHabitIdInput");
    const deleteModalText = document.getElementById("deleteModalText");
    const closeDeleteModalBtn = document.getElementById("closeDeleteModalBtn");

    document.querySelectorAll(".habit-complete-btn").forEach((button) => {
        if (button.disabled) return;

        button.addEventListener("click", async function () {
            const habitId = this.dataset.habitId;
            const clickedButton = this;

            clickedButton.disabled = true;
            clickedButton.textContent = "Completing...";

            try {
                const formData = new FormData();
                formData.append("habit_id", habitId);

                const response = await fetch("complete_habit.php", {
                    method: "POST",
                    body: formData
                });

                const text = await response.text();
                console.log("complete_habit raw response:", text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    clickedButton.disabled = false;
                    clickedButton.textContent = "✓ Mark Complete";
                    alert("complete_habit.php is not returning valid JSON.");
                    return;
                }

                if (!data.success && !data.already_completed) {
                    clickedButton.disabled = false;
                    clickedButton.textContent = "✓ Mark Complete";
                    alert(data.message || "Could not complete habit.");
                    return;
                }

                totalXpValue.textContent = data.total_xp + " XP";
                levelBadge.textContent = "LV " + data.level;
                xpProgressText.textContent = data.xp_into_level + "/" + data.xp_needed + " XP";
                xpProgressBar.style.width = data.progress_percent + "%";

                clickedButton.textContent = "✓ Completed Today";
                clickedButton.classList.add("done-btn");
                clickedButton.disabled = true;

                const habitCard = clickedButton.closest(".habit-item");
                if (habitCard) {
                    habitCard.classList.add("habit-complete");
                }

                const xpFlash = document.createElement("div");
                xpFlash.className = "xp-float";
                xpFlash.textContent = "+" + (data.reward_xp || 0) + " XP";
                clickedButton.parentElement.appendChild(xpFlash);

                setTimeout(() => {
                    xpFlash.remove();
                }, 1400);
            } catch (error) {
                console.error(error);
                clickedButton.disabled = false;
                clickedButton.textContent = "✓ Mark Complete";
                alert("Something went wrong.");
            }
        });
    });

    document.querySelectorAll(".open-delete-modal-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const habitId = this.dataset.habitId;
            const habitName = this.dataset.habitName || "this habit";

            deleteHabitIdInput.value = habitId;
            deleteModalText.textContent = 'Delete "' + habitName + '"? This cannot be undone.';
            deleteModalOverlay.classList.add("show");
        });
    });

    closeDeleteModalBtn.addEventListener("click", function () {
        deleteModalOverlay.classList.remove("show");
    });

    deleteModalOverlay.addEventListener("click", function (e) {
        if (e.target === deleteModalOverlay) {
            deleteModalOverlay.classList.remove("show");
        }
    });
});
</script>
</body>
</html>
<?php
$stmt->close();
?>