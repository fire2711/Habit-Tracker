<?php
require_once "db.php";
require_once "google_config.php";

function getMultiplierFromBusyMinutes(int $minutes): float {
    if ($minutes >= 300) return 1.50;
    if ($minutes >= 180) return 1.25;
    if ($minutes >= 60) return 1.10;
    return 1.00;
}

function getGoogleTokenRow(mysqli $conn, int $user_id): ?array {
    $stmt = $conn->prepare("
        SELECT access_token, refresh_token, expires_at
        FROM google_calendar_tokens
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function postFormRequest(string $url, array $fields): ?array {
    $postFields = http_build_query($fields);

    $options = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/x-www-form-urlencoded\r\n",
            "content" => $postFields,
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function postJsonRequest(string $url, array $payload, string $accessToken): ?array {
    $options = [
        "http" => [
            "method" => "POST",
            "header" =>
                "Authorization: Bearer " . $accessToken . "\r\n" .
                "Content-Type: application/json\r\n",
            "content" => json_encode($payload),
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function refreshGoogleAccessToken(mysqli $conn, int $user_id): ?string {
    $tokenRow = getGoogleTokenRow($conn, $user_id);
    if (!$tokenRow) {
        return null;
    }

    if (!empty($tokenRow["expires_at"]) && strtotime($tokenRow["expires_at"]) > time() + 60) {
        return $tokenRow["access_token"];
    }

    if (empty($tokenRow["refresh_token"])) {
        return null;
    }

    $tokenData = postFormRequest("https://oauth2.googleapis.com/token", [
        "client_id" => googleClientId(),
        "client_secret" => googleClientSecret(),
        "refresh_token" => $tokenRow["refresh_token"],
        "grant_type" => "refresh_token"
    ]);

    if (!$tokenData || isset($tokenData["error"])) {
        return null;
    }

    $newAccessToken = $tokenData["access_token"] ?? null;
    if (!$newAccessToken) {
        return null;
    }

    $expiresIn = (int)($tokenData["expires_in"] ?? 3600);
    $expiresAt = date("Y-m-d H:i:s", time() + $expiresIn);

    $stmt = $conn->prepare("
        UPDATE google_calendar_tokens
        SET access_token = ?, expires_at = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $newAccessToken, $expiresAt, $user_id);
    $stmt->execute();
    $stmt->close();

    return $newAccessToken;
}

function calculateBusyMinutes(array $busyBlocks): int {
    $total = 0;

    foreach ($busyBlocks as $block) {
        $start = strtotime($block["start"]);
        $end = strtotime($block["end"]);

        if ($start !== false && $end !== false && $end > $start) {
            $total += (int) round(($end - $start) / 60);
        }
    }

    return $total;
}

function updateTodayBusyness(mysqli $conn, int $user_id): ?array {
    $accessToken = refreshGoogleAccessToken($conn, $user_id);
    if (!$accessToken) {
        return null;
    }

    $timezone = "America/New_York";
    $start = new DateTime("today", new DateTimeZone($timezone));
    $end = new DateTime("tomorrow", new DateTimeZone($timezone));

    $data = postJsonRequest(
        "https://www.googleapis.com/calendar/v3/freeBusy",
        [
            "timeMin" => $start->format(DateTime::RFC3339),
            "timeMax" => $end->format(DateTime::RFC3339),
            "timeZone" => $timezone,
            "items" => [
                ["id" => "primary"]
            ]
        ],
        $accessToken
    );

    if (!$data || !isset($data["calendars"]["primary"]["busy"])) {
        return null;
    }

    $busyBlocks = $data["calendars"]["primary"]["busy"];
    $busyMinutes = calculateBusyMinutes($busyBlocks);
    $busyBlocksCount = count($busyBlocks);
    $multiplier = getMultiplierFromBusyMinutes($busyMinutes);
    $today = $start->format("Y-m-d");

    $stmt = $conn->prepare("
        INSERT INTO calendar_busyness (user_id, busyness_date, busy_minutes, busy_blocks, multiplier, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            busy_minutes = VALUES(busy_minutes),
            busy_blocks = VALUES(busy_blocks),
            multiplier = VALUES(multiplier),
            updated_at = NOW()
    ");
    $stmt->bind_param("isiid", $user_id, $today, $busyMinutes, $busyBlocksCount, $multiplier);
    $stmt->execute();
    $stmt->close();

    return [
        "busy_minutes" => $busyMinutes,
        "busy_blocks" => $busyBlocksCount,
        "multiplier" => $multiplier
    ];
}

function getTodayBusyness(mysqli $conn, int $user_id): ?array {
    $stmt = $conn->prepare("
        SELECT busy_minutes, busy_blocks, multiplier
        FROM calendar_busyness
        WHERE user_id = ? AND busyness_date = CURDATE()
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}
?>