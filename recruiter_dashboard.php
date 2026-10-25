<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireRole(['recruiter']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_job') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requiredSkills = trim($_POST['required_skills'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($title === '' || $description === '' || $requiredSkills === '') {
        setFlash('danger', 'Please fill in the required job fields.');
    } else {
        $recruiterId = (int) $_SESSION['user_id'];
        $stmt = $conn->prepare('INSERT INTO jobs (recruiter_id, title, description, required_skills, location) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $recruiterId, $title, $description, $requiredSkills, $location);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Job posted successfully.');
    }

    header('Location: recruiter_dashboard.php');
    exit;
}

$recruiterId = (int) $_SESSION['user_id'];

$jobs = $conn->prepare("
    SELECT jobs.*, COUNT(applications.id) AS application_count
    FROM jobs
    LEFT JOIN applications ON applications.job_id = jobs.id
    WHERE recruiter_id = ?
    GROUP BY jobs.id
    ORDER BY jobs.created_at DESC
");
$jobs->bind_param('i', $recruiterId);
$jobs->execute();
$jobResult = $jobs->get_result();

$applications = $conn->prepare("
    SELECT jobs.title AS job_title, users.name AS student_name, applications.status, applications.cover_note
    FROM applications
    INNER JOIN jobs ON jobs.id = applications.job_id
    INNER JOIN users ON users.id = applications.student_id
    WHERE jobs.recruiter_id = ?
    ORDER BY applications.created_at DESC
");
$applications->bind_param('i', $recruiterId);
$applications->execute();
$applicationResult = $applications->get_result();

$pageTitle = 'SkillConnect | Recruiter Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Recruiter dashboard</span>
        <h1 class="display-6 fw-bold">Post roles and review student applications</h1>
        <p class="muted-text">Recruit talent based on portfolios, verified skills, and relevant experience.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5" data-reveal>
                <div class="content-card">
                    <h2 class="h5 fw-bold mb-3">Create a job or gig</h2>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create_job">
                        <div class="col-12">
                            <label class="form-label">Job title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Required skills</label>
                            <input type="text" name="required_skills" class="form-control" placeholder="PHP, MySQL, Bootstrap" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Remote / Dhaka">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent px-4">Post job</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7" data-reveal>
                <div class="content-card table-card h-100">
                    <h2 class="h5 fw-bold mb-3">Your job postings</h2>
                    <?php if ($jobResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Skills</th>
                                        <th>Applications</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($job = $jobResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($job['title']); ?></td>
                                            <td><?= e($job['required_skills']); ?></td>
                                            <td><span class="status-pill status-open"><?= e((string) $job['application_count']); ?> received</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No jobs posted yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12" data-reveal>
                <div class="content-card table-card">
                    <h2 class="h5 fw-bold mb-3">Recent applications</h2>
                    <?php if ($applicationResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Cover note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($application = $applicationResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= e($application['job_title']); ?></td>
                                            <td><?= e($application['student_name']); ?></td>
                                            <td><span class="status-pill status-applied"><?= e(ucfirst($application['status'])); ?></span></td>
                                            <td><?= e($application['cover_note']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">Applications will appear here when students apply.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$jobs->close();
$applications->close();
require_once __DIR__ . '/includes/footer.php';
?>
