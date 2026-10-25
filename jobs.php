<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'student' && ($_POST['action'] ?? '') === 'apply_job') {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $coverNote = trim($_POST['cover_note'] ?? '');

    $check = $conn->prepare('SELECT id FROM applications WHERE job_id = ? AND student_id = ?');
    $check->bind_param('ii', $jobId, $userId);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        setFlash('warning', 'You already applied for this job.');
    } else {
        $stmt = $conn->prepare('INSERT INTO applications (job_id, student_id, cover_note) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $jobId, $userId, $coverNote);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Application submitted successfully.');
    }

    header('Location: jobs.php');
    exit;
}

$jobs = $conn->query("
    SELECT jobs.*, users.name AS recruiter_name
    FROM jobs
    INNER JOIN users ON users.id = jobs.recruiter_id
    ORDER BY jobs.created_at DESC
");

$myApplications = null;
if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT jobs.title, applications.status, applications.created_at
        FROM applications
        INNER JOIN jobs ON jobs.id = applications.job_id
        WHERE applications.student_id = ?
        ORDER BY applications.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $myApplications = $stmt->get_result();
}

$pageTitle = 'SkillConnect | Jobs';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Jobs and gigs</span>
        <h1 class="display-6 fw-bold">Browse opportunities and submit applications</h1>
        <p class="muted-text">Recruiters publish jobs, and students can apply directly from the platform.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4">
            <?php while ($job = $jobs->fetch_assoc()): ?>
                <div class="col-lg-6" data-reveal>
                    <div class="content-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h2 class="h4 fw-bold mb-1"><?= e($job['title']); ?></h2>
                                <div class="muted-text small">Posted by <?= e($job['recruiter_name']); ?><?= $job['location'] ? ' | ' . e($job['location']) : ''; ?></div>
                            </div>
                            <span class="status-pill status-open">Open</span>
                        </div>
                        <p><?= e($job['description']); ?></p>
                        <p class="mb-3"><strong>Required skills:</strong> <?= e($job['required_skills']); ?></p>
                        <?php if ($role === 'student'): ?>
                            <form method="post" class="row g-3">
                                <input type="hidden" name="action" value="apply_job">
                                <input type="hidden" name="job_id" value="<?= e((string) $job['id']); ?>">
                                <div class="col-12">
                                    <label class="form-label">Cover note</label>
                                    <textarea name="cover_note" class="form-control" rows="3" placeholder="Briefly explain why you are a fit."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-accent px-4">Apply now</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($role === 'student' && $myApplications): ?>
            <div class="content-card table-card mt-4" data-reveal>
                <h2 class="h5 fw-bold mb-3">My applications</h2>
                <?php if ($myApplications->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Job</th>
                                    <th>Status</th>
                                    <th>Applied on</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($application = $myApplications->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= e($application['title']); ?></td>
                                        <td><span class="status-pill status-applied"><?= e(ucfirst($application['status'])); ?></span></td>
                                        <td><?= e($application['created_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">You have not applied for any jobs yet.</div>
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
