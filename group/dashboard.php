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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_habit"])) {
    $habit_name = trim($_POST["habit_name"]);
    $description = trim($_POST["description"]);
    $weight = (int)$_POST["weight"];

    if ($weight < 1) {
        $weight = 1;
    }

    if (!empty($habit_name)) {
        $stmt = $conn->prepare("INSERT INTO habits (user_id, habit_name, description, weight) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $habit_name, $description, $weight);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT habit_id, habit_name, description, weight, is_active, created_at FROM habits WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$habits = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Habit Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Welcome, <?php echo htmlspecialchars($name); ?></h1>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="card shadow p-4 mb-4">
        <h3>Your Stats</h3>
        <p class="mb-1">XP: <?php echo (int)$_SESSION["xp"]; ?></p>
        <p class="mb-0">Level: <?php echo (int)$_SESSION["level"]; ?></p>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow p-4 mb-4">
                <h3 class="mb-3">Add Habit</h3>
                <form method="post" action="dashboard.php">
                    <input type="hidden" name="add_habit" value="1">

                    <div class="mb-3">
                        <label class="form-label">Habit Name</label>
                        <input type="text" name="habit_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Weight</label>
                        <input type="number" name="weight" class="form-control" min="1" value="1" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Add Habit</button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow p-4">
                <h3 class="mb-3">Your Habits</h3>

                <?php if ($habits->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($habit = $habits->fetch_assoc()): ?>
                            <div class="list-group-item mb-2">
                                <h5 class="mb-1"><?php echo htmlspecialchars($habit["habit_name"]); ?></h5>
                                <p class="mb-1"><?php echo htmlspecialchars($habit["description"]); ?></p>
                                <small>Weight: <?php echo (int)$habit["weight"]; ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No habits yet. Add your first habit.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php
$stmt->close();
?>
