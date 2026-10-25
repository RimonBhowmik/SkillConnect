<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();

    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: dashboard.php');
        exit;
    }
}

function currentUser(mysqli $conn): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id, name, email, role, bio, phone, headline, created_at FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $user;
}

function redirectByRole(string $role): void
{
    $routes = [
        'admin' => 'admin_panel.php',
        'trainer' => 'trainer_panel.php',
        'student' => 'student_dashboard.php',
        'recruiter' => 'recruiter_dashboard.php',
    ];

    header('Location: ' . ($routes[$role] ?? 'dashboard.php'));
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getStudentSkillNames(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare("
        SELECT skills.name
        FROM user_skills
        INNER JOIN skills ON skills.id = user_skills.skill_id
        WHERE user_skills.user_id = ?
        ORDER BY user_skills.verified DESC, skills.name ASC
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = strtolower($row['name']);
    }

    $stmt->close();
    return array_values(array_unique($skills));
}

function buildRecommendations(mysqli $conn, int $userId, string $type, int $limit = 5): array
{
    $skills = getStudentSkillNames($conn, $userId);
    $items = [];

    if ($type === 'job') {
        $stmt = $conn->prepare("
            SELECT jobs.id, jobs.title, jobs.description, jobs.required_skills, jobs.location
            FROM jobs
            WHERE jobs.id NOT IN (
                SELECT job_id FROM applications WHERE student_id = ?
            )
            ORDER BY jobs.created_at DESC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $haystack = strtolower($row['title'] . ' ' . $row['description'] . ' ' . $row['required_skills']);
            $matched = [];
            foreach ($skills as $skill) {
                if ($skill !== '' && str_contains($haystack, $skill)) {
                    $matched[] = ucfirst($skill);
                }
            }

            $score = count($matched);
            if ($score > 0 || empty($skills)) {
                $row['score'] = $score;
                $row['reason'] = $score > 0
                    ? 'Matched skills: ' . implode(', ', $matched)
                    : 'Recommended because this is a current open role for new applicants.';
                $items[] = $row;
            }
        }

        $stmt->close();
    }

    if ($type === 'bootcamp') {
        $stmt = $conn->prepare("
            SELECT bootcamps.id, bootcamps.title, bootcamps.description, bootcamps.start_date, bootcamps.end_date,
                   users.name AS trainer_name
            FROM bootcamps
            INNER JOIN users ON users.id = bootcamps.trainer_id
            WHERE bootcamps.id NOT IN (
                SELECT bootcamp_id FROM enrollments WHERE user_id = ?
            )
            ORDER BY bootcamps.start_date ASC
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $haystack = strtolower($row['title'] . ' ' . $row['description']);
            $matched = [];
            foreach ($skills as $skill) {
                if ($skill !== '' && str_contains($haystack, $skill)) {
                    $matched[] = ucfirst($skill);
                }
            }

            $score = count($matched);
            if ($score > 0 || empty($skills)) {
                $row['score'] = $score;
                $row['reason'] = $score > 0
                    ? 'Suggested because it aligns with your skills: ' . implode(', ', $matched)
                    : 'Suggested as an available learning path to start building your profile.';
                $items[] = $row;
            }
        }

        $stmt->close();
    }

    usort($items, static function (array $a, array $b): int {
        return ($b['score'] <=> $a['score']) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });

    return array_slice($items, 0, $limit);
}

function searchSite(mysqli $conn, string $query, bool $includeUsers = false): array
{
    $query = trim($query);

    if ($query === '') {
        return [
            'jobs' => [],
            'bootcamps' => [],
            'projects' => [],
            'skills' => [],
            'users' => [],
        ];
    }

    $wildcard = '%' . $query . '%';

    $stmt = $conn->prepare("
        SELECT title, location, required_skills
        FROM jobs
        WHERE title LIKE ? OR description LIKE ? OR required_skills LIKE ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param('sss', $wildcard, $wildcard, $wildcard);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT bootcamps.title, users.name AS trainer_name, bootcamps.start_date, bootcamps.end_date, bootcamps.fee
        FROM bootcamps
        INNER JOIN users ON users.id = bootcamps.trainer_id
        WHERE bootcamps.title LIKE ? OR bootcamps.description LIKE ?
        ORDER BY bootcamps.start_date ASC
    ");
    $stmt->bind_param('ss', $wildcard, $wildcard);
    $stmt->execute();
    $bootcamps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT projects.title, users.name, projects.project_link
        FROM projects
        INNER JOIN users ON users.id = projects.user_id
        WHERE projects.title LIKE ? OR projects.description LIKE ?
        ORDER BY projects.created_at DESC
    ");
    $stmt->bind_param('ss', $wildcard, $wildcard);
    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT name
        FROM skills
        WHERE name LIKE ?
        ORDER BY name ASC
    ");
    $stmt->bind_param('s', $wildcard);
    $stmt->execute();
    $skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $users = [];
    if ($includeUsers) {
        $stmt = $conn->prepare("
            SELECT name, email, role
            FROM users
            WHERE name LIKE ? OR email LIKE ? OR role LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('sss', $wildcard, $wildcard, $wildcard);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    return [
        'jobs' => $jobs,
        'bootcamps' => $bootcamps,
        'projects' => $projects,
        'skills' => $skills,
        'users' => $users,
    ];
}

function chatbotReply(mysqli $conn, string $message, bool $includeUsers = false): string
{
    $message = strtolower(trim($message));

    if ($message === '') {
        return 'Ask about login, bootcamps, skills, jobs, portfolio, or recommendations.';
    }

    $rules = [
        'login' => 'Use your email and password on the login page. If you do not have an account, register as a student, trainer, or recruiter.',
        'register' => 'Registration is open for student, trainer, and recruiter accounts. Admin accounts are not self-registered.',
        'bootcamp' => 'Go to Bootcamps to view available bootcamps. Students can enroll and trainers can create new bootcamps.',
        'portfolio' => 'Go to Portfolio to add projects with a description, optional file upload, and optional project link.',
        'skill' => 'Students can submit skills for review on the Skills page. Trainers and admins can verify them.',
        'job' => 'Recruiters can post jobs from their dashboard. Students can browse jobs and apply with a cover note.',
        'recommend' => 'Recommendations are generated from your skills, applications, and enrollments to suggest relevant jobs and bootcamps.',
        'search' => 'Use the search box in the top navigation to search jobs, bootcamps, portfolio projects, and users.',
        'admin' => 'Admins can verify skills, delete non-admin users, review users, and add skill records from the admin panel.',
    ];

    foreach ($rules as $keyword => $reply) {
        if (str_contains($message, $keyword)) {
            return $reply;
        }
    }

    $results = searchSite($conn, $message, $includeUsers);
    $parts = [];

    if ($results['jobs'] !== []) {
        $parts[] = 'Jobs: ' . implode(', ', array_map(static fn(array $item): string => $item['title'], array_slice($results['jobs'], 0, 3)));
    }

    if ($results['bootcamps'] !== []) {
        $parts[] = 'Bootcamps: ' . implode(', ', array_map(static fn(array $item): string => $item['title'], array_slice($results['bootcamps'], 0, 3)));
    }

    if ($results['projects'] !== []) {
        $parts[] = 'Projects: ' . implode(', ', array_map(static fn(array $item): string => $item['title'], array_slice($results['projects'], 0, 3)));
    }

    if ($results['skills'] !== []) {
        $parts[] = 'Skills: ' . implode(', ', array_map(static fn(array $item): string => $item['name'], array_slice($results['skills'], 0, 5)));
    }

    if ($includeUsers && $results['users'] !== []) {
        $parts[] = 'Users: ' . implode(', ', array_map(static fn(array $item): string => $item['name'], array_slice($results['users'], 0, 3)));
    }

    if ($parts !== []) {
        return 'I searched the platform for "' . $message . '". ' . implode(' | ', $parts) . '.';
    }

    return 'I could not find a matching result for "' . $message . '" in jobs, bootcamps, projects, skills, or users.';
}
?>
