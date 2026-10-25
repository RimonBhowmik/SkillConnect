<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectByRole($_SESSION['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $bio = trim($_POST['bio'] ?? '');

    if ($name === '' || $email === '' || $password === '' || !in_array($role, ['student', 'trainer', 'recruiter'], true)) {
        setFlash('danger', 'Please complete all required fields.');
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($exists) {
            setFlash('warning', 'An account with that email already exists.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, bio) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $name, $email, $hash, $role, $bio);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Registration complete. Please log in.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'SkillConnect | Register';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="auth-card" data-reveal>
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <span class="section-chip mb-2">Create account</span>
                            <h1 class="h3 fw-bold mb-1">Join SkillConnect</h1>
                            <p class="muted-text mb-0">Create a student, trainer, or recruiter account and start using the platform.</p>
                        </div>
                    </div>
                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select role</option>
                                <option value="student">Student</option>
                                <option value="trainer">Trainer</option>
                                <option value="recruiter">Recruiter</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Short bio</label>
                            <textarea name="bio" class="form-control" rows="4" placeholder="Tell others what you do or want to learn."></textarea>
                        </div>
                        <div class="col-12 d-flex gap-3 flex-wrap">
                            <button type="submit" class="btn btn-accent px-4">Register</button>
                            <a href="login.php" class="btn btn-outline-soft px-4">Already have an account?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
