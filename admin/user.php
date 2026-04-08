<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/csrf.php");

$flashMessage = $_SESSION['user_management_message'] ?? '';
$flashType = $_SESSION['user_management_type'] ?? 'success';
unset($_SESSION['user_management_message'], $_SESSION['user_management_type']);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'], $_POST['user_id'])){
    require_valid_csrf_token();
    $id = (int) $_POST['user_id'];
    $action = $_POST['user_action'];
    $message = 'No changes were applied.';
    $messageType = 'warning';

    if ($id <= 0) {
        $_SESSION['user_management_message'] = 'Invalid user selected.';
        $_SESSION['user_management_type'] = 'danger';
        header("Location: user.php");
        exit;
    }

    $targetUserStmt = $conn->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    if ($targetUserStmt) {
        $targetUserStmt->bind_param("i", $id);
        $targetUserStmt->execute();
        $targetUser = $targetUserStmt->get_result()->fetch_assoc();
        $targetUserStmt->close();
    } else {
        $targetUser = null;
    }

    if (!$targetUser) {
        $_SESSION['user_management_message'] = 'User not found.';
        $_SESSION['user_management_type'] = 'danger';
        header("Location: user.php");
        exit;
    }

    if($action === 'block'){
        $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = 'User blocked successfully.';
            $messageType = 'success';
        } else {
            $message = 'Unable to block user.';
            $messageType = 'danger';
        }
    } elseif ($action === 'unblock') {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = 'User unblocked successfully.';
            $messageType = 'success';
        } else {
            $message = 'Unable to unblock user.';
            $messageType = 'danger';
        }
    } elseif ($action === 'reset_attempts') {
        $stmt = $conn->prepare("UPDATE users SET attempts = 0 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Login attempts reset successfully.';
            $messageType = 'success';
        } else {
            $message = 'Unable to reset attempts.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        if ($targetUser['role'] === 'admin') {
            $message = 'Admin accounts cannot be deleted from this page.';
            $messageType = 'warning';
        } elseif ((int)$_SESSION['user_id'] === $id) {
            $message = 'You cannot delete your own account.';
            $messageType = 'warning';
        } else {
            try {
                $conn->begin_transaction();

                $voteStmt = $conn->prepare("SELECT candidate_id FROM votes WHERE user_id = ? LIMIT 1");
                if (!$voteStmt) {
                    throw new RuntimeException("Failed to load vote record.");
                }
                $voteStmt->bind_param("i", $id);
                $voteStmt->execute();
                $voteRow = $voteStmt->get_result()->fetch_assoc();
                $voteStmt->close();

                if ($voteRow && isset($voteRow['candidate_id'])) {
                    $candidateId = (int) $voteRow['candidate_id'];
                    $candidateVoteStmt = $conn->prepare("UPDATE candidates SET votes = GREATEST(votes - 1, 0) WHERE id = ?");
                    if (!$candidateVoteStmt) {
                        throw new RuntimeException("Failed to update candidate votes.");
                    }
                    $candidateVoteStmt->bind_param("i", $candidateId);
                    $candidateVoteStmt->execute();
                    $candidateVoteStmt->close();
                }

                $deleteVoteStmt = $conn->prepare("DELETE FROM votes WHERE user_id = ?");
                if (!$deleteVoteStmt) {
                    throw new RuntimeException("Failed to delete vote records.");
                }
                $deleteVoteStmt->bind_param("i", $id);
                $deleteVoteStmt->execute();
                $deleteVoteStmt->close();

                $deleteRequestStmt = $conn->prepare("DELETE FROM candidate_requests WHERE user_id = ?");
                if ($deleteRequestStmt) {
                    $deleteRequestStmt->bind_param("i", $id);
                    $deleteRequestStmt->execute();
                    $deleteRequestStmt->close();
                }

                $deleteUserStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                if (!$deleteUserStmt) {
                    throw new RuntimeException("Failed to delete user.");
                }
                $deleteUserStmt->bind_param("i", $id);
                $deleteUserStmt->execute();
                $deletedRows = $deleteUserStmt->affected_rows;
                $deleteUserStmt->close();

                if ($deletedRows !== 1) {
                    throw new RuntimeException("User could not be deleted.");
                }

                $conn->commit();
                $message = 'User deleted successfully.';
                $messageType = 'success';
            } catch (Throwable $e) {
                $conn->rollback();
                $message = $e->getMessage();
                $messageType = 'danger';
            }
        }
    }

    $_SESSION['user_management_message'] = $message;
    $_SESSION['user_management_type'] = $messageType;
    header("Location: user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .action-btn {
            border: 0;
            font-weight: 600;
            letter-spacing: .2px;
            padding: .4rem .75rem;
            border-radius: 999px;
            color: #fff;
            box-shadow: 0 8px 16px rgba(15, 23, 42, .12);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 10px 18px rgba(15, 23, 42, .16);
            color: #fff;
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .action-block { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .action-unblock { background: linear-gradient(135deg, #16a34a, #15803d); }
        .action-reset { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .action-delete { background: linear-gradient(135deg, #0f172a, #1e293b); }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Manage Users</h1>
                <p class="text-muted mb-0">Review account status and update access.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if($flashMessage): ?>
                    <div class="alert alert-<?= htmlspecialchars($flashType) ?> rounded-0 border-0 mb-0">
                        <?= htmlspecialchars($flashMessage) ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

<?php
$result = $conn->query("SELECT * FROM users");

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td><span class='badge ".($row['role'] === 'admin' ? "bg-dark" : "bg-primary")."'>".htmlspecialchars($row['role'])."</span></td>";
    echo "<td><span class='badge ".($row['status'] === 'blocked' ? "bg-danger" : "bg-success")."'>".htmlspecialchars($row['status'])."</span></td>";
    echo "<td>".(int) $row['attempts']." / 3</td>";
    echo "<td class='text-end'>";
    echo "<form method='POST' class='d-inline me-2'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='block'>";
    echo "<button type='submit' class='btn btn-sm action-btn action-block'>Block</button>";
    echo "</form>";

    echo "<form method='POST' class='d-inline me-2'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='unblock'>";
    echo "<button type='submit' class='btn btn-sm action-btn action-unblock'>Unblock</button>";
    echo "</form>";

    echo "<form method='POST' class='d-inline'>";
    echo csrf_input();
    echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
    echo "<input type='hidden' name='user_action' value='reset_attempts'>";
    echo "<button type='submit' class='btn btn-sm action-btn action-reset'>Reset Attempts</button>";
    echo "</form>";

    if ($row['role'] !== 'admin') {
        echo "<form method='POST' class='d-inline ms-2' onsubmit=\"return confirm('Delete this user account permanently?');\">";
        echo csrf_input();
        echo "<input type='hidden' name='user_id' value='".(int) $row['id']."'>";
        echo "<input type='hidden' name='user_action' value='delete'>";
        echo "<button type='submit' class='btn btn-sm action-btn action-delete'>Delete</button>";
        echo "</form>";
    }
    echo "</td>";
    echo "</tr>";
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
