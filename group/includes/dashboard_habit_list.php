<div class="glass-card section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-title mb-0">Your Habits</div>
        <div class="d-flex align-items-center gap-2">
            <span class="small-muted"><?php echo $habits->num_rows; ?> total</span>
            <a href="manage_habit.php" class="btn btn-sm btn-primary">Add Habit</a>
        </div>
    </div>

    <?php if ($habits->num_rows > 0): ?>
        <div class="habit-list">
            <?php while ($habit = $habits->fetch_assoc()): ?>
                <?php
                    $isCompletedToday = ($habit["last_completed_date"] === $today);
                    $baseReward = xpReward((int)$habit["weight"]);
                    $displayReward = (int) round($baseReward * $todayMultiplier);
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
                        <span class="xp-chip">⚡ +<?php echo $displayReward; ?> XP</span>
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
                        <a href="manage_habit.php?edit=<?php echo (int)$habit["habit_id"]; ?>" class="btn action-btn-edit">
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
            <p class="mb-3">Create your first habit and start leveling up.</p>
            <a href="manage_habit.php" class="btn btn-primary">Create Habit</a>
        </div>
    <?php endif; ?>
</div>