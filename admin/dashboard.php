<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/election_helpers.php");

$settings = get_election_settings($conn);
$status = election_status_label($settings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark shadow-sm">
        <div class="container">
            <span class="navbar-brand">Admin Panel</span>
            <a href="../auth/logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 mb-2">Dashboard</h1>
                        <p class="text-muted mb-4">Manage candidates, users, and election results from one place.</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="add_candidates.php" class="btn btn-primary w-100 py-3">Add Candidate</a>
                            </div>
                            <div class="col-md-4">
                                <a href="user.php" class="btn btn-outline-primary w-100 py-3">Manage Users</a>
                            </div>
                            <div class="col-md-4">
                                <a href="results.php" class="btn btn-success w-100 py-3">View Results</a>
                            </div>
                            <div class="col-md-4">
                                <a href="election.php" class="btn btn-warning w-100 py-3">Election Control</a>
                            </div>
                            <div class="col-md-4">
                                <a href="candidate_requests.php" class="btn btn-outline-dark w-100 py-3">Candidate Requests</a>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="soft-stat">
                                    <span class="text-muted">Election status</span>
                                    <strong><?= htmlspecialchars($status) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="soft-stat">
                                    <span class="text-muted">Vote timer</span>
                                    <strong><?= (int) ($settings['vote_duration_seconds'] ?? 120) ?> sec</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="soft-stat">
                                    <span class="text-muted">Election title</span>
                                    <strong><?= htmlspecialchars($settings['election_title'] ?? 'General Election') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
