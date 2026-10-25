<?php
declare(strict_types=1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'skillconnect';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

if (!columnExists($conn, 'users', 'phone')) {
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL AFTER bio");
}

if (!columnExists($conn, 'users', 'headline')) {
    $conn->query("ALTER TABLE users ADD COLUMN headline VARCHAR(150) NULL AFTER phone");
}

if (!columnExists($conn, 'bootcamps', 'fee')) {
    $conn->query("ALTER TABLE bootcamps ADD COLUMN fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER description");
}

$conn->query("
    CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL UNIQUE,
        student_id INT NOT NULL,
        bootcamp_id INT NOT NULL,
        trainer_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NULL,
        transaction_ref VARCHAR(100) NULL,
        status ENUM('submitted', 'transferred') NOT NULL DEFAULT 'submitted',
        admin_note VARCHAR(255) NULL,
        paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        transferred_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (bootcamp_id) REFERENCES bootcamps(id) ON DELETE CASCADE,
        FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
?>
