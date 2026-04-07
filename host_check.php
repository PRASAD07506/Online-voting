<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Hosting Check</h2>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";

try {
    include __DIR__ . "/config/db.php";
    echo "<p>Database connection: OK</p>";

    $tables = ['users', 'candidates', 'votes', 'election_settings', 'candidate_requests'];

    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
        echo "<p>Table <strong>{$table}</strong>: " . ($result && $result->num_rows > 0 ? "found" : "missing") . "</p>";
    }

    echo "<p>Uploads folder writable: " . (is_writable(__DIR__ . "/uploads") ? "yes" : "no") . "</p>";
    echo "<p>Faces folder writable: " . (is_writable(__DIR__ . "/uploads/faces") ? "yes" : "no") . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>Hosting check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
