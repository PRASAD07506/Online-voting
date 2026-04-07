<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");
require_once("../config/candidate_request_helpers.php");
require_once("../config/csrf.php");

ensure_candidate_requests_table($conn);
$counts = candidate_request_counts($conn);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'], $_POST['request_id'])){
    require_valid_csrf_token();
    $id = (int) $_POST['request_id'];
    $action = $_POST['request_action'];

    if($action === 'approve'){
        $request = $conn->query("SELECT * FROM candidate_requests WHERE id = $id LIMIT 1")->fetch_assoc();
        if($request && $request['status'] !== 'approved'){
            $stmt = $conn->prepare("INSERT INTO candidates(name, party) VALUES(?, ?)");
            $stmt->bind_param("ss", $request['candidate_name'], $request['party_name']);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE candidate_requests SET status = 'approved', reviewed_at = NOW() WHERE id = $id");
        }
    } elseif ($action === 'reject') {
        $conn->query("UPDATE candidate_requests SET status = 'rejected', reviewed_at = NOW() WHERE id = $id");
    }

    header("Location: candidate_requests.php");
    exit;
}

$requests = $conn->query("
    SELECT cr.*, u.email
    FROM candidate_requests cr
    INNER JOIN users u ON u.id = cr.user_id
    ORDER BY FIELD(cr.status, 'pending', 'approved', 'rejected'), cr.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="app-shell">
<nav class="navbar navbar-dark bg-dark nav-shadow">
    <div class="container">
        <span class="navbar-brand">Candidate Requests</span>
        <a href="dashboard.php" class="btn btn-outline-light">Back</a>
    </div>
</nav>

<div class="container page-section">
    <div class="card glass-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h1 class="h3 mb-1">Requests from candidates</h1>
                    <p class="text-muted mb-0">Approve a request to add that person directly to the candidate list.</p>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="soft-stat admin-request-stat">
                        <span class="text-muted">Pending</span>
                        <strong><?= (int) ($counts['pending_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="soft-stat admin-request-stat">
                        <span class="text-muted">Approved</span>
                        <strong><?= (int) ($counts['approved_count'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="soft-stat admin-request-stat">
                        <span class="text-muted">Rejected</span>
                        <strong><?= (int) ($counts['rejected_count'] ?? 0) ?></strong>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle admin-request-table">
                    <thead class="table-dark">
                        <tr>
                            <th>Candidate</th>
                            <th>Email</th>
                            <th>Party</th>
                            <th>Manifesto</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($requests && $requests->num_rows > 0): ?>
                            <?php while($row = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['candidate_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['party_name']) ?></td>
                                    <td><?= htmlspecialchars($row['manifesto'] ?: 'No manifesto provided') ?></td>
                                    <td>
                                        <span class="badge <?= $row['status'] === 'pending' ? 'bg-warning text-dark' : ($row['status'] === 'approved' ? 'bg-success' : 'bg-danger') ?>">
                                            <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if($row['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline me-2">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                                                <input type="hidden" name="request_action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-outline-success landing-action">Approve</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                                                <input type="hidden" name="request_action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-outline-danger landing-action">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Reviewed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No candidate requests yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
