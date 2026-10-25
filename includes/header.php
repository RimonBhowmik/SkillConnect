<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'SkillConnect';
$flash = getFlash();
$loggedInUser = currentUser($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">SkillConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <form class="d-flex ms-lg-auto me-lg-3 my-3 my-lg-0" action="search.php" method="get" role="search">
                    <input class="form-control form-control-sm search-input" type="search" name="q" placeholder="Search everything" value="<?= e($_GET['q'] ?? ''); ?>">
                </form>
                <ul class="navbar-nav align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <?php if ($loggedInUser): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="bootcamps.php">Bootcamps</a></li>
                        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfolio</a></li>
                        <li class="nav-item"><a class="nav-link" href="skills.php">Skills</a></li>
                        <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <?php if ($loggedInUser['role'] === 'student'): ?>
                            <li class="nav-item"><a class="nav-link" href="recommendations.php">Recommendations</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="chatbot.php">Chatbot</a></li>
                        <li class="nav-item"><span class="nav-link text-white-50"><?= e($loggedInUser['name']); ?></span></li>
                        <li class="nav-item"><a class="btn btn-accent btn-sm px-3" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-accent btn-sm px-3" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <main class="pb-5">
