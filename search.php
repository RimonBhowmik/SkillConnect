<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$query = trim($_GET['q'] ?? '');
$includeUsers = isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
$results = searchSite($conn, $query, $includeUsers);

$pageTitle = 'SkillConnect | Search';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Search</span>
        <h1 class="display-6 fw-bold">Search the whole platform</h1>
        <p class="muted-text">One search now looks through jobs, bootcamps, portfolio projects, skills, and for admins, user accounts too.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="content-card mb-4" data-reveal>
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Search term</label>
                    <input type="text" name="q" class="form-control" value="<?= e($query); ?>" placeholder="Try PHP, portfolio, trainer, bootcamp, HTML">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-accent px-4 w-100">Search everything</button>
                </div>
            </form>
        </div>

        <?php if ($query !== ''): ?>
            <div class="row g-4">
                <div class="col-lg-6" data-reveal>
                    <div class="content-card h-100">
                        <h2 class="h5 fw-bold mb-3">Jobs</h2>
                        <?php if ($results['jobs'] !== []): ?>
                            <ul class="list-clean">
                                <?php foreach ($results['jobs'] as $job): ?>
                                    <li class="border rounded-4 p-3 bg-white">
                                        <div class="fw-bold"><?= e($job['title']); ?></div>
                                        <div class="small">Location: <?= e($job['location'] ?: 'Not specified'); ?></div>
                                        <div class="small muted-text"><?= e($job['required_skills']); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">No job matches found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6" data-reveal>
                    <div class="content-card h-100">
                        <h2 class="h5 fw-bold mb-3">Bootcamps</h2>
                        <?php if ($results['bootcamps'] !== []): ?>
                            <ul class="list-clean">
                                <?php foreach ($results['bootcamps'] as $bootcamp): ?>
                                    <li class="border rounded-4 p-3 bg-white">
                                        <div class="fw-bold"><?= e($bootcamp['title']); ?></div>
                                        <div class="small">Trainer: <?= e($bootcamp['trainer_name']); ?></div>
                                        <div class="small muted-text"><?= e($bootcamp['start_date']); ?> to <?= e($bootcamp['end_date']); ?> | Fee: <?= e(number_format((float) $bootcamp['fee'], 2)); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">No bootcamp matches found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6" data-reveal>
                    <div class="content-card h-100">
                        <h2 class="h5 fw-bold mb-3">Portfolio projects</h2>
                        <?php if ($results['projects'] !== []): ?>
                            <ul class="list-clean">
                                <?php foreach ($results['projects'] as $project): ?>
                                    <li class="border rounded-4 p-3 bg-white">
                                        <div class="fw-bold"><?= e($project['title']); ?></div>
                                        <div class="small">Owner: <?= e($project['name']); ?></div>
                                        <div class="small muted-text"><?= e($project['project_link'] ?: 'No live link'); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">No project matches found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6" data-reveal>
                    <div class="content-card h-100">
                        <h2 class="h5 fw-bold mb-3">Skills</h2>
                        <?php if ($results['skills'] !== []): ?>
                            <ul class="list-clean">
                                <?php foreach ($results['skills'] as $skill): ?>
                                    <li class="border rounded-4 p-3 bg-white">
                                        <div class="fw-bold"><?= e($skill['name']); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">No skill matches found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($includeUsers): ?>
                    <div class="col-12" data-reveal>
                        <div class="content-card h-100">
                            <h2 class="h5 fw-bold mb-3">Users</h2>
                            <?php if ($results['users'] !== []): ?>
                                <ul class="list-clean">
                                    <?php foreach ($results['users'] as $user): ?>
                                        <li class="border rounded-4 p-3 bg-white">
                                            <div class="fw-bold"><?= e($user['name']); ?></div>
                                            <div class="small"><?= e($user['email']); ?></div>
                                            <div class="small muted-text">Role: <?= e(ucfirst($user['role'])); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">No user matches found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
