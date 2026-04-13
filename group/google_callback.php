#!/usr/local/bin/php
<?php
session_start();
require_once "db.php";
require_once "google_config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET["error"])) {
    if ($_GET["error"] === "access_denied") {
        $_SESSION["calendar_error"] = "Google Calendar connection was cancelled.";
    } else {
        $_SESSION["calendar_error"] = "Google authorization failed.";
    }

    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET["code"]) || !isset($_GET["state"])) {
    $_SESSION["calendar_error"] = "Missing OAuth response data.";
    header("Location: dashboard.php");
    exit();
}

if (!isset($_SESSION["google_oauth_state"])) {
    $_SESSION["calendar_error"] = "Missing saved OAuth state. Please try connecting again.";
    header("Location: dashboard.php");
    exit();
}

if ($_GET["state"] !== $_SESSION["google_oauth_state"]) {
    unset($_SESSION["google_oauth_state"]);
    $_SESSION["calendar_error"] = "Invalid OAuth state. Please try again.";
    header("Location: dashboard.php");
    exit();
}

unset($_SESSION["google_oauth_state"]);

$user_id = (int)$_SESSION["user_id"];
$code = $_GET["code"];

$postFields = http_build_query([
    "code" => $code,
    "client_id" => googleClientId(),
    "client_secret" => googleClientSecret(),
    "redirect_uri" => googleRedirectUri(),
    "grant_type" => "authorization_code"
]);

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/x-www-form-urlencoded\r\n",
        "content" => $postFields,
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);
$response = file_get_contents("https://oauth2.googleapis.com/token", false, $context);

if ($response === false) {
    $_SESSION["calendar_error"] = "Failed to contact Google for token exchange.";
    header("Location: dashboard.php");
    exit();
}

$tokenData = json_decode($response, true);

if (!$tokenData || isset($tokenData["error"])) {
    $_SESSION["calendar_error"] = "Google OAuth token exchange failed.";
    header("Location: dashboard.php");
    exit();
}

$accessToken = $tokenData["access_token"] ?? "";
$refreshToken = $tokenData["refresh_token"] ?? null;
$expiresIn = (int)($tokenData["expires_in"] ?? 3600);
$expiresAt = date("Y-m-d H:i:s", time() + $expiresIn);

if ($accessToken === "") {
    $_SESSION["calendar_error"] = "Google did not return a valid access token.";
    header("Location: dashboard.php");
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO google_calendar_tokens (user_id, access_token, refresh_token, expires_at)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        access_token = VALUES(access_token),
        refresh_token = COALESCE(VALUES(refresh_token), refresh_token),
        expires_at = VALUES(expires_at)
");

if (!$stmt) {
    $_SESSION["calendar_error"] = "Database prepare failed while saving Google tokens.";
    header("Location: dashboard.php");
    exit();
}

$stmt->bind_param("isss", $user_id, $accessToken, $refreshToken, $expiresAt);
$stmt->execute();

if ($stmt->error) {
    $stmt->close();
    $_SESSION["calendar_error"] = "Database error while saving Google tokens.";
    header("Location: dashboard.php");
    exit();
}

$stmt->close();

header("Location: dashboard.php?google_connected=1");
exit();
?>