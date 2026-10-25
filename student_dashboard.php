<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireRole(['student']);

$userId = (int) $_SESSION['user_id'];

$metrics = [
    'enrollments' => 0,
    'projects' => 0,
    'skills' => 0,
    'verified' => 0,
];

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM enrollments WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$metrics['enrollments'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM projects WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$metrics['projects'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total, SUM(verified = 1) AS verified_total FROM user_skills WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$skillCounts = $stmt->get_result()->fetch_assoc() ?: ['total' => 0, 'verified_total' => 0];
$stmt->close();
$metrics['skills'] = $skillCounts['total'] ?? 0;
$metrics['verified'] = $skillCounts['verified_total'] ?? 0;

$recommendedBootcamps = buildRecommendations($conn, $userId, 'bootcamp', 3);
$recommendedJobs = buildRecommendations($conn, $userId, 'job', 3);

$pageTitle = 'SkillConnect | Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Student dashboard</span>
        <h1 class="display-6 fw-bold">Track learning, showcase projects, and discover opportunities</h1>
        <p class="muted-text">Your profile is the center of your bootcamps, skills, portfolio, and job applications.</p>
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

        <div class="row g-4">
            <div class="col-lg-6" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Recommended bootcamps</h2>
                    <?php if ($recommendedBootcamps !== []): ?>
                        <ul class="list-clean">
                            <?php foreach ($recommendedBootcamps as $item): ?>
                                <li class="border rounded-4 p-3 bg-white">
                                    <div class="fw-bold"><?= e($item['title']); ?></div>
                                    <div class="small">Trainer: <?= e($item['trainer_name']); ?></div>
                                    <div class="small muted-text mt-2"><?= e($item['reason']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">No bootcamp recommendations yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Recommended jobs</h2>
                    <?php if ($recommendedJobs !== []): ?>
                        <ul class="list-clean">
                            <?php foreach ($recommendedJobs as $item): ?>
                                <li class="border rounded-4 p-3 bg-white">
                                    <div class="fw-bold"><?= e($item['title']); ?></div>
                                    <div class="small">Location: <?= e($item['location']); ?></div>
                                    <div class="small muted-text mt-2"><?= e($item['reason']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">No job recommendations yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
