<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'student') {
    $skillId = (int) ($_POST['skill_id'] ?? 0);

    if ($skillId > 0) {
        $stmt = $conn->prepare('INSERT IGNORE INTO user_skills (user_id, skill_id, verified) VALUES (?, ?, 0)');
        $stmt->bind_param('ii', $userId, $skillId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Skill added for review.');
    }

    header('Location: skills.php');
    exit;
}

$allSkills = $conn->query('SELECT id, name FROM skills ORDER BY name ASC');

if ($role === 'student') {
    $stmt = $conn->prepare("
        SELECT user_skills.id, skills.name, user_skills.verified, verifier.name AS verified_by_name
        FROM user_skills
        INNER JOIN skills ON skills.id = user_skills.skill_id
        LEFT JOIN users AS verifier ON verifier.id = user_skills.verified_by
        WHERE user_skills.user_id = ?
        ORDER BY user_skills.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $skillList = $stmt->get_result();
} else {
    $skillList = $conn->query("
        SELECT users.name AS user_name, skills.name, user_skills.verified, verifier.name AS verified_by_name
        FROM user_skills
        INNER JOIN skills ON skills.id = user_skills.skill_id
        INNER JOIN users ON users.id = user_skills.user_id
        LEFT JOIN users AS verifier ON verifier.id = user_skills.verified_by
        ORDER BY user_skills.created_at DESC
    ");
}

$pageTitle = 'SkillConnect | Skills';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Skills</span>
        <h1 class="display-6 fw-bold">Track and verify capability</h1>
        <p class="muted-text">Students can submit skills, and trainers or admins can verify them for credibility.</p>
    </div>
</section>

<section>
    <div class="container">
        <?php if ($role === 'student'): ?>
            <div class="content-card mb-4" data-reveal>
                <h2 class="h5 fw-bold mb-3">Add a skill</h2>
                <form method="post" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Choose a skill</label>
                        <select name="skill_id" class="form-select" required>
                            <option value="">Select a skill</option>
                            <?php while ($skill = $allSkills->fetch_assoc()): ?>
                                <option value="<?= e((string) $skill['id']); ?>"><?= e($skill['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-accent px-4 w-100">Submit skill</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="content-card table-card" data-reveal>
            <h2 class="h5 fw-bold mb-3"><?= $role === 'student' ? 'My skills' : 'Submitted skills'; ?></h2>
            <?php if ($skillList->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <?php if ($role !== 'student'): ?><th>User</th><?php endif; ?>
                                <th>Skill</th>
                                <th>Status</th>
                                <th>Verified by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $skillList->fetch_assoc()): ?>
                                <tr>
                                    <?php if ($role !== 'student'): ?><td><?= e($item['user_name']); ?></td><?php endif; ?>
                                    <td><?= e($item['name']); ?></td>
                                    <td>
                                        <span class="status-pill <?= (int) $item['verified'] === 1 ? 'status-verified' : 'status-pending'; ?>">
                                            <?= (int) $item['verified'] === 1 ? 'Verified' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td><?= e($item['verified_by_name'] ?? 'Not verified yet'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">No skill records yet.</div>
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
