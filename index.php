<?php
declare(strict_types=1);

$pageTitle = 'SkillConnect | Home';
require_once __DIR__ . '/includes/header.php';

$stats = [
    'users' => $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'] ?? 0,
    'bootcamps' => $conn->query('SELECT COUNT(*) AS total FROM bootcamps')->fetch_assoc()['total'] ?? 0,
    'jobs' => $conn->query('SELECT COUNT(*) AS total FROM jobs')->fetch_assoc()['total'] ?? 0,
];

$featuredBootcamps = $conn->query("
    SELECT bootcamps.title, bootcamps.fee, users.name AS trainer_name
    FROM bootcamps
    INNER JOIN users ON users.id = bootcamps.trainer_id
    ORDER BY bootcamps.start_date ASC
    LIMIT 3
");

$featuredJobs = $conn->query("
    SELECT title, location, required_skills
    FROM jobs
    ORDER BY created_at DESC
    LIMIT 3
");
?>

<?php if ($loggedInUser): ?>
    <section class="hero-section home-shell">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-xl-7" data-reveal>
                    <div class="command-deck h-100">
                        <div class="command-topbar">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="terminal-line">$ skillconnect session --role="<?= e($loggedInUser['role']); ?>"</div>
                        <div class="terminal-line">> welcome back <?= e(strtolower($loggedInUser['name'])); ?></div>
                        <div class="terminal-line">> recommended route: /dashboard.php</div>
                        <div class="terminal-line">> profile module: active</div>
                        <div class="terminal-line">> tuition workflow: available</div>
                        <div class="mt-4 pt-2">
                            <span class="hero-chip mb-3">Private workspace</span>
                            <h1 class="display-5 fw-bold mb-3">Your account is live inside the platform, not on the public landing page.</h1>
                            <p class="lead muted-text mb-4">This homepage now behaves like a logged-in control center with quick access to profile editing, tuition, recommendations, and core workflow pages.</p>
                            <form action="search.php" method="get" class="row g-2 mb-4">
                                <div class="col-md-9">
                                    <input type="search" name="q" class="form-control" placeholder="Search jobs, bootcamps, projects, skills, users">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-accent w-100">Search</button>
                                </div>
                            </form>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="dashboard.php" class="btn btn-accent btn-lg px-4">Open dashboard</a>
                                <a href="profile.php" class="btn btn-outline-soft btn-lg px-4">Edit profile</a>
                                <a href="bootcamps.php" class="btn btn-outline-soft btn-lg px-4">Bootcamps and tuition</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5" data-reveal>
                    <div class="overview-stack h-100">
                        <div class="glass-panel">
                            <div class="stack-label">Identity</div>
                            <h2 class="h4 fw-bold mb-2"><?= e($loggedInUser['name']); ?></h2>
                            <p class="muted-text mb-0"><?= e($loggedInUser['headline'] ?? 'SkillConnect member'); ?></p>
                        </div>
                        <div class="glass-panel">
                            <div class="stack-label">Role</div>
                            <div class="big-inline"><?= e(ucfirst($loggedInUser['role'])); ?></div>
                        </div>
                        <div class="glass-panel">
                            <div class="stack-label">Suggested next steps</div>
                            <ul class="list-clean mb-0">
                                <li>Update your account in `Profile`.</li>
                                <li>Use `Bootcamps` to review fee and payment status.</li>
                                <li>Use `Recommendations` if you are a student.</li>
                                <li>Use `Admin Panel` or `Trainer Panel` for operational work.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-4">
                <div class="col-md-4" data-reveal>
                    <div class="signal-card">
                        <div class="signal-number"><?= e((string) $stats['users']); ?></div>
                        <div class="signal-label">Registered accounts</div>
                    </div>
                </div>
                <div class="col-md-4" data-reveal>
                    <div class="signal-card">
                        <div class="signal-number"><?= e((string) $stats['bootcamps']); ?></div>
                        <div class="signal-label">Active bootcamps</div>
                    </div>
                </div>
                <div class="col-md-4" data-reveal>
                    <div class="signal-card">
                        <div class="signal-number"><?= e((string) $stats['jobs']); ?></div>
                        <div class="signal-label">Open roles</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="hero-section home-shell">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-xl-7" data-reveal>
                    <div class="hero-spotlight h-100">
                        <span class="hero-chip mb-3">Modern training and hiring network</span>
                        <h1 class="display-3 fw-bold mb-3">One platform where learners grow, trainers teach, and recruiters hire from verified work.</h1>
                        <p class="lead muted-text mb-4">SkillConnect combines bootcamps, tuition handling, profile building, skill verification, portfolio proof, job discovery, and role-based management in one realistic workflow.</p>
                        <form action="search.php" method="get" class="row g-2 mb-4">
                            <div class="col-md-9">
                                <input type="search" name="q" class="form-control" placeholder="Search jobs, bootcamps, projects, skills">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-accent w-100">Search</button>
                            </div>
                        </form>
                        <div class="d-flex flex-wrap gap-3 mb-5">
                            <a href="register.php" class="btn btn-accent btn-lg px-4">Create account</a>
                            <a href="login.php" class="btn btn-outline-soft btn-lg px-4">Login</a>
                        </div>

                        <div class="grid-mini">
                            <div class="mini-panel">
                                <div class="mini-label">Verified skills</div>
                                <div class="mini-value">Trainer/Admin reviewed</div>
                            </div>
                            <div class="mini-panel">
                                <div class="mini-label">Tuition flow</div>
                                <div class="mini-value">Student pays, admin transfers</div>
                            </div>
                            <div class="mini-panel">
                                <div class="mini-label">Portfolio proof</div>
                                <div class="mini-value">Files and live links</div>
                            </div>
                            <div class="mini-panel">
                                <div class="mini-label">Hiring path</div>
                                <div class="mini-value">Recommendations plus jobs</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5" data-reveal>
                    <div class="dashboard-mock h-100">
                        <div class="mock-header">
                            <div>
                                <div class="stack-label">Platform snapshot</div>
                                <h2 class="h4 fw-bold mb-0">Live ecosystem</h2>
                            </div>
                            <span class="status-pill status-open">Online</span>
                        </div>
                        <div class="mock-grid">
                            <div class="mock-stat">
                                <div class="mock-stat-value"><?= e((string) $stats['users']); ?></div>
                                <div class="mock-stat-label">Users</div>
                            </div>
                            <div class="mock-stat">
                                <div class="mock-stat-value"><?= e((string) $stats['bootcamps']); ?></div>
                                <div class="mock-stat-label">Bootcamps</div>
                            </div>
                            <div class="mock-stat">
                                <div class="mock-stat-value"><?= e((string) $stats['jobs']); ?></div>
                                <div class="mock-stat-label">Jobs</div>
                            </div>
                        </div>
                        <div class="mock-feed">
                            <div class="feed-item">
                                <span class="feed-dot blue"></span>
                                Students build portfolios and request skill verification
                            </div>
                            <div class="feed-item">
                                <span class="feed-dot green"></span>
                                Trainers create fee-based bootcamps and track transfers
                            </div>
                            <div class="feed-item">
                                <span class="feed-dot amber"></span>
                                Recruiters review job-ready candidate profiles
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-4">
                <div class="col-lg-8" data-reveal>
                    <div class="content-card section-frame h-100">
                        <div class="section-chip mb-3">Featured bootcamps</div>
                        <div class="row g-3">
                            <?php while ($bootcamp = $featuredBootcamps->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="subcard h-100">
                                        <div class="fw-bold mb-2"><?= e($bootcamp['title']); ?></div>
                                        <div class="small muted-text mb-2">Trainer: <?= e($bootcamp['trainer_name']); ?></div>
                                        <div class="code-price"><?= e(number_format((float) $bootcamp['fee'], 2)); ?> BDT</div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4" data-reveal>
                    <div class="content-card section-frame h-100">
                        <div class="section-chip mb-3">Latest jobs</div>
                        <ul class="list-clean">
                            <?php while ($job = $featuredJobs->fetch_assoc()): ?>
                                <li class="subcard">
                                    <div class="fw-bold"><?= e($job['title']); ?></div>
                                    <div class="small muted-text"><?= e($job['location'] ?: 'Remote'); ?></div>
                                    <div class="small muted-text mt-2"><?= e($job['required_skills']); ?></div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-4">
                <div class="col-md-4" data-reveal>
                    <div class="feature-card modern-feature h-100">
                        <div class="section-chip mb-3">01</div>
                        <h3 class="h4 fw-bold">Learn with structure</h3>
                        <p class="mb-0 muted-text">Students join bootcamps, pay tuition, build projects, and move through a real training pipeline.</p>
                    </div>
                </div>
                <div class="col-md-4" data-reveal>
                    <div class="feature-card modern-feature h-100">
                        <div class="section-chip mb-3">02</div>
                        <h3 class="h4 fw-bold">Teach with accountability</h3>
                        <p class="mb-0 muted-text">Trainers publish programs, verify skills, and track what the platform owes them after admin transfer.</p>
                    </div>
                </div>
                <div class="col-md-4" data-reveal>
                    <div class="feature-card modern-feature h-100">
                        <div class="section-chip mb-3">03</div>
                        <h3 class="h4 fw-bold">Hire from proof</h3>
                        <p class="mb-0 muted-text">Recruiters do not guess. They review verified skills, portfolio evidence, and platform activity.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
