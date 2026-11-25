# Student Attendance Management System

A web app for recording and reviewing class attendance. Admins set up the school, teachers take attendance for their own classes, and students view their own attendance.

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Frontend | Vue 3, Inertia.js, Vuetify, Vite |
| Database | MySQL / MariaDB |
| Local environment | Laravel Herd (PHP, Composer, nginx) + XAMPP (MySQL) |

## Features

- **Roles:** admin, teacher, student, each with its own menu and permissions
- **No public registration:** the admin creates every account
- **Passwords:** system-generated temporary passwords shown once, forced change at first login, Change Password page
- **Setup:** sections, teachers (auto number `T-0001`), subjects, students (auto number `2026-0001`)
- **Schedules:** subject + teacher + section + day/time/room, with conflict checks for teachers and sections
- **Take attendance:**
  - Students start unmarked, and everyone must be marked before saving
  - The date must match the class's scheduled day and cannot be in the future
  - Editing saved attendance requires a reason; teachers can edit only within 7 days
- **Audit log:** every edit to saved attendance (old status, new status, who, when, reason)
- **Records:** latest records, with an "Edited" badge on changed rows
- **Reports:** filter by section, subject, and date; export to CSV; print
- **Dashboard:** totals, today's summary, 7-day trend, low-attendance list (below 75%)
- **User management:** change roles, activate or deactivate accounts, generate a new temporary password

## Roles

| Role | Access |
|---|---|
| Admin | Everything |
| Teacher | Own schedules (edit day/time/room), take attendance for own classes, records and reports for own classes, own students |
| Student | My Attendance (own classes, per-subject counts, history), Change Password |

Attendance rate is (Present + Late) / all records.

## Requirements

- Laravel Herd (PHP 8.2+ and Composer)
- XAMPP (MySQL)
- Node.js 20.19+ or 22+
- Git

## Setup

1. Clone the project into your Herd folder:
   ```
   cd C:\Users\<you>\Herd
   git clone <repository-url> Student-Attendance-Management-System
   cd Student-Attendance-Management-System
   ```
2. Install dependencies:
   ```
   composer install
   npm install
   ```
3. Create the environment file and key:
   ```
   copy .env.example .env
   php artisan key:generate
   ```
4. Start **MySQL** in XAMPP. Create an empty database (for example `student_attendance_management_system`).
5. In `.env`, set:
   ```dotenv
   APP_URL=http://student-attendance-management-system.test
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=student_attendance_management_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```
6. Run the migrations:
   ```
   php artisan migrate
   ```
7. Create the first admin (registration is disabled):
   ```
   php artisan tinker
   ```
   ```php
   App\Models\User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>'choose-a-password','role'=>'admin']);
   ```
8. Make sure Herd is running, then start the frontend:
   ```
   npm run dev
   ```
9. Open `http://student-attendance-management-system.test` and log in.

> Herd serves on port 80. Keep XAMPP's Apache stopped, or move it to port 8080 if you want phpMyAdmin (`http://localhost:8080/phpmyadmin`).

## Daily routine

1. Start MySQL in XAMPP.
2. Make sure Herd is running.
3. Run `npm run dev` in the project folder.
4. Open the site.

## Recommended first-time workflow (admin)

1. **Sections & Teachers:** add sections and teachers. Copy the temporary password from the popup.
2. **Subjects:** add subjects.
3. **Students:** add students (give a login email if the student should sign in).
4. **Schedules:** assign subject, teacher, section, day, and time.
5. Teachers log in, change their password, and take attendance.

## Testing

Tests use a separate database so real data is never touched.

1. Create an empty database named `attendance_testing`.
2. Confirm `phpunit.xml` points `DB_DATABASE` at it.
3. Run:
   ```
   php artisan test
   ```

The suite covers role access, teacher ownership, attendance date rules, the reason-required rule, deactivated accounts, and forced password change.

## Notes

- Mail is only logged (`MAIL_MAILER=log`), so emailed password resets do not work. The admin generates a new temporary password from **Users**.
- Deleting students, subjects, or schedules is blocked while attendance data depends on them. Deactivate accounts instead of deleting them.

## Before deploying for real use

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Use a real MySQL user and password (not empty root)
- Configure real mail settings
- Serve over HTTPS and back up the database regularly