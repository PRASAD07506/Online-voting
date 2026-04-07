<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv("HOST_DB_HOST") ?: "sql206.infinityfree.com";
$user = getenv("HOST_DB_USER") ?: "if0_41575875";
$pass = getenv("HOST_DB_PASS") ?: "Prasad@2004";
$name = getenv("HOST_DB_NAME") ?: "if0_41575875_voting";
$port = (int) (getenv("HOST_DB_PORT") ?: 3307);

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
