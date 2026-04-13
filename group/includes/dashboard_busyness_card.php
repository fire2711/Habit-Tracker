<div class="glass-card busyness-compact-card mb-4">
    <div class="busyness-compact-wrap">
        <div class="busyness-compact-main">
            <div class="busyness-label">Today’s Busyness</div>

            <div class="busyness-main-row">
                <div class="busyness-value">x<?php echo number_format($todayMultiplier, 2); ?></div>
                <span class="busyness-status-pill">
                    <?php echo $calendarFeatureEnabled ? "Calendar synced" : "Not connected"; ?>
                </span>
            </div>

            <div class="busyness-description">
                <?php if ($calendarFeatureEnabled): ?>
                    XP scales with how packed your day is.
                <?php else: ?>
                    Connect Google Calendar to adjust XP based on your schedule.
                <?php endif; ?>
            </div>
        </div>

        <div class="busyness-stats">
            <div class="busyness-stat-box">
                <div class="busyness-stat-top"><?php echo $todayBusyMinutes; ?> min</div>
                <div class="busyness-stat-bottom">Scheduled time</div>
            </div>

            <div class="busyness-stat-box">
                <div class="busyness-stat-top"><?php echo $todayBusyBlocks; ?></div>
                <div class="busyness-stat-bottom">Busy blocks</div>
            </div>
        </div>
    </div>
</div>