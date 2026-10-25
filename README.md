# SkillConnect

SkillConnect is a PHP and MySQL web application for skill-based learning and hiring. It includes:

- Authentication and role-based dashboards
- Bootcamp creation and enrollments
- Portfolio project management
- Skill submission and verification
- Job posting and job applications
- Basic recommendation display for students

## Project structure

- `config/database.php` - MySQL connection
- `includes/` - auth helpers and shared layout
- `assets/css/style.css` - custom UI styling
- `assets/js/app.js` - lightweight client-side behavior
- `schema.sql` - database schema and sample data

## How to run

1. Copy this folder into your XAMPP `htdocs` directory, for example `C:\xampp\htdocs\skillconnect`.
2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin and import `schema.sql`.
4. Update `config/database.php` if your MySQL credentials are different.
5. Visit `http://localhost/skillconnect/`.

## Demo logins

After importing the SQL file, you can log in with these accounts using password `password`:

- `admin@skillconnect.test`
- `trainer@skillconnect.test`
- `student@skillconnect.test`
- `recruiter@skillconnect.test`
