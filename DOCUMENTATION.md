# CSE Department Portal — Complete Documentation
## Department of Computer Science & Engineering — Jatiya Kabi Kazi Nazrul Islam University

**Stack:** PHP 8 (OOP, PDO) + MySQL + HTML5/CSS3/JS ES6+ — Zero frameworks, zero external dependencies.

---

## 1. FOLDER STRUCTURE

```
cse_portal/
├── app/
│   ├── classes/            ← 19 OOP classes (Database, Auth, all Models)
│   ├── models/             ← (reserved for future expansion)
│   └── controllers/        ← (reserved for future expansion)
├── config/
│   ├── config.php          ← constants, autoloader, session bootstrap
│   └── database.php        ← PDO connection loader
├── includes/
│   ├── header.php / footer.php
│   ├── navbar.php / sidebar.php   ← role-adaptive
│   └── auth_check.php      ← bootstraps every protected page
├── assets/
│   ├── css/ (style.css, auth.css, dashboard.css)
│   └── js/script.js        ← ES6+, sidebar/modal/chat/validation logic
├── uploads/                ← photos, notices, routines, results, resources, messages
├── admin/                  ← 15 pages
├── teacher/                ← 10 pages
├── student/                ← 10 pages
├── sql/database.sql        ← full schema + sample data
├── index.php / login.php / register.php
├── forgot_password.php / reset_password.php / logout.php
└── setup_passwords.php     ← run once, then delete
```

**Total: 68 PHP files**, 19 of which are reusable OOP classes (`Database`, `Auth`, `User`, `Student`, `Teacher`, `FileUpload`, `Helper`, `BatchModel`, `CourseModel`, `NoticeModel`, `RoutineModel`, `ExamModel`, `ResultModel`, `ResourceModel`, `DiscussionModel`, `MessageModel`, `NotificationModel`, `ProfileRequestModel`, `SettingsModel`).

---

## 2. ER DIAGRAM DESCRIPTION

```
users (1)──(1) students ──(N:1)── batches
users (1)──(1) teachers
users (1)──(N) notices [posted_by]          notices (1)──(N) notice_files
users (1)──(N) routines [created_by]        routines: batch_id → batches
users (1)──(N) result_files [uploaded_by]   batches (1)──(N) routine_files
users (1)──(N) resources (via teachers)     batches (1)──(N) exam_schedules
users (1)──(N) discussions / replies
users (1)──(N) messages [sender/receiver]   messages (1)──(N) message_files
users (1)──(N) notifications
users (1)──(N) profile_requests

courses (1)──(N) course_assignments ──(N:1)── batches
courses (1)──(N) course_assignments ──(N:1)── teachers   [LOCKED once created]
courses (1)──(N) exam_schedules
courses (1)──(N) results
courses (1)──(N) resources
courses (1)──(N) discussions

students (1)──(N) results
settings — single configuration row (portal/university/department branding)
```

**18 core tables + settings.** All foreign keys use `ON DELETE CASCADE` (or `SET NULL` for batch references on students) to maintain referential integrity automatically.

---

## 3. INSTALLATION GUIDE (Mac M1 + XAMPP)

### Step 1 — Start XAMPP
Open **XAMPP Control Panel** → Start **Apache** and **MySQL**.

### Step 2 — Copy Project
```bash
cp -r cse_portal /Applications/XAMPP/xamppfiles/htdocs/
```
Final path must be: `/Applications/XAMPP/xamppfiles/htdocs/cse_portal/`

### Step 3 — Create Database
Open **http://localhost/phpmyadmin** → New database `cse_portal` (collation `utf8mb4_unicode_ci`) → Import → select `cse_portal/sql/database.sql` → Go.

*(Or via terminal: `/Applications/XAMPP/xamppfiles/bin/mysql -u root -p < sql/database.sql`)*

### Step 4 — Verify DB Config
`config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cse_portal');
define('DB_USER', 'root');
define('DB_PASS', '');   // XAMPP default
```

### Step 5 — Hash Sample Passwords
Visit **http://localhost/cse_portal/setup_passwords.php** once, confirm ✅ green output, then **delete the file**.

### Step 6 — Open the Portal
**http://localhost/cse_portal/** → redirects to login.

---

## 4. DEMO CREDENTIALS

| Role | Email | Password |
|---|---|---|
| Admin | admin@cse.jkkniu.edu.bd | Admin@123 |
| Teacher | rafiqul@cse.jkkniu.edu.bd | Teacher@123 |
| Teacher | nasrin@cse.jkkniu.edu.bd | Teacher@123 |
| Student | karim@student.jkkniu.edu.bd | Student@123 |
| Student (pending demo) | nasima.pending@student.jkkniu.edu.bd | Student@123 |

The pending account demonstrates the **registration → admin approval** workflow — try logging in with it before approval (blocked), then approve it from **Admin → Approvals**.

---

## 5. KEY WORKFLOWS IMPLEMENTED

- **Registration:** 2-step form (info+photo → password) → `status='pending'` → Admin reviews/edits/approves/rejects in **Approvals** → notification sent.
- **Profile updates:** Password changes are instant (no approval). Name/phone/designation/photo changes create a `profile_requests` row and wait for admin approval.
- **Course assignment:** Admin manually links Course + Batch + Teacher in `course_assignments`. The `assign()` method in `CourseModel` checks for an existing row and **refuses to re-assign** — locked permanently once created, per spec.
- **Routine:** Admin uploads master PDF/image (`routine_files`, batch-specific or global). The batch's CR (flagged via `students.is_cr`) creates structured day/time/course entries (`routines`) — visible instantly, no approval.
- **Results:** Teachers enter Attendance + Mid-1/2/3 per student per assigned course; `total` auto-sums (no grade, per spec). Admin separately uploads official batch-wide final result files (PDF/image).
- **Messaging:** Direct PDO-backed private threads, restricted to allowed pairs only (Student↔Teacher, Student↔Admin, Teacher↔Admin) — enforced in each role's `messages.php` by only listing permitted contacts.
- **Discussions:** Course Q&A scoped to a student's own batch + the course's actual teacher; replies stored separately in `discussion_replies`.
- **Notifications:** Simple list, no read/unread state, sorted by recency — exactly as scoped.
- **Global Search:** Admin-only page querying students/teachers/courses/notices/resources in one form.
- **File uploads:** Centralized in `FileUpload.php` — extension + MIME-type whitelist, 20MB cap, randomized filenames, per-feature subfolder under `/uploads/`.

---

## 6. SECURITY

- PDO prepared statements everywhere (zero raw string interpolation in SQL).
- `password_hash()` / `password_verify()` (bcrypt).
- Session-based auth; `session_regenerate_id(true)` on login.
- `Auth::requireRole()` guards on all 35 admin/teacher/student pages (verified programmatically — zero gaps).
- All output passed through `htmlspecialchars()`.
- File upload validated by extension **and** real MIME sniffing (`finfo`), not just filename.
- Course assignments and result entries are ownership-checked (`isOwner()`) before allowing edit/delete.

---

## 7. TROUBLESHOOTING

| Problem | Fix |
|---|---|
| DB connection failed | Check MySQL is running; verify `DB_PASS` in `config/config.php` |
| 404 on every page | Project must sit at `htdocs/cse_portal/` exactly |
| Login fails with correct password | Run `setup_passwords.php`, then delete it |
| File upload fails | Check `uploads/` subfolders are writable (`chmod -R 755 uploads`) |
| Blank page / fatal error | Check `error_reporting` is on in `config/config.php` (already enabled by default) |

---

*CSE Department Portal — JKKNIU — 3rd Year Software Development Project*
