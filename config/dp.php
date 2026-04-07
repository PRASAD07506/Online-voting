<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv("DB_HOST") ?: "localhost";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$name = getenv("DB_NAME") ?: "voting";
$port = (int) (getenv("DB_PORT") ?: 3307);

$conn = @new mysqli(
    $host,
    $user,
    $pass,
    $name,
    $port
);

if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
