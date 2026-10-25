<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireRole(['admin']);

if (isset($_GET['delete_user'])) {
    $userIdToDelete = (int) $_GET['delete_user'];
    $currentAdminId = (int) $_SESSION['user_id'];

    if ($userIdToDelete === $currentAdminId) {
        setFlash('warning', 'You cannot delete your own admin account.');
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role <> 'admin'");
        $stmt->bind_param('i', $userIdToDelete);
        $stmt->execute();
        $deletedRows = $stmt->affected_rows;
        $stmt->close();
        setFlash($deletedRows > 0 ? 'success' : 'warning', $deletedRows > 0 ? 'User deleted successfully.' : 'Only non-admin accounts can be deleted.');
    }

    header('Location: admin_panel.php');
    exit;
}

if (isset($_GET['verify_skill'])) {
    $userSkillId = (int) $_GET['verify_skill'];
    $verifierId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare('UPDATE user_skills SET verified = 1, verified_by = ? WHERE id = ?');
    $stmt->bind_param('ii', $verifierId, $userSkillId);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Skill verified successfully.');
    header('Location: admin_panel.php');
    exit;
}

if (isset($_GET['transfer_payment'])) {
    $paymentId = (int) $_GET['transfer_payment'];
    $stmt = $conn->prepare("UPDATE payments SET status = 'transferred', transferred_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Payment marked as transferred to the trainer.');
    header('Location: admin_panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_skill') {
    $skillName = trim($_POST['skill_name'] ?? '');

    if ($skillName === '') {
        setFlash('danger', 'Skill name is required.');
    } else {
        $stmt = $conn->prepare('INSERT IGNORE INTO skills (name) VALUES (?)');
        $stmt->bind_param('s', $skillName);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Skill entry saved.');
    }

    header('Location: admin_panel.php');
    exit;
}

$metrics = [
    'users' => $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'] ?? 0,
    'bootcamps' => $conn->query('SELECT COUNT(*) AS total FROM bootcamps')->fetch_assoc()['total'] ?? 0,
    'projects' => $conn->query('SELECT COUNT(*) AS total FROM projects')->fetch_assoc()['total'] ?? 0,
    'jobs' => $conn->query('SELECT COUNT(*) AS total FROM jobs')->fetch_assoc()['total'] ?? 0,
];

$paymentMetrics = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN status = 'submitted' THEN amount ELSE 0 END), 0) AS submitted_total,
        COALESCE(SUM(CASE WHEN status = 'transferred' THEN amount ELSE 0 END), 0) AS transferred_total
    FROM payments
")->fetch_assoc() ?: ['submitted_total' => 0, 'transferred_total' => 0];

$pendingSkills = $conn->query("
    SELECT user_skills.id, users.name AS user_name, skills.name AS skill_name
    FROM user_skills
    INNER JOIN users ON users.id = user_skills.user_id
    INNER JOIN skills ON skills.id = user_skills.skill_id
    WHERE user_skills.verified = 0
    ORDER BY user_skills.created_at DESC
");

$recentUsers = $conn->query('SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 8');
$allUsers = $conn->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
$allSkills = $conn->query('SELECT name FROM skills ORDER BY name ASC LIMIT 10');
$paymentQueue = $conn->query("
    SELECT payments.id, students.name AS student_name, trainers.name AS trainer_name, bootcamps.title AS bootcamp_title,
           payments.amount, payments.status, payments.transaction_ref, payments.paid_at
    FROM payments
    INNER JOIN users AS students ON students.id = payments.student_id
    INNER JOIN users AS trainers ON trainers.id = payments.trainer_id
    INNER JOIN bootcamps ON bootcamps.id = payments.bootcamp_id
    ORDER BY payments.paid_at DESC
");

$pageTitle = 'SkillConnect | Admin Panel';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Admin dashboard</span>
        <h1 class="display-6 fw-bold">Platform overview and verification control</h1>
        <p class="muted-text">Manage users, review skill submissions, and keep the ecosystem healthy.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4 mb-4">
            <?php foreach ($metrics as $label => $value): ?>
                <div class="col-md-6 col-xl-3" data-reveal>
                    <div class="metric-card">
                        <div class="metric-value"><?= e((string) $value); ?></div>
                        <div class="metric-label"><?= e(ucfirst($label)); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6" data-reveal>
                <div class="metric-card">
                    <div class="metric-value"><?= e(number_format((float) $paymentMetrics['submitted_total'], 2)); ?></div>
                    <div class="metric-label">Student funds waiting for transfer</div>
                </div>
            </div>
            <div class="col-md-6" data-reveal>
                <div class="metric-card">
                    <div class="metric-value"><?= e(number_format((float) $paymentMetrics['transferred_total'], 2)); ?></div>
                    <div class="metric-label">Funds transferred to trainers</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7" data-reveal>
                <div class="content-card table-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Pending skill verifications</h2>
                    </div>
                    <?php if ($pendingSkills && $pendingSkills->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Skill</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($skill = $pendingSkills->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($skill['user_name']); ?></td>
                                            <td><?= e($skill['skill_name']); ?></td>
                                            <td><a href="?verify_skill=<?= e((string) $skill['id']); ?>" class="btn btn-sm btn-accent">Verify</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No pending verifications right now.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Recent users</h2>
                    <ul class="list-clean">
                        <?php while ($user = $recentUsers->fetch_assoc()): ?>
                            <li class="border rounded-4 p-3 bg-white">
                                <div class="fw-bold"><?= e($user['name']); ?></div>
                                <div class="muted-text small"><?= e($user['email']); ?></div>
                                <div class="small mt-2">Role: <strong><?= e(ucfirst($user['role'])); ?></strong></div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-4" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Database entry</h2>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create_skill">
                        <div class="col-12">
                            <label class="form-label">New skill name</label>
                            <input type="text" name="skill_name" class="form-control" placeholder="Example: Python" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent px-4">Add skill</button>
                        </div>
                    </form>
                    <hr>
                    <h3 class="h6 fw-bold">Recent skill entries</h3>
                    <ul class="list-clean">
                        <?php while ($skill = $allSkills->fetch_assoc()): ?>
                            <li class="small"><?= e($skill['name']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-8" data-reveal>
                <div class="content-card table-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">User management</h2>
                        <a href="search.php?q=" class="btn btn-outline-soft btn-sm">Open search</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($managedUser = $allUsers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($managedUser['name']); ?></td>
                                        <td><?= e($managedUser['email']); ?></td>
                                        <td><?= e(ucfirst($managedUser['role'])); ?></td>
                                        <td>
                                            <?php if ($managedUser['role'] !== 'admin'): ?>
                                                <a href="?delete_user=<?= e((string) $managedUser['id']); ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this user?">Delete</a>
                                            <?php else: ?>
                                                <span class="muted-text small">Protected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12" data-reveal>
                <div class="content-card table-card">
                    <h2 class="h5 fw-bold mb-3">Tuition transfer management</h2>
                    <?php if ($paymentQueue && $paymentQueue->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Trainer</th>
                                        <th>Bootcamp</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $paymentQueue->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($payment['student_name']); ?></td>
                                            <td><?= e($payment['trainer_name']); ?></td>
                                            <td>
                                                <?= e($payment['bootcamp_title']); ?>
                                                <div class="small muted-text"><?= e($payment['transaction_ref']); ?></div>
                                            </td>
                                            <td><?= e(number_format((float) $payment['amount'], 2)); ?></td>
                                            <td><span class="status-pill status-<?= e($payment['status']); ?>"><?= e(ucfirst($payment['status'])); ?></span></td>
                                            <td>
                                                <?php if ($payment['status'] === 'submitted'): ?>
                                                    <a href="?transfer_payment=<?= e((string) $payment['id']); ?>" class="btn btn-sm btn-accent">Transfer to trainer</a>
                                                <?php else: ?>
                                                    <span class="muted-text small">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No tuition payments are waiting for review.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
