<?php

function ensure_candidate_requests_table($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS candidate_requests (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            candidate_name VARCHAR(100) NOT NULL,
            party_name VARCHAR(100) NOT NULL,
            manifesto TEXT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME NULL,
            UNIQUE KEY unique_user_request (user_id)
        )
    ");
}

function candidate_request_counts($conn) {
    ensure_candidate_requests_table($conn);
    $result = $conn->query("
        SELECT
            SUM(status = 'pending') AS pending_count,
            SUM(status = 'approved') AS approved_count,
            SUM(status = 'rejected') AS rejected_count
        FROM candidate_requests
    ");

    return $result ? $result->fetch_assoc() : [
        'pending_count' => 0,
        'approved_count' => 0,
        'rejected_count' => 0,
    ];
}
