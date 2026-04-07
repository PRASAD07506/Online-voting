<?php

function ensure_election_settings_table($conn) {
    $conn->query("
        CREATE TABLE IF NOT EXISTS election_settings (
            id TINYINT NOT NULL PRIMARY KEY,
            election_title VARCHAR(150) NOT NULL DEFAULT 'General Election',
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            vote_duration_seconds INT NOT NULL DEFAULT 120,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $conn->query("
        INSERT INTO election_settings (id, election_title, is_active, starts_at, ends_at, vote_duration_seconds)
        SELECT 1, 'General Election', 0, NULL, NULL, 120
        FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM election_settings WHERE id = 1)
    ");
}

function get_election_settings($conn) {
    ensure_election_settings_table($conn);
    $result = $conn->query("SELECT * FROM election_settings WHERE id = 1 LIMIT 1");
    return $result ? $result->fetch_assoc() : null;
}

function is_election_open($settings) {
    if (!$settings || empty($settings['is_active'])) {
        return false;
    }

    $now = time();
    $startOk = empty($settings['starts_at']) || strtotime($settings['starts_at']) <= $now;
    $endOk = empty($settings['ends_at']) || strtotime($settings['ends_at']) >= $now;

    return $startOk && $endOk;
}

function election_status_label($settings) {
    if (!$settings) {
        return 'Not Configured';
    }

    if (!empty($settings['is_active']) && is_election_open($settings)) {
        return 'Live';
    }

    if (!empty($settings['is_active']) && !empty($settings['starts_at']) && strtotime($settings['starts_at']) > time()) {
        return 'Scheduled';
    }

    return 'Closed';
}
