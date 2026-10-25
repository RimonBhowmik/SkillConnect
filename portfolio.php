<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($role === 'student' || $role === 'trainer')) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $projectLink = trim($_POST['project_link'] ?? '');
    $filePath = null;

    if ($title === '' || $description === '') {
        setFlash('danger', 'Title and description are required.');
    } else {
        if (!empty($_FILES['project_file']['name'])) {
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['project_file']['name']));
            $targetPath = __DIR__ . '/uploads/' . $filename;
            if (move_uploaded_file($_FILES['project_file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/' . $filename;
            }
        }

        $stmt = $conn->prepare('INSERT INTO projects (user_id, title, description, file_url, project_link) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $userId, $title, $description, $filePath, $projectLink);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Project added to your portfolio.');
    }

    header('Location: portfolio.php');
    exit;
}

if ($role === 'admin' || $role === 'recruiter') {
    $projects = $conn->query("
        SELECT projects.*, users.name
        FROM projects
        INNER JOIN users ON users.id = projects.user_id
        ORDER BY projects.created_at DESC
    ");
} else {
    $stmt = $conn->prepare('SELECT projects.*, users.name FROM projects INNER JOIN users ON users.id = projects.user_id WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $projects = $stmt->get_result();
}

$pageTitle = 'SkillConnect | Portfolio';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Portfolio</span>
        <h1 class="display-6 fw-bold">Show what you have built</h1>
        <p class="muted-text">Students and trainers can upload projects, while recruiters can review real work.</p>
    </div>
</section>

<section>
    <div class="container">
        <?php if ($role === 'student' || $role === 'trainer'): ?>
            <div class="content-card mb-4" data-reveal>
                <h2 class="h5 fw-bold mb-3">Add a portfolio project</h2>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Project title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project link</label>
                        <input type="url" name="project_link" class="form-control" placeholder="https://your-demo-link.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Upload file (optional)</label>
                        <input type="file" name="project_file" class="form-control">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-accent px-4">Save project</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if ($projects->num_rows > 0): ?>
                <?php while ($project = $projects->fetch_assoc()): ?>
                    <div class="col-md-6 col-xl-4" data-reveal>
                        <div class="content-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h2 class="h5 fw-bold mb-1"><?= e($project['title']); ?></h2>
                                    <div class="small muted-text">By <?= e($project['name']); ?></div>
                                </div>
                            </div>
                            <p class="muted-text"><?= e($project['description']); ?></p>
                            <?php if (!empty($project['project_link'])): ?>
                                <p class="mb-2"><a href="<?= e($project['project_link']); ?>" target="_blank" rel="noopener">Open live project</a></p>
                            <?php endif; ?>
                            <?php if (!empty($project['file_url'])): ?>
                                <p class="mb-0"><a href="<?= e($project['file_url']); ?>" target="_blank">Download uploaded file</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12" data-reveal>
                    <div class="empty-state">No portfolio projects have been added yet.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
require_once __DIR__ . '/includes/footer.php';
?>
