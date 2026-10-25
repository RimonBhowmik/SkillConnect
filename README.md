
# SkillConnect — Skill-Based Learning & Hiring Platform

SkillConnect is a PHP and MySQL web application designed to connect students, trainers, and recruiters through skill-based learning, portfolio development, and job opportunities.

The platform provides role-based dashboards and tools for managing bootcamps, tracking skill development, showcasing projects, and connecting learners with potential employers.

## ✨ Key Features

- **Authentication & Role-Based Access** — Separate dashboards and access for administrators, trainers, students, and recruiters.
- **Bootcamp Management** — Create bootcamps and manage student enrollments.
- **Portfolio Management** — Create and manage portfolio projects to showcase practical skills.
- **Skill Submission & Verification** — Submit skills and support skill verification workflows.
- **Job Management** — Publish job opportunities and manage job applications.
- **Recommendation Display** — Display basic recommendations for students.
- **Responsive Interface** — Custom styling and client-side interactions for a user-friendly experience.
- **Database Integration** — Store application data using MySQL.

## 🛠️ Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Server-side application logic |
| MySQL | Database management |
| HTML5 | Web page structure |
| CSS3 | Styling and layout |
| JavaScript | Client-side interactions |
| Bootstrap | UI components and responsive design |
| Apache | Local web server |
| XAMPP | Local development environment |

## 📂 Project Structure

```text
SkillConnect/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── config/
│   └── database.php
├── includes/
│   └── Shared layout and authentication helpers
├── schema.sql
└── README.md
```

> Note: This is a simplified overview of the project structure. Additional application files and folders may exist in the repository.

## 🚀 Getting Started

Follow these steps to run SkillConnect on your local machine.

### Prerequisites

Install the following tools before starting:

- [XAMPP](https://www.apachefriends.org/)
- A modern web browser
- A code editor such as [Visual Studio Code](https://code.visualstudio.com/)

### Installation

**1. Clone the repository**

Open a terminal and run:

```bash
git clone https://github.com/RimonBhowmik/SkillConnect.git
```

**2. Move the project into XAMPP**

Copy or move the project folder into your XAMPP `htdocs` directory.

Example on Windows:

```text
C:\xampp\htdocs\SkillConnect
```

**3. Start Apache and MySQL**

Open the XAMPP Control Panel and start:

- Apache
- MySQL

**4. Create and import the database**

1. Open [phpMyAdmin](http://localhost/phpmyadmin/).
2. Create the database if required by your SQL configuration.
3. Select the appropriate database.
4. Import the project's `schema.sql` file.

If the SQL file creates the database itself, follow its instructions rather than creating a duplicate database.

**5. Configure the database connection**

Open:

```text
config/database.php
```

Update the database host, database name, username, and password according to your local MySQL configuration.

Example configuration values:

```text
Host: localhost
Username: root
Password: (your local MySQL password)
```

> Use the actual database name and connection variable names defined in your project. The example above describes typical local XAMPP settings.

**6. Run the application**

Open the following URL in your browser:

http://localhost/SkillConnect/

If you used a different folder name, replace `SkillConnect` with that folder name.

## 🔐 Demo Accounts

After importing the database, the following demo accounts may be used if they are present in the supplied SQL data.

| Role | Email |
|---|---|
| Administrator | `admin@skillconnect.test` |
| Trainer | `trainer@skillconnect.test` |
| Student | `student@skillconnect.test` |
| Recruiter | `recruiter@skillconnect.test` |

**Demo password:** `password`

> These credentials depend on the sample data in `schema.sql`. If an account is missing or a login fails, verify the seeded user records and password-hashing logic before using the account.

For security, use these sample credentials only in a local development environment. Never deploy a public application with default demo passwords.

## 🗄️ Database

SkillConnect uses MySQL to manage application data.

The `schema.sql` file contains the database schema and sample data used to initialize the application.

Before running the application, ensure that:

- The database has been imported successfully.
- The database connection settings are correct.
- The configured MySQL service is running.
- The application has access to the required database tables.

## 📁 Important Files

| File or Directory | Description |
|---|---|
| `config/database.php` | MySQL database connection configuration |
| `includes/` | Shared layout components and authentication helpers |
| `assets/css/style.css` | Custom application styling |
| `assets/js/app.js` | Client-side JavaScript behavior |
| `schema.sql` | Database schema and sample data |
| `README.md` | Project documentation and setup guide |

## 🔒 Security Considerations

For a safer deployment:

- Store passwords using secure password hashing.
- Validate and sanitize user input on the server.
- Use prepared statements for database queries.
- Enforce authorization checks for role-specific operations.
- Keep production database credentials out of public repositories.
- Change or remove demo credentials before deployment.
- Use HTTPS and appropriate session security settings in production.

These are recommended practices; verify the implementation in the source code before assuming every measure is already in place.

## 🛣️ Future Improvements

Potential enhancements for SkillConnect include:

- Advanced skill-based recommendation algorithms.
- Email notifications for enrollments and job applications.
- Improved skill verification workflows.
- Resume uploads and downloadable student portfolios.
- Advanced job search, filtering, and matching.
- An analytics dashboard for administrators and trainers.
- Automated testing and deployment workflows.

## 🎯 Project Objectives

SkillConnect aims to bring learning, skill development, portfolio building, and employment opportunities together in one platform.

It provides a foundation for exploring full-stack web development, relational database design, authentication, role-based access control, and practical application development.

## 👨‍💻 Author

**Rimon Chandra Bhowmik**

- GitHub: [@RimonBhowmik](https://github.com/RimonBhowmik)
- LinkedIn: [Connect on LinkedIn](https://www.linkedin.com/in/rimon-bhowmik-307063318/)

## 📄 License

No license has been specified here. If you intend to distribute this project publicly, consider adding an appropriate `LICENSE` file and updating this section.

---

<p align="center">
  Built with PHP, MySQL, and a focus on connecting skills with opportunities.
</p>
