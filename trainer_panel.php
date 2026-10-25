<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireRole(['trainer']);

$trainerId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_bootcamp') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $fee = (float) ($_POST['fee'] ?? 0);

    if ($title === '' || $description === '' || $startDate === '' || $endDate === '') {
        setFlash('danger', 'Please fill in all bootcamp fields.');
    } else {
        $stmt = $conn->prepare('INSERT INTO bootcamps (title, description, fee, trainer_id, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssdiss', $title, $description, $fee, $trainerId, $startDate, $endDate);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Bootcamp created successfully.');
    }

    header('Location: trainer_panel.php');
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
    header('Location: trainer_panel.php');
    exit;
}

$bootcamps = $conn->prepare("
    SELECT bootcamps.*, COUNT(enrollments.id) AS enrolled_count
    FROM bootcamps
    LEFT JOIN enrollments ON enrollments.bootcamp_id = bootcamps.id
    WHERE trainer_id = ?
    GROUP BY bootcamps.id
    ORDER BY bootcamps.start_date DESC
");
$bootcamps->bind_param('i', $trainerId);
$bootcamps->execute();
$bootcampResult = $bootcamps->get_result();

$pendingSkills = $conn->query("
    SELECT user_skills.id, users.name AS user_name, skills.name AS skill_name
    FROM user_skills
    INNER JOIN users ON users.id = user_skills.user_id
    INNER JOIN skills ON skills.id = user_skills.skill_id
    WHERE user_skills.verified = 0
    ORDER BY user_skills.created_at DESC
    LIMIT 10
");

$earnings = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN payments.status = 'submitted' THEN payments.amount ELSE 0 END), 0) AS pending_amount,
        COALESCE(SUM(CASE WHEN payments.status = 'transferred' THEN payments.amount ELSE 0 END), 0) AS transferred_amount
    FROM payments
    WHERE trainer_id = ?
");
$earnings->bind_param('i', $trainerId);
$earnings->execute();
$earningRow = $earnings->get_result()->fetch_assoc() ?: ['pending_amount' => 0, 'transferred_amount' => 0];
$earnings->close();

$paymentList = $conn->prepare("
    SELECT bootcamps.title, users.name AS student_name, payments.amount, payments.status, payments.transaction_ref, payments.paid_at
    FROM payments
    INNER JOIN bootcamps ON bootcamps.id = payments.bootcamp_id
    INNER JOIN users ON users.id = payments.student_id
    WHERE payments.trainer_id = ?
    ORDER BY payments.paid_at DESC
");
$paymentList->bind_param('i', $trainerId);
$paymentList->execute();
$paymentResult = $paymentList->get_result();

$pageTitle = 'SkillConnect | Trainer Panel';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Trainer dashboard</span>
        <h1 class="display-6 fw-bold">Run bootcamps, verify skills, and track tuition transfers</h1>
        <p class="muted-text">Students submit tuition on the platform, then the admin transfers the collected fund to you.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-6" data-reveal>
                <div class="metric-card">
                    <div class="metric-value"><?= e(number_format((float) $earningRow['pending_amount'], 2)); ?></div>
                    <div class="metric-label">Pending admin transfer</div>
                </div>
            </div>
            <div class="col-md-6" data-reveal>
                <div class="metric-card">
                    <div class="metric-value"><?= e(number_format((float) $earningRow['transferred_amount'], 2)); ?></div>
                    <div class="metric-label">Transferred to you</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5" data-reveal>
                <div class="content-card">
                    <h2 class="h5 fw-bold mb-3">Create a bootcamp</h2>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create_bootcamp">
                        <div class="col-12">
                            <label class="form-label">Bootcamp title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tuition fee</label>
                            <input type="number" name="fee" class="form-control" step="0.01" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent px-4">Publish bootcamp</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7" data-reveal>
                <div class="content-card table-card h-100">
                    <h2 class="h5 fw-bold mb-3">Your bootcamps</h2>
                    <?php if ($bootcampResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Fee</th>
                                        <th>Schedule</th>
                                        <th>Enrollments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($bootcamp = $bootcampResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($bootcamp['title']); ?></td>
                                            <td><?= e(number_format((float) $bootcamp['fee'], 2)); ?></td>
                                            <td><?= e($bootcamp['start_date']); ?> to <?= e($bootcamp['end_date']); ?></td>
                                            <td><span class="status-pill status-open"><?= e((string) $bootcamp['enrolled_count']); ?> enrolled</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">You have not created any bootcamps yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6" data-reveal>
                <div class="content-card table-card h-100">
                    <h2 class="h5 fw-bold mb-3">Tuition payment activity</h2>
                    <?php if ($paymentResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Bootcamp</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $paymentResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($payment['student_name']); ?></td>
                                            <td><?= e($payment['title']); ?></td>
                                            <td><?= e(number_format((float) $payment['amount'], 2)); ?></td>
                                            <td>
                                                <span class="status-pill status-<?= e($payment['status']); ?>"><?= e(ucfirst($payment['status'])); ?></span>
                                                <div class="small muted-text mt-1"><?= e($payment['transaction_ref']); ?></div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No tuition payments have been submitted yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6" data-reveal>
                <div class="content-card table-card">
                    <h2 class="h5 fw-bold mb-3">Pending skill reviews</h2>
                    <?php if ($pendingSkills && $pendingSkills->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Skill</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($skill = $pendingSkills->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($skill['user_name']); ?></td>
                                            <td><?= e($skill['skill_name']); ?></td>
                                            <td><a href="?verify_skill=<?= e((string) $skill['id']); ?>" class="btn btn-sm btn-accent">Verify now</a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No skills are waiting for verification.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$bootcamps->close();
$paymentList->close();
require_once __DIR__ . '/includes/footer.php';
?>
