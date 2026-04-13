#!/usr/local/bin/php
<?php
session_start();
require_once "google_config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$state = bin2hex(random_bytes(16));
$_SESSION["google_oauth_state"] = $state;

$params = [
    "client_id" => googleClientId(),
    "redirect_uri" => googleRedirectUri(),
    "response_type" => "code",
    "scope" => "email https://www.googleapis.com/auth/calendar.readonly",
    "access_type" => "offline",
    "prompt" => "consent",
    "state" => $state
];

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);

header("Location: " . $authUrl);
exit();
?>