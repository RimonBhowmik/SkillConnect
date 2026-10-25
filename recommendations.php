<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireRole(['student']);

$userId = (int) $_SESSION['user_id'];
$jobRecommendations = buildRecommendations($conn, $userId, 'job', 6);
$bootcampRecommendations = buildRecommendations($conn, $userId, 'bootcamp', 6);

$pageTitle = 'SkillConnect | Recommendations';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Recommendations</span>
        <h1 class="display-6 fw-bold">Personalized suggestions</h1>
        <p class="muted-text">These suggestions are generated from your current skills, enrollments, and application history.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Recommended jobs</h2>
                    <?php if ($jobRecommendations !== []): ?>
                        <ul class="list-clean">
                            <?php foreach ($jobRecommendations as $job): ?>
                                <li class="border rounded-4 p-3 bg-white">
                                    <div class="fw-bold"><?= e($job['title']); ?></div>
                                    <div class="small">Location: <?= e($job['location'] ?: 'Not specified'); ?></div>
                                    <div class="small">Skills: <?= e($job['required_skills']); ?></div>
                                    <div class="small muted-text mt-2"><?= e($job['reason']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">No job recommendations available yet. Add more skills to improve matching.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6" data-reveal>
                <div class="content-card h-100">
                    <h2 class="h5 fw-bold mb-3">Recommended bootcamps</h2>
                    <?php if ($bootcampRecommendations !== []): ?>
                        <ul class="list-clean">
                            <?php foreach ($bootcampRecommendations as $bootcamp): ?>
                                <li class="border rounded-4 p-3 bg-white">
                                    <div class="fw-bold"><?= e($bootcamp['title']); ?></div>
                                    <div class="small">Trainer: <?= e($bootcamp['trainer_name']); ?></div>
                                    <div class="small"><?= e($bootcamp['start_date']); ?> to <?= e($bootcamp['end_date']); ?></div>
                                    <div class="small muted-text mt-2"><?= e($bootcamp['reason']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">No bootcamp recommendations available yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
