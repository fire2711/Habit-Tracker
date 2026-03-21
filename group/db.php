<?php
$config = parse_ini_file("../../../db_config.ini");

if ($config === false) {
    die("Failed to load database config file.");
}

$conn = new mysqli(
    $config["servername"],
    $config["username"],
    $config["password"],
    $config["dbname"],
    (int)$config["port"]
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>