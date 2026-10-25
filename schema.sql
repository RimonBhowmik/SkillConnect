CREATE DATABASE IF NOT EXISTS skillconnect;
USE skillconnect;

DROP TABLE IF EXISTS recommendations;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS bootcamps;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'trainer', 'recruiter') NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(30) NULL,
    headline VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bootcamps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    trainer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bootcamps_trainer FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bootcamp_id INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (user_id, bootcamp_id),
    CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_bootcamp FOREIGN KEY (bootcamp_id) REFERENCES bootcamps(id) ON DELETE CASCADE
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    file_url VARCHAR(255) NULL,
    project_link VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_skill (user_id, skill_id),
    CONSTRAINT fk_user_skills_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    required_skills TEXT NOT NULL,
    location VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jobs_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    cover_note TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'applied',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_application (job_id, student_id),
    CONSTRAINT fk_applications_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payments (
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
    CONSTRAINT fk_payments_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_bootcamp FOREIGN KEY (bootcamp_id) REFERENCES bootcamps(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_trainer FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation_type ENUM('bootcamp', 'job') NOT NULL,
    reference_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recommendations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, password, role, bio, phone, headline) VALUES
('Admin User', 'admin@skillconnect.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Platform administrator', '01700000001', 'System admin'),
('Tania Trainer', 'trainer@skillconnect.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'Web development trainer', '01700000002', 'Frontend and PHP trainer'),
('Sami Student', 'student@skillconnect.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Aspiring full-stack developer', '01700000003', 'Student developer'),
('Rima Recruiter', 'recruiter@skillconnect.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recruiter', 'Hiring for tech roles', '01700000004', 'Tech recruiter');

INSERT INTO skills (name) VALUES
('HTML'),
('CSS'),
('JavaScript'),
('PHP'),
('MySQL'),
('UI Design'),
('React'),
('Laravel');

INSERT INTO bootcamps (title, description, fee, trainer_id, start_date, end_date) VALUES
('Full Stack Web Bootcamp', 'Learn HTML, CSS, JavaScript, PHP, and MySQL through guided projects.', 3500.00, 2, '2026-05-01', '2026-07-15'),
('Frontend Accelerator', 'Build responsive interfaces and portfolio-ready frontend projects.', 2200.00, 2, '2026-06-01', '2026-08-01');

INSERT INTO enrollments (user_id, bootcamp_id, status) VALUES
(3, 1, 'approved');

INSERT INTO projects (user_id, title, description, file_url, project_link) VALUES
(3, 'Personal Portfolio', 'Responsive portfolio website built with Bootstrap and custom CSS.', NULL, 'https://example.com/portfolio');

INSERT INTO user_skills (user_id, skill_id, verified, verified_by) VALUES
(3, 1, 1, 2),
(3, 2, 1, 2),
(3, 3, 1, 2),
(3, 4, 0, NULL);

INSERT INTO jobs (recruiter_id, title, description, required_skills, location) VALUES
(4, 'Junior PHP Developer', 'Work on internal platforms, fix bugs, and build dashboard features.', 'PHP, MySQL, JavaScript', 'Dhaka'),
(4, 'Frontend Intern', 'Support UI implementation with HTML, CSS, Bootstrap, and JavaScript.', 'HTML, CSS, JavaScript', 'Remote');

INSERT INTO recommendations (user_id, recommendation_type, reference_id, reason) VALUES
(3, 'bootcamp', 2, 'Recommended because you already completed a web bootcamp and have verified frontend skills.'),
(3, 'job', 2, 'Recommended because your verified HTML, CSS, and JavaScript skills match this role.');
