<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/face_helpers.php");
require_once("../config/csrf.php");

$user_id = (int) $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$userName = $user['name'] ?? ($_SESSION['user_name'] ?? 'User');
$error = '';
$facePath = "../" . face_image_relative_path($user_id);
$attempts = (int) ($user['attempts'] ?? 0);
$remainingAttempts = max(0, 3 - $attempts);
$verificationNonce = $_SESSION['face_verification_nonce'] ?? bin2hex(random_bytes(16));
$_SESSION['face_verification_nonce'] = $verificationNonce;

if(!face_image_exists($user_id)){
    header("Location: enroll_face.php");
    exit;
}

if(isset($_POST['complete_verification'])){
    require_valid_csrf_token();
    if($attempts >= 3){
        $error = "You have used all 3 verification attempts. Please ask the admin to reset your attempts.";
    } elseif (
        !isset($_SESSION['face_match_confirmed_user'], $_SESSION['face_match_confirmed_at']) ||
        (int) $_SESSION['face_match_confirmed_user'] !== $user_id ||
        (time() - (int) $_SESSION['face_match_confirmed_at']) > 30
    ) {
        $error = "Live match confirmation expired. Please run verification again.";
    } else {
        $conn->query("UPDATE users SET attempts = 0 WHERE id = $user_id");
        unset($_SESSION['face_match_confirmed_user'], $_SESSION['face_match_confirmed_at']);
        $_SESSION['face_verified_user'] = $user_id;
        $_SESSION['face_verified_at'] = time();
        header("Location: vote.php");
        exit;
    }

    if($error && $attempts < 3){
        $conn->query("UPDATE users SET attempts = LEAST(attempts + 1, 3) WHERE id = $user_id");
        $user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
        $attempts = (int) ($user['attempts'] ?? 0);
        $remainingAttempts = max(0, 3 - $attempts);
        if($attempts >= 3){
            $error = "You have used all 3 verification attempts. Please ask the admin to reset your attempts.";
        } else {
            $error = "Live face verification did not pass. Attempts remaining: " . $remainingAttempts . ".";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Face</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
</head>
<body class="app-shell">
<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Live Face Verification</span>
        <a href="dashboard.php" class="btn btn-outline-light">Back</a>
    </div>
</nav>

<div class="container page-section">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-4">
            <div class="card glass-card h-100">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Reference face</h1>
                    <p class="text-muted">We compare your live webcam feed against your enrolled image.</p>
                    <img src="<?= htmlspecialchars($facePath) ?>" id="referenceFaceImage" class="face-reference" alt="Reference face image">
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-body p-4 p-md-5">
                    <span class="brand-badge mb-3">Verification Step</span>
                    <h2 class="h3 mb-2">Verify your identity, <?= htmlspecialchars($userName) ?></h2>
                    <p class="text-muted mb-4">Look at the camera and keep your face centered. When the live match passes, you can continue to the ballot.</p>
                    <div class="alert <?= $remainingAttempts > 0 ? 'alert-info' : 'alert-danger' ?>">
                        Face verification attempts remaining: <strong><?= $remainingAttempts ?></strong> of 3
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="face-auth-block mb-4" data-face-auth data-face-mode="verify" data-face-threshold="0.5" data-model-url="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/" data-verify-signal-url="verify_face_signal.php" data-csrf-token="<?= htmlspecialchars(get_csrf_token()) ?>" data-verification-nonce="<?= htmlspecialchars($verificationNonce) ?>">
                            <div class="face-camera-shell mb-3">
                                <video id="faceVideo" class="face-video" autoplay muted playsinline></video>
                                <canvas id="faceCanvas" class="d-none"></canvas>
                            </div>
                            <div id="faceStatus" class="alert alert-secondary">Camera is preparing...</div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="runFaceVerification" class="btn btn-outline-primary" <?= $remainingAttempts <= 0 ? 'disabled' : '' ?>>Check Again</button>
                                <span id="faceMatchMessage" class="text-muted align-self-center">Waiting for live comparison.</span>
                            </div>
                        </div>

                        <?= csrf_input() ?>
                        <button class="btn btn-success w-100 py-2" name="complete_verification" id="faceSubmitButton" disabled>Continue To Vote</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/face-auth.js"></script>
</body>
</html>
