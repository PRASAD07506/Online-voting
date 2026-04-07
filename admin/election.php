<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/election_helpers.php");
require_once("../config/csrf.php");

$error = '';
$success = '';

ensure_election_settings_table($conn);

if(isset($_POST['save_election'])){
    require_valid_csrf_token();
    $title = trim($_POST['election_title']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $startsAt = !empty($_POST['starts_at']) ? date('Y-m-d H:i:s', strtotime($_POST['starts_at'])) : null;
    $endsAt = !empty($_POST['ends_at']) ? date('Y-m-d H:i:s', strtotime($_POST['ends_at'])) : null;
    $duration = max(30, (int) $_POST['vote_duration_seconds']);

    if($title === ''){
        $error = 'Election title is required.';
    } elseif ($startsAt && $endsAt && strtotime($endsAt) <= strtotime($startsAt)) {
        $error = 'End time must be later than start time.';
    } else {
        $stmt = $conn->prepare("
            UPDATE election_settings
            SET election_title = ?, is_active = ?, starts_at = ?, ends_at = ?, vote_duration_seconds = ?
            WHERE id = 1
        ");
        $stmt->bind_param("sissi", $title, $isActive, $startsAt, $endsAt, $duration);
        if($stmt->execute()){
            $success = 'Election settings saved successfully.';
        } else {
            $error = 'Could not save election settings.';
        }
        $stmt->close();
    }
}

$settings = get_election_settings($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-shell">
<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Election Control</span>
        <a href="dashboard.php" class="btn btn-outline-light">Back</a>
    </div>
</nav>

<div class="container page-section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-2">Create or update voting settings</h1>
                    <p class="text-muted mb-4">Admins can control when voting opens and how long each voter gets on the ballot page.</p>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">
                        <?= csrf_input() ?>
                        <div class="col-12">
                            <label for="election_title" class="form-label">Election title</label>
                            <input type="text" id="election_title" name="election_title" class="form-control" value="<?= htmlspecialchars($settings['election_title'] ?? 'General Election') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="starts_at" class="form-label">Start time</label>
                            <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="<?= !empty($settings['starts_at']) ? date('Y-m-d\TH:i', strtotime($settings['starts_at'])) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="ends_at" class="form-label">End time</label>
                            <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="<?= !empty($settings['ends_at']) ? date('Y-m-d\TH:i', strtotime($settings['ends_at'])) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="vote_duration_seconds" class="form-label">Per-voter timer (seconds)</label>
                            <input type="number" min="30" max="1800" id="vote_duration_seconds" name="vote_duration_seconds" class="form-control" value="<?= (int) ($settings['vote_duration_seconds'] ?? 120) ?>" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= !empty($settings['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Voting is active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="save_election" class="btn btn-primary">Save Election Settings</button>
                        </div>
                    </form>

                    <div class="row g-3 mt-4">
                        <div class="col-md-4">
                            <div class="soft-stat">
                                <span class="text-muted">Status</span>
                                <strong><?= htmlspecialchars(election_status_label($settings)) ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="soft-stat">
                                <span class="text-muted">Timer</span>
                                <strong><?= (int) ($settings['vote_duration_seconds'] ?? 120) ?> sec</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="soft-stat">
                                <span class="text-muted">Attempts</span>
                                <strong>3 max / voter</strong>
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
