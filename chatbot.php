<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$conversation = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $includeUsers = ($_SESSION['role'] ?? '') === 'admin';
    $conversation[] = ['role' => 'user', 'text' => $message];
    $conversation[] = ['role' => 'bot', 'text' => chatbotReply($conn, $message, $includeUsers)];
}

$pageTitle = 'SkillConnect | Chatbot';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <span class="section-chip mb-3">Chatbot</span>
        <h1 class="display-6 fw-bold">Basic project assistant</h1>
        <p class="muted-text">This helper now uses the same site-wide search criteria as the search page, so it can look through platform content instead of only replying with fixed text.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="content-card" data-reveal>
            <div class="chat-log mb-4">
                <div class="chat-bubble bot">
                    Ask a question or type anything you want to search. I can search jobs, bootcamps, projects, skills, and for admins, users too.
                </div>
                <?php foreach ($conversation as $message): ?>
                    <div class="chat-bubble <?= e($message['role']); ?>">
                        <?= e($message['text']); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" class="row g-3">
                <div class="col-md-10">
                    <label class="form-label">Your message</label>
                    <input type="text" name="message" class="form-control" placeholder="Example: how do I apply for a job?" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-accent w-100">Send</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
