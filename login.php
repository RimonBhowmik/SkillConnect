<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectByRole($_SESSION['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        setFlash('success', 'Welcome back, ' . $user['name'] . '.');
        redirectByRole($user['role']);
    }

    setFlash('danger', 'Invalid email or password.');
}

$pageTitle = 'SkillConnect | Login';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="auth-card" data-reveal>
                    <span class="section-chip mb-2">Secure access</span>
                    <h1 class="h3 fw-bold">Login to SkillConnect</h1>
                    <p class="muted-text">Use your registered email and password to access your dashboard.</p>
                    <form method="post" class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12 d-flex gap-3 flex-wrap">
                            <button type="submit" class="btn btn-accent px-4">Login</button>
                            <a href="register.php" class="btn btn-outline-soft px-4">Create account</a>
                        </div>
                    </form>
                    <div class="mt-4 pt-3 border-top">
                        <p class="mb-2 fw-bold">Demo accounts after importing SQL</p>
                        <p class="mb-0 muted-text">Use `admin@skillconnect.test`, `trainer@skillconnect.test`, `student@skillconnect.test`, or `recruiter@skillconnect.test` with password `password`.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
