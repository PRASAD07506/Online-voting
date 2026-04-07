<?php
session_start();

require_once("../config/face_helpers.php");

$error = '';
$success = '';

if(isset($_SESSION['user_id'])){
    $destination = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
        ? '../admin/dashboard.php'
        : '../user/dashboard.php';
    header("Location: " . $destination);
    exit;
}

try {
    include("../config/db.php");

    if(isset($_POST['register'])){
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if(strlen($name) < 2){
            $error = "Please enter a valid name.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");

            if(!$checkStmt){
                $error = "Unable to prepare email validation right now.";
            } else {
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $checkStmt->store_result();
                $existingUser = $checkStmt->num_rows > 0;
                $checkStmt->close();

                if($existingUser){
                    $error = "That email is already registered.";
                } else {
                    $pass = password_hash($password, PASSWORD_DEFAULT);
                    $status = 'active';
                    $role = 'user';
                    $attempts = 0;
                    $hasVoted = 0;
                    $stmt = $conn->prepare("INSERT INTO users(name,email,password,attempts,status,has_voted,role) VALUES(?,?,?,?,?,?,?)");

                    if(!$stmt){
                        $error = "Unable to create the account right now.";
                    } else {
                        $stmt->bind_param("sssisis", $name, $email, $pass, $attempts, $status, $hasVoted, $role);

                        if($stmt->execute()){
                            $newUserId = (int) $stmt->insert_id;

                            if(empty($_POST['face_image_data'])){
                                $conn->query("DELETE FROM users WHERE id = " . $newUserId);
                                $error = "Please capture your face before creating the account.";
                            } elseif(!save_face_image_from_data($newUserId, $_POST['face_image_data'])) {
                                $conn->query("DELETE FROM users WHERE id = " . $newUserId);
                                $error = "Account details were received, but the face image could not be saved. Please try again.";
                            } else {
                                $success = "Account created successfully with face enrollment. You can log in now.";
                                $_POST = array();
                            }
                        } else {
                            $error = "Unable to create the account right now.";
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    $error = "Registration failed on the server: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
</head>
<body class="app-shell">
<div class="container min-vh-100 d-flex justify-content-center align-items-center py-5 auth-register-shell">
    <div class="row g-4 align-items-stretch w-100">
        <div class="col-lg-5">
            <div class="card hero-card register-panel h-100 interactive-panel" data-tilt-card>
                <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-between register-content">
                    <div>
                        <img src="../assets/images/brand-mark.svg" alt="Voting system logo" class="brand-logo mb-4">
                        <div class="floating-chip mb-3">
                            <span>Smart Registration</span>
                        </div>
                        <h1 class="display-6 fw-bold mb-3">Secure digital voting starts with a trusted profile.</h1>
                        <p class="mini-note mb-4">Create your account with a face capture now, then use live verification later before entering the ballot.</p>
                    </div>

                    <div class="register-visual-stage mb-4" aria-hidden="true">
                        <div class="visual-backdrop"></div>
                        <div class="visual-grid"></div>
                        <div class="visual-orb orb-one"></div>
                        <div class="visual-orb orb-two"></div>
                        <div class="visual-ring ring-one"></div>
                        <div class="visual-ring ring-two"></div>

                        <div class="visual-main-card">
                            <div class="visual-main-header">
                                <span class="visual-live-dot"></span>
                                <span>Identity confidence</span>
                            </div>
                            <div class="visual-avatar-stack">
                                <div class="visual-avatar-shell">
                                    <img src="../assets/images/brand-mark.svg" alt="" class="visual-avatar visual-avatar-primary">
                                </div>
                                <div class="visual-scan-beam"></div>
                            </div>
                            <div class="visual-progress">
                                <span class="visual-progress-label">Verification readiness</span>
                                <div class="visual-progress-track">
                                    <div class="visual-progress-bar"></div>
                                </div>
                            </div>
                        </div>

                        <div class="visual-floating-card visual-card-top">
                            <img src="../assets/images/security-badge.svg" alt="">
                            <div>
                                <strong>AI-assisted security look</strong>
                                <span>Motion-ready identity panel</span>
                            </div>
                        </div>

                        <div class="visual-floating-card visual-card-bottom">
                            <img src="../assets/images/camera-badge.svg" alt="">
                            <div>
                                <strong>Face setup included</strong>
                                <span>Register with identity capture from the start</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-3">
                        <div class="feature-tile">
                            <img src="../assets/images/security-badge.svg" alt="Security badge">
                            <div>
                                <div class="fw-semibold">Identity-first security</div>
                                <div class="mini-note">Face enrollment adds an extra layer before a ballot can be submitted.</div>
                            </div>
                        </div>
                        <div class="feature-tile">
                            <img src="../assets/images/camera-badge.svg" alt="Camera badge">
                            <div>
                                <div class="fw-semibold">Face enrollment at signup</div>
                                <div class="mini-note">Create the account only after your face is captured, so identity setup starts immediately.</div>
                            </div>
                        </div>
                        <div class="feature-tile">
                            <img src="../assets/images/brand-mark.svg" alt="Brand logo">
                            <div>
                                <div class="fw-semibold">Fast voter access</div>
                                <div class="mini-note">A cleaner dashboard and guided steps make the full flow easier to complete.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card glass-card register-form-card elevate-hover ms-lg-auto interactive-panel" data-tilt-card>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="brand-badge mb-3">Create Account</span>
                        <h3 class="mb-2">Join the voting system</h3>
                        <p class="text-muted mb-0">Create your account now and capture your face as part of registration.</p>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" class="register-form-shell">
                        <div class="mb-3 form-field-shell">
                            <span class="field-glow"></span>
                            <input type="text" name="name" class="form-control" placeholder="Full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                        </div>
                        <div class="mb-3 form-field-shell">
                            <span class="field-glow"></span>
                            <input type="email" name="email" class="form-control" placeholder="Email address" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>
                        <div class="mb-3 form-field-shell">
                            <span class="field-glow"></span>
                            <div class="input-group">
                                <input type="password" name="password" id="registerPassword" class="form-control" placeholder="Password (min 6 characters)" required>
                                <button type="button" class="btn btn-outline-secondary" data-toggle-password="#registerPassword">Show</button>
                            </div>
                            <div class="password-strength" aria-hidden="true">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-strength-label" id="passwordStrengthLabel">Use 6+ characters for a stronger password.</div>
                        </div>

                        <div class="face-auth-block mb-4" data-face-auth data-face-mode="enroll" data-model-url="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div>
                                    <h4 class="h6 mb-1">Face validation at registration</h4>
                                    <p class="mini-note mb-0">Capture a clear face image now. Registration will only finish after a valid face photo is saved.</p>
                                </div>
                                <img src="../assets/images/camera-badge.svg" alt="Camera icon" width="42" height="42">
                            </div>
                            <div class="face-camera-shell mb-3">
                                <video id="faceVideo" class="face-video" autoplay muted playsinline></video>
                                <canvas id="faceCanvas" class="d-none"></canvas>
                            </div>
                            <div id="faceStatus" class="alert alert-secondary">Camera is preparing...</div>
                            <div class="d-grid gap-2">
                                <button type="button" id="captureFaceButton" class="btn btn-outline-primary">Capture Face</button>
                            </div>
                            <input type="hidden" name="face_image_data" id="faceImageData" value="<?= isset($_POST['face_image_data']) ? htmlspecialchars($_POST['face_image_data']) : '' ?>">
                            <img id="facePreview" class="img-fluid rounded-4 border mt-3 <?= !empty($_POST['face_image_data']) ? '' : 'd-none' ?>" alt="Captured face preview" <?= !empty($_POST['face_image_data']) ? 'src="' . htmlspecialchars($_POST['face_image_data']) . '"' : '' ?>>
                            <div class="alert alert-info mb-0">
                                Capture your face first, then submit the registration form to create a fully enrolled account.
                            </div>
                        </div>

                        <button class="btn btn-success w-100 py-2 register-submit" name="register" id="faceSubmitButton" disabled>
                            <span>Register</span>
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted">Already have an account?</span>
                        <a href="user_login.php" class="text-decoration-none fw-semibold">User Login</a>
                    </div>

                    <div class="text-center mt-2">
                        <a href="admin_login.php" class="text-decoration-none">Admin Login</a>
                    </div>

                    <div class="text-center mt-4 register-footer">
                        Copyright &copy; 2026 Online Voting System. All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/face-auth.js"></script>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.querySelector(button.getAttribute('data-toggle-password'));
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.textContent = isPassword ? 'Hide' : 'Show';
    });
});

var passwordInput = document.getElementById('registerPassword');
var passwordStrengthBar = document.getElementById('passwordStrengthBar');
var passwordStrengthLabel = document.getElementById('passwordStrengthLabel');

if (passwordInput && passwordStrengthBar && passwordStrengthLabel) {
    passwordInput.addEventListener('input', function () {
        var value = passwordInput.value;
        var score = 0;

        if (value.length >= 6) score += 1;
        if (/[A-Z]/.test(value)) score += 1;
        if (/[0-9]/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

        var labels = [
            'Use 6+ characters for a stronger password.',
            'Weak password strength.',
            'Fair password strength.',
            'Strong password strength.',
            'Excellent password strength.'
        ];

        passwordStrengthBar.style.width = (score * 25) + '%';
        passwordStrengthBar.setAttribute('data-score', score);
        passwordStrengthLabel.textContent = value.length ? labels[score] : labels[0];
    });
}

document.querySelectorAll('[data-tilt-card]').forEach(function (card) {
    card.addEventListener('mousemove', function (event) {
        var rect = card.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        var rotateX = ((y / rect.height) - 0.5) * -8;
        var rotateY = ((x / rect.width) - 0.5) * 8;

        card.style.setProperty('--rotate-x', rotateX.toFixed(2) + 'deg');
        card.style.setProperty('--rotate-y', rotateY.toFixed(2) + 'deg');
        card.style.setProperty('--spotlight-x', x.toFixed(0) + 'px');
        card.style.setProperty('--spotlight-y', y.toFixed(0) + 'px');
    });

    card.addEventListener('mouseleave', function () {
        card.style.setProperty('--rotate-x', '0deg');
        card.style.setProperty('--rotate-y', '0deg');
        card.style.setProperty('--spotlight-x', '50%');
        card.style.setProperty('--spotlight-y', '50%');
    });
});
</script>
</body>
</html>
