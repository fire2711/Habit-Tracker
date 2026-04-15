<div class="glass-card hero-card hero-card-with-settings mb-4">
    <a href="settings.php"
        class="settings-corner-btn"
        title="Settings"
        style="position: absolute; top: 10px; right: 18px;">
    <i class="bi bi-gear-fill"></i>
</a>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="hero-title">
                Welcome back, <?php echo htmlspecialchars($name); ?> 👾
            </div>
            <p class="hero-subtitle">
                Track habits, earn XP, level up, and build consistency.
            </p>
        </div>

        <div class="top-actions d-flex gap-2 flex-wrap">
            <a href="connect_google.php" class="btn btn-soft-primary">Link Google Calendar</a>
            <a href="history.php" class="btn btn-history">History</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</div>