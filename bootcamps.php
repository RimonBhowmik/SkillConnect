<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'student' && isset($_GET['enroll'])) {
    $bootcampId = (int) $_GET['enroll'];
    $status = 'pending';

    $check = $conn->prepare('SELECT id FROM enrollments WHERE user_id = ? AND bootcamp_id = ?');
    $check->bind_param('ii', $userId, $bootcampId);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        setFlash('warning', 'You are already enrolled or pending approval for this bootcamp.');
    } else {
        $stmt = $conn->prepare('INSERT INTO enrollments (user_id, bootcamp_id, status) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $userId, $bootcampId, $status);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Bootcamp enrollment submitted.');
    }

    header('Location: bootcamps.php');
    exit;
}

if ($role === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_payment') {
    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $transactionRef = trim($_POST['transaction_ref'] ?? '');

    $stmt = $conn->prepare("
        SELECT enrollments.id, enrollments.user_id, bootcamps.id AS bootcamp_id, bootcamps.fee, bootcamps.trainer_id
        FROM enrollments
        INNER JOIN bootcamps ON bootcamps.id = enrollments.bootcamp_id
        WHERE enrollments.id = ? AND enrollments.user_id = ? AND enrollments.status = 'approved'
    ");
    $stmt->bind_param('ii', $enrollmentId, $userId);
    $stmt->execute();
    $enrollmentData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$enrollmentData) {
        setFlash('danger', 'Approved enrollment not found for payment.');
    } elseif ((float) $enrollmentData['fee'] <= 0) {
        setFlash('warning', 'This bootcamp does not have a tuition fee.');
    } else {
        $amount = (float) $enrollmentData['fee'];
        $trainerId = (int) $enrollmentData['trainer_id'];
        $bootcampId = (int) $enrollmentData['bootcamp_id'];

        $stmt = $conn->prepare("
            INSERT INTO payments (enrollment_id, student_id, bootcamp_id, trainer_id, amount, payment_method, transaction_ref, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')
            ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                payment_method = VALUES(payment_method),
                transaction_ref = VALUES(transaction_ref),
                status = 'submitted',
                transferred_at = NULL
        ");
        $stmt->bind_param('iiiidss', $enrollmentId, $userId, $bootcampId, $trainerId, $amount, $paymentMethod, $transactionRef);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Tuition payment submitted. The admin will review and transfer the fund to the trainer.');
    }

    header('Location: bootcamps.php');
    exit;
}

if (($role === 'trainer' || $role === 'admin') && isset($_GET['approve_enrollment'])) {
    $enrollmentId = (int) $_GET['approve_enrollment'];
    $status = 'approved';
    $stmt = $conn->prepare('UPDATE enrollments SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $enrollmentId);
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'Enrollment approved.');
    header('Location: bootcamps.php');
    exit;
}

$bootcampQuery = "
    SELECT bootcamps.id, bootcamps.title, bootcamps.description, bootcamps.start_date, bootcamps.end_date, bootcamps.fee,
           users.name AS trainer_name,
           COUNT(enrollments.id) AS enrolled_count
    FROM bootcamps
    INNER JOIN users ON users.id = bootcamps.trainer_id
    LEFT JOIN enrollments ON enrollments.bootcamp_id = bootcamps.id
    GROUP BY bootcamps.id
    ORDER BY bootcamps.start_date ASC
";
$bootcamps = $conn->query($bootcampQuery);

$enrollmentList = null;
if ($role === 'trainer' || $role === 'admin') {
    $enrollmentSql = "
        SELECT enrollments.id, users.name AS student_name, bootcamps.title AS bootcamp_title, bootcamps.fee,
               enrollments.status, COALESCE(payments.status, 'unpaid') AS payment_status, payments.amount
        FROM enrollments
        INNER JOIN users ON users.id = enrollments.user_id
        INNER JOIN bootcamps ON bootcamps.id = enrollments.bootcamp_id
        LEFT JOIN payments ON payments.enrollment_id = enrollments.id
        ORDER BY enrollments.created_at DESC
    ";
    $enrollmentList = $conn->query($enrollmentSql);
}

$myEnrollments = null;
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT enrollments.id, bootcamps.title, enrollments.status, bootcamps.start_date, bootcamps.end_date, bootcamps.fee,
               COALESCE(payments.status, 'unpaid') AS payment_status, payments.transaction_ref
        FROM enrollments
        INNER JOIN bootcamps ON bootcamps.id = enrollments.bootcamp_id
        LEFT JOIN payments ON payments.enrollment_id = enrollments.id
        WHERE enrollments.user_id = ?
        ORDER BY enrollments.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $myEnrollments = $stmt->get_result();
}

$pageTitle = 'SkillConnect | Bootcamps';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Bootcamps</span>
        <h1 class="display-6 fw-bold">Discover, publish, and manage bootcamp experiences</h1>
        <p class="muted-text">Students enroll and pay tuition here, admins control fund transfer, and trainers deliver the teaching.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="content-card table-card mb-4" data-reveal>
            <h2 class="h5 fw-bold mb-3">Available bootcamps</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Trainer</th>
                            <th>Dates</th>
                            <th>Tuition fee</th>
                            <th>Enrolled</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bootcamp = $bootcamps->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($bootcamp['title']); ?></div>
                                    <div class="small muted-text"><?= e($bootcamp['description']); ?></div>
                                </td>
                                <td><?= e($bootcamp['trainer_name']); ?></td>
                                <td><?= e($bootcamp['start_date']); ?> to <?= e($bootcamp['end_date']); ?></td>
                                <td><?= e(number_format((float) $bootcamp['fee'], 2)); ?></td>
                                <td><?= e((string) $bootcamp['enrolled_count']); ?></td>
                                <td>
                                    <?php if ($role === 'student'): ?>
                                        <a href="?enroll=<?= e((string) $bootcamp['id']); ?>" class="btn btn-sm btn-accent">Enroll</a>
                                    <?php else: ?>
                                        <span class="muted-text small">View only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($role === 'student' && $myEnrollments): ?>
            <div class="content-card table-card" data-reveal>
                <h2 class="h5 fw-bold mb-3">My enrollments and payments</h2>
                <?php if ($myEnrollments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Bootcamp</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Tuition</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($enrollment = $myEnrollments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($enrollment['title']); ?></td>
                                        <td><?= e($enrollment['start_date']); ?> to <?= e($enrollment['end_date']); ?></td>
                                        <td><span class="status-pill status-<?= e(strtolower($enrollment['status'])); ?>"><?= e(ucfirst($enrollment['status'])); ?></span></td>
                                        <td><?= e(number_format((float) $enrollment['fee'], 2)); ?></td>
                                        <td>
                                            <?php if ((float) $enrollment['fee'] <= 0): ?>
                                                <span class="muted-text small">No tuition fee</span>
                                            <?php elseif ($enrollment['payment_status'] === 'unpaid' && $enrollment['status'] === 'approved'): ?>
                                                <form method="post" class="row g-2">
                                                    <input type="hidden" name="action" value="submit_payment">
                                                    <input type="hidden" name="enrollment_id" value="<?= e((string) $enrollment['id']); ?>">
                                                    <div class="col-12">
                                                        <input type="text" name="payment_method" class="form-control form-control-sm" placeholder="Payment method" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <input type="text" name="transaction_ref" class="form-control form-control-sm" placeholder="Transaction reference" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <button type="submit" class="btn btn-sm btn-accent">Submit payment</button>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <span class="status-pill status-<?= e($enrollment['payment_status']); ?>"><?= e(ucfirst($enrollment['payment_status'])); ?></span>
                                                <?php if (!empty($enrollment['transaction_ref'])): ?>
                                                    <div class="small muted-text mt-2">Ref: <?= e($enrollment['transaction_ref']); ?></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">You have not enrolled in any bootcamp yet.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (($role === 'trainer' || $role === 'admin') && $enrollmentList): ?>
            <div class="content-card table-card mt-4" data-reveal>
                <h2 class="h5 fw-bold mb-3">Enrollment and payment management</h2>
                <?php if ($enrollmentList->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Bootcamp</th>
                                    <th>Status</th>
                                    <th>Fee</th>
                                    <th>Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($enrollment = $enrollmentList->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($enrollment['student_name']); ?></td>
                                        <td><?= e($enrollment['bootcamp_title']); ?></td>
                                        <td><span class="status-pill status-<?= e(strtolower($enrollment['status'])); ?>"><?= e(ucfirst($enrollment['status'])); ?></span></td>
                                        <td><?= e(number_format((float) $enrollment['fee'], 2)); ?></td>
                                        <td><span class="status-pill status-<?= e($enrollment['payment_status']); ?>"><?= e(ucfirst($enrollment['payment_status'])); ?></span></td>
                                        <td>
                                            <?php if ($enrollment['status'] !== 'approved'): ?>
                                                <a href="?approve_enrollment=<?= e((string) $enrollment['id']); ?>" class="btn btn-sm btn-accent">Approve</a>
                                            <?php else: ?>
                                                <span class="muted-text small">Approved</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No enrollment activity to review.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
require_once __DIR__ . '/includes/footer.php';
?>
