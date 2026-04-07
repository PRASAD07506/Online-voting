<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/face_helpers.php");
require_once("../config/election_helpers.php");
require_once("../config/identity_helpers.php");
require_once("../config/csrf.php");

$user_id = (int) $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
$settings = get_election_settings($conn);

if(!empty($user['has_voted'])){
    header("Location: dashboard.php");
    exit;
}

if(!face_image_exists($user_id)){
    header("Location: enroll_face.php");
    exit;
}

$faceVerified = isset($_SESSION['face_verified_user'], $_SESSION['face_verified_at'])
    && (int) $_SESSION['face_verified_user'] === $user_id
    && (int) $_SESSION['face_verified_at'] >= (time() - 600);

if(!$faceVerified){
    header("Location: verify_face.php");
    exit;
}

if(!is_election_open($settings)){
    unset($_SESSION['vote_deadline_at']);
    header("Location: dashboard.php");
    exit;
}

$voteDuration = max(30, (int) ($settings['vote_duration_seconds'] ?? 120));
$electionEndAt = !empty($settings['ends_at']) ? strtotime($settings['ends_at']) : null;

if(empty($_SESSION['vote_deadline_at']) || (int) $_SESSION['vote_deadline_at'] < time()){
    $deadline = time() + $voteDuration;
    if($electionEndAt) {
        $deadline = min($deadline, $electionEndAt);
    }
    $_SESSION['vote_deadline_at'] = $deadline;
}

$deadlineAt = (int) $_SESSION['vote_deadline_at'];
$error = '';
$success = '';
$demoOtp = '';

$voteIdentityVerified = isset($_SESSION['vote_identity_verified_user'], $_SESSION['vote_identity_verified_at'])
    && (int) $_SESSION['vote_identity_verified_user'] === $user_id
    && (int) $_SESSION['vote_identity_verified_at'] >= (time() - 600);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token();
}

if (isset($_POST['send_vote_otp'])) {
    if (time() > $deadlineAt) {
        $error = "Your voting timer expired. Please restart verification to continue.";
        unset($_SESSION['vote_deadline_at'], $_SESSION['face_verified_at'], $_SESSION['face_verified_user'], $_SESSION['vote_otp'], $_SESSION['vote_otp_user'], $_SESSION['vote_otp_expires_at'], $_SESSION['vote_identity_verified_user'], $_SESSION['vote_identity_verified_at'], $_SESSION['vote_aadhaar_masked']);
    } else {
        $aadhaar = normalize_aadhaar($_POST['aadhaar_number'] ?? '');

        if (!is_valid_aadhaar($aadhaar)) {
            $error = "Please enter a valid 12-digit Aadhaar number.";
        } else {
            $otp = generate_vote_otp();
            $_SESSION['vote_otp'] = $otp;
            $_SESSION['vote_otp_user'] = $user_id;
            $_SESSION['vote_otp_expires_at'] = time() + 300;
            $_SESSION['vote_aadhaar_masked'] = mask_aadhaar($aadhaar);
            unset($_SESSION['vote_identity_verified_user'], $_SESSION['vote_identity_verified_at']);
            $success = "OTP generated for " . $_SESSION['vote_aadhaar_masked'] . ".";
            $demoOtp = $otp;
        }
    }
}

if (isset($_POST['verify_vote_otp'])) {
    $aadhaar = normalize_aadhaar($_POST['aadhaar_number'] ?? '');
    $otpValue = trim($_POST['vote_otp'] ?? '');

    if (!is_valid_aadhaar($aadhaar)) {
        $error = "Please enter a valid 12-digit Aadhaar number.";
    } elseif (
        empty($_SESSION['vote_otp']) ||
        empty($_SESSION['vote_otp_user']) ||
        (int) $_SESSION['vote_otp_user'] !== $user_id
    ) {
        $error = "Generate an OTP before trying to verify it.";
    } elseif ((int) ($_SESSION['vote_otp_expires_at'] ?? 0) < time()) {
        $error = "The OTP expired. Please generate a new one.";
        unset($_SESSION['vote_otp'], $_SESSION['vote_otp_user'], $_SESSION['vote_otp_expires_at']);
    } elseif ($otpValue !== (string) $_SESSION['vote_otp']) {
        $error = "The OTP you entered is incorrect.";
    } else {
        $_SESSION['vote_identity_verified_user'] = $user_id;
        $_SESSION['vote_identity_verified_at'] = time();
        $_SESSION['vote_aadhaar_masked'] = mask_aadhaar($aadhaar);
        unset($_SESSION['vote_otp'], $_SESSION['vote_otp_user'], $_SESSION['vote_otp_expires_at']);
        $voteIdentityVerified = true;
        $success = "Aadhaar number and OTP verified for " . $_SESSION['vote_aadhaar_masked'] . ". You can now submit your vote.";
    }
}

if(isset($_POST['vote'])){
    if(time() > $deadlineAt){
        $error = "Your voting timer expired. Please restart verification to continue.";
        unset($_SESSION['vote_deadline_at'], $_SESSION['face_verified_at'], $_SESSION['face_verified_user'], $_SESSION['vote_identity_verified_user'], $_SESSION['vote_identity_verified_at'], $_SESSION['vote_aadhaar_masked'], $_SESSION['vote_otp'], $_SESSION['vote_otp_user'], $_SESSION['vote_otp_expires_at']);
    } elseif(!is_election_open($settings)) {
        $error = "Voting is no longer active.";
        unset($_SESSION['vote_deadline_at']);
    } elseif(!$voteIdentityVerified) {
        $error = "Complete Aadhaar OTP validation before submitting your vote.";
    } else {
        $cid = (int) $_POST['candidate_id'];

        if ($cid <= 0) {
            $error = "Please select a valid candidate.";
        } else {
            try {
                $conn->begin_transaction();

                $candidateStmt = $conn->prepare("SELECT id FROM candidates WHERE id = ? LIMIT 1");
                $candidateStmt->bind_param("i", $cid);
                $candidateStmt->execute();
                $candidateResult = $candidateStmt->get_result();
                $candidate = $candidateResult->fetch_assoc();
                $candidateStmt->close();

                if (!$candidate) {
                    throw new RuntimeException("Selected candidate does not exist.");
                }

                $userVoteGuardStmt = $conn->prepare("UPDATE users SET has_voted = 1 WHERE id = ? AND has_voted = 0");
                $userVoteGuardStmt->bind_param("i", $user_id);
                $userVoteGuardStmt->execute();
                $voteGuardUpdated = $userVoteGuardStmt->affected_rows;
                $userVoteGuardStmt->close();

                if ($voteGuardUpdated !== 1) {
                    throw new RuntimeException("Your vote has already been recorded.");
                }

                $insertVoteStmt = $conn->prepare("INSERT INTO votes(user_id,candidate_id) VALUES(?, ?)");
                $insertVoteStmt->bind_param("ii", $user_id, $cid);
                $insertVoteStmt->execute();
                $insertVoteStmt->close();

                $updateCandidateStmt = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
                $updateCandidateStmt->bind_param("i", $cid);
                $updateCandidateStmt->execute();
                $updatedCandidateRows = $updateCandidateStmt->affected_rows;
                $updateCandidateStmt->close();

                if ($updatedCandidateRows !== 1) {
                    throw new RuntimeException("Could not update candidate vote count.");
                }

                $conn->commit();

                unset($_SESSION['vote_deadline_at'], $_SESSION['vote_identity_verified_user'], $_SESSION['vote_identity_verified_at'], $_SESSION['vote_aadhaar_masked'], $_SESSION['vote_otp'], $_SESSION['vote_otp_user'], $_SESSION['vote_otp_expires_at']);
                header("Location: dashboard.php");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$result = $conn->query("SELECT * FROM candidates");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>

<body class="app-shell">

<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Cast Your Vote</span>
        <a href="dashboard.php" class="btn btn-outline-light landing-action">Back</a>
    </div>
</nav>

<div class="container page-section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card glass-card vote-shell-card interactive-panel" data-landing-shell>
                <span class="vote-orb one"></span>
                <span class="vote-orb two"></span>
                <div class="card-body p-4 p-md-5 position-relative">
                    <div class="vote-header-bar mb-4">
                        <div>
                            <span class="vote-topline mb-3">Protected Ballot</span>
                            <h3 class="mb-1">Vote for your candidate</h3>
                            <p class="text-muted mb-0">Select one candidate and submit your ballot once.</p>
                        </div>
                        <span class="badge text-bg-primary">One vote only</span>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>This ballot closes when the timer reaches zero.</span>
                        <strong id="voteCountdown" data-deadline="<?= $deadlineAt ?>">--:--</strong>
                    </div>

                    <div class="face-auth-block vote-identity-shell mb-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 position-relative">
                            <div>
                                <h4 class="h6 mb-1">Aadhaar and OTP check</h4>
                                <p class="mini-note mb-0">Enter your Aadhaar number and verify the OTP before the ballot can be submitted.</p>
                            </div>
                            <span class="brand-badge"><?= $voteIdentityVerified ? 'Verified' : 'Required' ?></span>
                        </div>

                        <?php if($demoOtp): ?>
                            <div class="alert alert-info">
                                Demo OTP for this local setup: <strong><?= htmlspecialchars($demoOtp) ?></strong>
                            </div>
                        <?php elseif(!$voteIdentityVerified && !empty($_SESSION['vote_otp']) && (int) ($_SESSION['vote_otp_expires_at'] ?? 0) >= time()): ?>
                            <div class="alert alert-info">
                                OTP already generated for <strong><?= htmlspecialchars($_SESSION['vote_aadhaar_masked'] ?? 'your Aadhaar number') ?></strong>. Enter it below to finish verification.
                            </div>
                        <?php endif; ?>

                        <?php if($voteIdentityVerified): ?>
                            <div class="alert alert-success mb-0">
                                Aadhaar OTP verification completed for <strong><?= htmlspecialchars($_SESSION['vote_aadhaar_masked'] ?? 'verified identity') ?></strong>.
                            </div>
                        <?php else: ?>
                            <div class="row g-3 position-relative">
                                <div class="col-md-7">
                                    <label for="aadhaar_number" class="form-label">Aadhaar number</label>
                                    <input
                                        type="text"
                                        id="aadhaar_number"
                                        name="aadhaar_number"
                                        class="form-control"
                                        inputmode="numeric"
                                        pattern="[0-9 ]{12,14}"
                                        maxlength="14"
                                        placeholder="1234 5678 9012"
                                        value="<?= htmlspecialchars($_POST['aadhaar_number'] ?? '') ?>"
                                        form="voteIdentityForm"
                                        required
                                    >
                                </div>
                                <div class="col-md-5">
                                    <label for="vote_otp" class="form-label">OTP</label>
                                    <input
                                        type="text"
                                        id="vote_otp"
                                        name="vote_otp"
                                        class="form-control"
                                        inputmode="numeric"
                                        maxlength="6"
                                        placeholder="6-digit OTP"
                                        value="<?= htmlspecialchars($_POST['vote_otp'] ?? '') ?>"
                                        form="voteIdentityForm"
                                    >
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3 position-relative">
                                <button type="submit" name="send_vote_otp" value="1" class="btn btn-outline-primary landing-action" form="voteIdentityForm">Send OTP</button>
                                <button type="submit" name="verify_vote_otp" value="1" class="btn btn-primary landing-action" form="voteIdentityForm">Verify OTP</button>
                            </div>
                            <div class="mini-note mt-2 position-relative">This is a demo OTP flow for the current project. It validates Aadhaar format and checksum locally and shows the OTP on-screen because no SMS gateway or UIDAI integration is configured.</div>
                            <form method="POST" id="voteIdentityForm">
                                <?= csrf_input() ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <form method="POST">
                        <?= csrf_input() ?>
                        <div class="row g-3 mb-4 vote-candidate-grid">
                            <?php while($row = $result->fetch_assoc()){ ?>
                                <div class="col-md-6">
                                    <div class="vote-option">
                                        <input type="radio" id="candidate-<?= $row['id'] ?>" name="candidate_id" value="<?= $row['id'] ?>" required>
                                        <label for="candidate-<?= $row['id'] ?>" class="h-100">
                                            <span class="d-block fw-semibold mb-1"><?= htmlspecialchars($row['name']) ?></span>
                                            <span class="text-muted"><?= htmlspecialchars($row['party']) ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 vote-submit-bar">
                            <div id="selectedCandidate" class="text-muted"><?= $voteIdentityVerified ? 'No candidate selected yet.' : 'Complete Aadhaar OTP verification first.' ?></div>
                            <button class="btn btn-success px-4 landing-action" name="vote" id="voteButton" disabled>Submit Vote</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var radios = document.querySelectorAll('input[name="candidate_id"]');
var voteButton = document.getElementById('voteButton');
var selectedCandidate = document.getElementById('selectedCandidate');
var voteCountdown = document.getElementById('voteCountdown');
var identityVerified = <?= $voteIdentityVerified ? 'true' : 'false' ?>;

var updateVoteButtonState = function () {
    var selectedRadio = document.querySelector('input[name="candidate_id"]:checked');
    voteButton.disabled = !(identityVerified && selectedRadio);
};

radios.forEach(function (radio) {
    radio.addEventListener('change', function () {
        var label = document.querySelector('label[for="' + radio.id + '"] .fw-semibold');
        selectedCandidate.textContent = 'Selected: ' + label.textContent;
        updateVoteButtonState();
    });
});

updateVoteButtonState();

if (voteCountdown) {
    var deadline = parseInt(voteCountdown.getAttribute('data-deadline'), 10) * 1000;

    var renderCountdown = function () {
        var remaining = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        var minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
        var seconds = String(remaining % 60).padStart(2, '0');
        voteCountdown.textContent = minutes + ':' + seconds;

        if (remaining <= 0) {
            voteButton.disabled = true;
            selectedCandidate.textContent = 'Voting time expired. Reloading...';
            window.location.href = 'dashboard.php';
        }
    };

    renderCountdown();
    window.setInterval(renderCountdown, 1000);
}
</script>
    <script src="../assets/js/theme-toggle.js"></script>
</body>
</html>
