#!/usr/local/bin/php
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION["user_id"];
$editHabit = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["add_habit"])) {
        $habit_name = trim($_POST["habit_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $weight = (int)($_POST["weight"] ?? 1);

        if ($weight < 1) $weight = 1;
        if ($weight > 3) $weight = 3;

        if ($habit_name !== "") {
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

    if (isset($_POST["update_habit"])) {
        $habit_id = (int)($_POST["habit_id"] ?? 0);
        $habit_name = trim($_POST["habit_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $weight = (int)($_POST["weight"] ?? 1);

        if ($weight < 1) $weight = 1;
        if ($weight > 3) $weight = 3;

        if ($habit_id > 0 && $habit_name !== "") {
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
}

if (isset($_GET["edit"])) {
    $habit_id = (int)$_GET["edit"];

    if ($habit_id > 0) {
        $stmt = $conn->prepare("
            SELECT habit_id, habit_name, description, weight
            FROM habits
            WHERE habit_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $habit_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $editHabit = $result->fetch_assoc();
        }

        $stmt->close();
    }
}

$pageTitle = $editHabit ? "Edit Habit" : "Add New Habit";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .manage-page {
            max-width: 760px;
            margin: 42px auto;
        }

        .manage-page-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .manage-page-title {
            font-size: 2rem;
            font-weight: 900;
            color: #6d3df5;
            margin: 0;
        }

        .manage-page-subtitle {
            color: #64748b;
            margin: 6px 0 0;
        }
    </style>
</head>
<body>
<div class="container page-wrap">
    <div class="manage-page">
        <div class="manage-page-top">
            <div>
                <h1 class="manage-page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="manage-page-subtitle">
                    <?php echo $editHabit ? "Update your habit details." : "Create a new habit for your routine."; ?>
                </p>
            </div>
            <a href="dashboard.php" class="btn action-btn-secondary">Back</a>
        </div>

        <div class="glass-card section-card">
            <form method="post" action="manage_habit.php<?php echo $editHabit ? '?edit=' . (int)$editHabit['habit_id'] : ''; ?>">
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
                        rows="5"
                        placeholder="Describe the habit..."
                    ><?php echo $editHabit ? htmlspecialchars($editHabit["description"]) : ""; ?></textarea>
                </div>

                <div class="mb-4">
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
                    <a href="dashboard.php" class="btn action-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>