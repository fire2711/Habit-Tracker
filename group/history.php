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
$name = $_SESSION["name"] ?? "User";
$filter = $_GET["filter"] ?? "all";

$dateCondition = "";
if ($filter === "week") {
    $dateCondition = " AND hl.log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === "month") {
    $dateCondition = " AND hl.log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

/* stats */
$statsStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_logs,
        SUM(CASE WHEN hl.completed = 1 THEN 1 ELSE 0 END) AS completed_logs,
        SUM(COALESCE(hl.xp_earned, 0)) AS total_xp,
        COUNT(DISTINCT CASE WHEN hl.completed = 1 THEN hl.log_date END) AS active_days
    FROM habit_logs hl
    INNER JOIN habits h ON hl.habit_id = h.habit_id
    WHERE h.user_id = ?
    $dateCondition
");
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
$statsStmt->close();

$totalLogs = (int)($stats["total_logs"] ?? 0);
$completedLogs = (int)($stats["completed_logs"] ?? 0);
$totalXp = (int)($stats["total_xp"] ?? 0);
$activeDays = (int)($stats["active_days"] ?? 0);
$completionRate = $totalLogs > 0 ? round(($completedLogs / $totalLogs) * 100) : 0;

/* history rows */
$historyStmt = $conn->prepare("
    SELECT
        hl.log_id,
        hl.log_date,
        hl.completed,
        hl.xp_earned,
        hl.busyness_multiplier,
        h.habit_name,
        h.description,
        h.weight
    FROM habit_logs hl
    INNER JOIN habits h ON hl.habit_id = h.habit_id
    WHERE h.user_id = ?
    $dateCondition
    ORDER BY hl.log_date DESC, hl.log_id DESC
");
$historyStmt->bind_param("i", $user_id);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

$groupedHistory = [];
while ($row = $historyResult->fetch_assoc()) {
    $dateKey = $row["log_date"];
    if (!isset($groupedHistory[$dateKey])) {
        $groupedHistory[$dateKey] = [];
    }
    $groupedHistory[$dateKey][] = $row;
}
$historyStmt->close();

function historyDifficultyClass($weight) {
    $weight = (int)$weight;
    if ($weight <= 1) {
        return "difficulty-easy";
    }
    if ($weight == 2) {
        return "difficulty-medium";
    }
    return "difficulty-hard";
}

function historyDifficultyLabel($weight) {
    $weight = (int)$weight;
    if ($weight <= 1) {
        return "Easy";
    }
    if ($weight == 2) {
        return "Medium";
    }
    return "Hard";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Habit Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .history-stat-card {
            padding: 22px;
            height: 100%;
        }

        .history-stat-label {
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7c4dff;
            margin-bottom: 10px;
        }

        .history-stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1;
        }

        .history-stat-sub {
            margin-top: 8px;
            color: #64748b;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .history-filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .history-filter-chip {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 800;
            border: 1px solid rgba(167, 139, 250, 0.24);
            background: rgba(167, 139, 250, 0.12);
            color: #6d28d9;
            transition: all 0.2s ease;
        }

        .history-filter-chip:hover {
            background: rgba(167, 139, 250, 0.22);
            color: #581c87;
        }

        .history-filter-chip.active {
            background: linear-gradient(90deg, #f472b6, #a78bfa);
            color: #fff;
            border-color: transparent;
        }

        .history-date-block + .history-date-block {
            margin-top: 22px;
        }

        .history-date-title {
            font-size: 1.1rem;
            font-weight: 900;
            color: #6d28d9;
            margin-bottom: 14px;
        }

        .history-item + .history-item {
            margin-top: 14px;
        }

        .history-right-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
        }

        .history-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 0.84rem;
            white-space: nowrap;
        }

        .history-pill-complete {
            background: linear-gradient(180deg, #86efac, #4ade80);
            color: #14532d;
            box-shadow: 0 6px 14px rgba(74, 222, 128, 0.2);
        }

        .history-pill-missed {
            background: linear-gradient(180deg, #fda4af, #fb7185);
            color: #881337;
            box-shadow: 0 6px 14px rgba(251, 113, 133, 0.2);
        }

        .history-pill-multiplier {
            background: rgba(96, 165, 250, 0.14);
            color: #2563eb;
            border: 1px solid rgba(96, 165, 250, 0.32);
        }

        .history-meta-row {
            margin-top: 14px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .history-empty {
            text-align: center;
            padding: 44px 20px;
        }

        .history-empty h5 {
            font-weight: 900;
            color: #7c3aed;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .history-right-meta {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="container page-wrap">
    <div class="glass-card hero-card">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="hero-title mb-1">Habit History</h1>
                <p class="hero-subtitle mb-0">
                    Track your completions, earned XP, and how your daily momentum has been building.
                </p>
            </div>

            <div class="top-actions d-flex gap-2 flex-wrap">
                <a href="dashboard.php" class="btn action-btn-secondary">← Dashboard</a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="glass-card history-stat-card">
                <div class="history-stat-label">Total Logs</div>
                <div class="history-stat-value"><?php echo $totalLogs; ?></div>
                <div class="history-stat-sub">All tracked habit records</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="glass-card history-stat-card">
                <div class="history-stat-label">Completed</div>
                <div class="history-stat-value"><?php echo $completedLogs; ?></div>
                <div class="history-stat-sub">Successful check-ins logged</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="glass-card history-stat-card">
                <div class="history-stat-label">Completion Rate</div>
                <div class="history-stat-value"><?php echo $completionRate; ?>%</div>
                <div class="history-stat-sub">How often you finished habits</div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="glass-card history-stat-card">
                <div class="history-stat-label">XP Earned</div>
                <div class="history-stat-value"><?php echo $totalXp; ?></div>
                <div class="history-stat-sub"><?php echo $activeDays; ?> active day<?php echo $activeDays === 1 ? "" : "s"; ?></div>
            </div>
        </div>
    </div>

    <div class="glass-card section-card mb-4">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h2 class="section-title mb-0">Filters</h2>

            <div class="history-filter-row">
                <a href="history.php?filter=all" class="history-filter-chip <?php echo $filter === 'all' ? 'active' : ''; ?>">All Time</a>
                <a href="history.php?filter=week" class="history-filter-chip <?php echo $filter === 'week' ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="history.php?filter=month" class="history-filter-chip <?php echo $filter === 'month' ? 'active' : ''; ?>">Last 30 Days</a>
            </div>
        </div>
    </div>

    <div class="glass-card section-card">
        <h2 class="section-title">Recent Activity</h2>

        <?php if (empty($groupedHistory)): ?>
            <div class="empty-state history-empty">
                <h5>No history yet</h5>
                <p class="mb-0">Complete a habit and your activity will start showing up here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedHistory as $date => $entries): ?>
                <div class="history-date-block">
                    <div class="history-date-title">
                        <?php echo date("l, F j, Y", strtotime($date)); ?>
                    </div>

                    <?php foreach ($entries as $entry): ?>
                        <div class="habit-item <?php echo ((int)$entry["completed"] === 1) ? 'habit-complete' : ''; ?> history-item">
                            <div class="habit-top">
                                <div class="habit-main">
                                    <div class="habit-name-row">
                                        <div class="habit-name">
                                            <?php echo htmlspecialchars($entry["habit_name"]); ?>
                                        </div>

                                        <div class="history-right-meta">
                                            <?php if ((int)$entry["completed"] === 1): ?>
                                                <span class="history-pill history-pill-complete">Completed</span>
                                            <?php else: ?>
                                                <span class="history-pill history-pill-missed">Not Completed</span>
                                            <?php endif; ?>

                                            <span class="xp-chip">+<?php echo (int)$entry["xp_earned"]; ?> XP</span>
                                            <span class="history-pill history-pill-multiplier">
                                                x<?php echo number_format((float)$entry["busyness_multiplier"], 2); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if (!empty($entry["description"])): ?>
                                        <p class="habit-desc"><?php echo htmlspecialchars($entry["description"]); ?></p>
                                    <?php endif; ?>

                                    <div class="history-meta-row">
                                        <span class="difficulty-badge <?php echo historyDifficultyClass($entry["weight"]); ?>">
                                            <?php echo historyDifficultyLabel($entry["weight"]); ?>
                                        </span>
                                        <span>Weight: <?php echo (int)$entry["weight"]; ?></span>
                                        <span>Log ID: <?php echo (int)$entry["log_id"]; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>