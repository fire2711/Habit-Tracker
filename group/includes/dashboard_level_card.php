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