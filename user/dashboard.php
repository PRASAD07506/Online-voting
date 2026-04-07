<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/face_helpers.php");
require_once("../config/election_helpers.php");

$user_id = (int) $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$userName = $user['name'] ?? ($_SESSION['user_name'] ?? 'User');
$hasVoted = !empty($user['has_voted']);
$faceEnrolled = face_image_exists($user_id);
$faceVerified = isset($_SESSION['face_verified_user'], $_SESSION['face_verified_at'])
    && (int) $_SESSION['face_verified_user'] === $user_id
    && (int) $_SESSION['face_verified_at'] >= (time() - 600);
$settings = get_election_settings($conn);
$electionOpen = is_election_open($settings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>

<body class="app-shell">

<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Voting System</span>
        <a href="../auth/logout.php" class="btn btn-danger landing-action">Logout</a>
    </div>
</nav>

<div class="container page-section">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-8">
            <div class="card hero-card h-100 dashboard-shell-card interactive-panel" data-landing-shell>
                <span class="dashboard-orb one"></span>
                <span class="dashboard-orb two"></span>
                <div class="dashboard-hero-glow"></div>
                <div class="card-body p-4 p-md-5 position-relative">
                    <div class="dashboard-hero-layout">
                        <div>
                            <span class="dashboard-topline mb-3">Verified Voter Space</span>
                            <span class="brand-badge mb-3">Voter Dashboard</span>
                            <h1 class="h2 mb-2">Welcome, <?= htmlspecialchars($userName) ?></h1>
                            <p class="text-muted mb-4">
                                Review your voting status and continue to the ballot when you're ready.
                            </p>

                            <?php if($hasVoted): ?>
                                <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span>Your vote has already been submitted successfully.</span>
                                    <a href="../auth/logout.php" class="btn btn-sm btn-outline-success">Finish Session</a>
                                </div>
                            <?php elseif(!$electionOpen): ?>
                                <div class="alert alert-warning">Voting is currently closed. Please wait for the admin to open the election window.</div>
                            <?php elseif(!$faceEnrolled): ?>
                                <div class="alert alert-warning">Face verification is required before voting. Enroll your face first.</div>
                                <a href="enroll_face.php" class="btn btn-primary btn-lg landing-action">Enroll Face</a>
                            <?php elseif(!$faceVerified): ?>
                                <div class="alert alert-info">Your face is enrolled. Complete live verification before entering the ballot.</div>
                                <a href="verify_face.php" class="btn btn-primary btn-lg landing-action">Verify Face</a>
                            <?php else: ?>
                                <div class="alert alert-primary">You are verified and ready to cast your vote.</div>
                                <a href="vote.php" class="btn btn-primary btn-lg landing-action">Vote Now</a>
                            <?php endif; ?>

                            <div class="status-pill-grid">
                                <div class="status-pill">
                                    <strong><?= $hasVoted ? 'Completed' : 'Pending' ?></strong>
                                    <span>Voting status</span>
                                </div>
                                <div class="status-pill">
                                    <strong><?= $faceEnrolled ? ($faceVerified ? 'Verified' : 'Enrolled') : 'Not Enrolled' ?></strong>
                                    <span>Identity status</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <img src="../assets/images/brand-mark.svg" alt="Voting illustration" class="dashboard-visual">
                        </div>
                    </div>

                    <div class="dashboard-gallery">
                        <div class="dashboard-gallery-card">
                            <img src="../assets/images/security-badge.svg" alt="Secure voting badge">
                            <div class="fw-semibold mt-2">Secure Voting</div>
                            <div class="mini-note">Face verification and timing controls help keep the process fair.</div>
                        </div>
                        <div class="dashboard-gallery-card">
                            <img src="../assets/images/camera-badge.svg" alt="Camera verification badge">
                            <div class="fw-semibold mt-2">Verified Identity</div>
                            <div class="mini-note">Your account follows a guided verification flow before voting.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card h-100 dashboard-shell-card">
                <span class="dashboard-orb one"></span>
                <span class="dashboard-orb two"></span>
                <div class="card-body p-4 position-relative">
                    <h2 class="h5 mb-3">Quick Status</h2>
                    <div class="soft-stat mb-3">
                        <span class="text-muted">Account</span>
                        <strong><?= htmlspecialchars($userName) ?></strong>
                    </div>
                    <div class="soft-stat">
                        <span class="text-muted">Voting status</span>
                        <strong><?= $hasVoted ? 'Completed' : 'Pending' ?></strong>
                    </div>
                    <div class="soft-stat mt-3">
                        <span class="text-muted">Face setup</span>
                        <strong><?= $faceEnrolled ? ($faceVerified ? 'Verified' : 'Enrolled') : 'Not Enrolled' ?></strong>
                    </div>
                    <div class="soft-stat mt-3">
                        <span class="text-muted">Election</span>
                        <strong><?= htmlspecialchars($settings['election_title'] ?? 'General Election') ?></strong>
                    </div>
                    <div class="soft-stat mt-3">
                        <span class="text-muted">Timer</span>
                        <strong><?= (int) ($settings['vote_duration_seconds'] ?? 120) ?> sec</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme-toggle.js"></script>
</body>
</html>
