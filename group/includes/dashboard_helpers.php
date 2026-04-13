<?php
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
?>