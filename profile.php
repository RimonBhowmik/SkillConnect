<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $headline = trim($_POST['headline'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if ($name === '' || $email === '') {
        setFlash('danger', 'Name and email are required.');
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            setFlash('warning', 'That email is already used by another account.');
        } else {
            if ($newPassword !== '') {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, bio = ?, phone = ?, headline = ?, password = ? WHERE id = ?');
                $stmt->bind_param('ssssssi', $name, $email, $bio, $phone, $headline, $passwordHash, $userId);
            } else {
                $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, bio = ?, phone = ?, headline = ? WHERE id = ?');
                $stmt->bind_param('sssssi', $name, $email, $bio, $phone, $headline, $userId);
            }

            $stmt->execute();
            $stmt->close();
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Profile updated successfully.');
        }
    }

    header('Location: profile.php');
    exit;
}

$user = currentUser($conn);
$pageTitle = 'SkillConnect | Profile';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">My profile</span>
        <h1 class="display-6 fw-bold">Edit your account</h1>
        <p class="muted-text">Update your public information and optionally set a new password.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4" data-reveal>
                <div class="content-card profile-card h-100">
                    <span class="section-chip mb-3"><?= e(ucfirst($user['role'])); ?></span>
                    <h2 class="h4 fw-bold mb-1"><?= e($user['name']); ?></h2>
                    <p class="muted-text mb-3"><?= e($user['headline'] ?: 'Add a short headline for your profile'); ?></p>
                    <ul class="list-clean">
                        <li>Email: <?= e($user['email']); ?></li>
                        <li>Phone: <?= e($user['phone'] ?: 'Not added'); ?></li>
                        <li>Joined: <?= e($user['created_at']); ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-8" data-reveal>
                <div class="content-card">
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Headline</label>
                            <input type="text" name="headline" class="form-control" value="<?= e($user['headline']); ?>" placeholder="Example: Full-stack learner">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="5"><?= e($user['bio']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">New password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep the current password">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent px-4">Save profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
