<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/face_helpers.php");

$user_id = (int) $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$userName = $user['name'] ?? ($_SESSION['user_name'] ?? 'User');
$error = '';
$success = '';

if(isset($_POST['save_face'])){
    if(empty($_POST['face_image_data'])){
        $error = "Please capture your face before saving.";
    } elseif(save_face_image_from_data($user_id, $_POST['face_image_data'])) {
        $success = "Face enrolled successfully. You can continue to verification.";
    } else {
        $error = "We could not save your face image right now.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Face</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
</head>
<body class="app-shell">
<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Face Enrollment</span>
        <a href="dashboard.php" class="btn btn-outline-light">Back</a>
    </div>
</nav>

<div class="container page-section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card glass-card">
                <div class="card-body p-4 p-md-5">
                    <span class="brand-badge mb-3">Security Step</span>
                    <h1 class="h3 mb-2">Enroll your face, <?= htmlspecialchars($userName) ?></h1>
                    <p class="text-muted mb-4">Capture a clear photo from your webcam. This image will be used for live verification before voting.</p>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span><?= htmlspecialchars($success) ?></span>
                            <a href="verify_face.php" class="btn btn-sm btn-success">Verify Now</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="face-auth-block mb-4" data-face-auth data-face-mode="enroll" data-model-url="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/">
                            <div class="face-camera-shell mb-3">
                                <video id="faceVideo" class="face-video" autoplay muted playsinline></video>
                                <canvas id="faceCanvas" class="d-none"></canvas>
                            </div>
                            <div id="faceStatus" class="alert alert-secondary">Camera is preparing...</div>
                            <div class="d-grid gap-2">
                                <button type="button" id="captureFaceButton" class="btn btn-outline-primary">Capture Face</button>
                            </div>
                            <input type="hidden" name="face_image_data" id="faceImageData">
                            <img id="facePreview" class="img-fluid rounded-4 border mt-3 d-none" alt="Captured face preview">
                        </div>

                        <button class="btn btn-primary w-100 py-2" name="save_face" id="faceSubmitButton" disabled>Save Face</button>
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

