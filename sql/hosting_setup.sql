CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    attempts INT(11) DEFAULT 0,
    status ENUM('active','blocked') DEFAULT 'active',
    has_voted TINYINT(1) DEFAULT 0,
    role ENUM('user','admin') DEFAULT 'user',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS candidates (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) DEFAULT NULL,
    party VARCHAR(100) DEFAULT NULL,
    votes INT(11) DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS votes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    candidate_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_vote_per_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS election_settings (
    id TINYINT NOT NULL PRIMARY KEY,
    election_title VARCHAR(150) NOT NULL DEFAULT 'General Election',
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    vote_duration_seconds INT NOT NULL DEFAULT 120,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO election_settings (id, election_title, is_active, starts_at, ends_at, vote_duration_seconds)
SELECT 1, 'General Election', 0, NULL, NULL, 120
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM election_settings WHERE id = 1);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO candidates (name, party, votes)
SELECT 'Demo Candidate', 'Independent', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM candidates LIMIT 1);
