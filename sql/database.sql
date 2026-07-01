-- ============================================================
-- CSE Department Portal — Full Database Schema
-- Department of CSE, Jatiya Kabi Kazi Nazrul Islam University
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS cse_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cse_portal;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. users — core authentication table (all roles)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') NOT NULL,
    status ENUM('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
    profile_photo VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. students
-- ============================================================
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    roll VARCHAR(30) NOT NULL UNIQUE,
    registration_no VARCHAR(50) NOT NULL UNIQUE,
    batch_id INT DEFAULT NULL,
    session VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_cr TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3. teachers
-- ============================================================
CREATE TABLE teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. batches
-- ============================================================
CREATE TABLE batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(50) NOT NULL,
    session VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE students
    ADD CONSTRAINT fk_students_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE SET NULL;

-- ============================================================
-- 5. courses
-- ============================================================
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_title VARCHAR(200) NOT NULL,
    credit DECIMAL(3,1) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 6. course_assignments — Course + Batch + Teacher (locked once set)
-- ============================================================
CREATE TABLE course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    batch_id INT NOT NULL,
    teacher_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_assignment (course_id, batch_id),
    CONSTRAINT fk_ca_course  FOREIGN KEY (course_id)  REFERENCES courses(course_id)   ON DELETE CASCADE,
    CONSTRAINT fk_ca_batch   FOREIGN KEY (batch_id)   REFERENCES batches(batch_id)    ON DELETE CASCADE,
    CONSTRAINT fk_ca_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. notices
-- ============================================================
CREATE TABLE notices (
    notice_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    target ENUM('all_students','specific_batch') NOT NULL DEFAULT 'all_students',
    batch_id INT DEFAULT NULL,
    posted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notices_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    CONSTRAINT fk_notices_user  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. notice_files
-- ============================================================
CREATE TABLE notice_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    CONSTRAINT fk_nf_notice FOREIGN KEY (notice_id) REFERENCES notices(notice_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. routines — structured entries created by CR
-- ============================================================
CREATE TABLE routines (
    routine_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    date DATE DEFAULT NULL,
    day ENUM('Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    course VARCHAR(200) NOT NULL,
    teacher VARCHAR(100) DEFAULT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_routines_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    CONSTRAINT fk_routines_user  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. routine_files — Admin-uploaded PDF/image routines
-- ============================================================
CREATE TABLE routine_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT DEFAULT NULL,   -- NULL = applies to all batches
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rf_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id) ON DELETE CASCADE,
    CONSTRAINT fk_rf_user  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. exam_schedules
-- ============================================================
CREATE TABLE exam_schedules (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    course_id INT NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exam_batch  FOREIGN KEY (batch_id)  REFERENCES batches(batch_id)  ON DELETE CASCADE,
    CONSTRAINT fk_exam_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 12. results — teacher-entered marks (no grade, auto total)
-- ============================================================
CREATE TABLE results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance DECIMAL(5,2) DEFAULT 0,
    mid1 DECIMAL(5,2) DEFAULT 0,
    mid2 DECIMAL(5,2) DEFAULT 0,
    mid3 DECIMAL(5,2) DEFAULT 0,
    total DECIMAL(6,2) DEFAULT 0,
    entered_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_result (student_id, course_id),
    CONSTRAINT fk_results_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_results_course  FOREIGN KEY (course_id)  REFERENCES courses(course_id)   ON DELETE CASCADE,
    CONSTRAINT fk_results_user    FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 13. result_files — Admin final result (batch-wise) + Teacher course result files
-- ============================================================
CREATE TABLE result_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT DEFAULT NULL,
    course_id INT DEFAULT NULL,
    uploaded_by INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    title VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resf_batch  FOREIGN KEY (batch_id)  REFERENCES batches(batch_id)  ON DELETE CASCADE,
    CONSTRAINT fk_resf_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    CONSTRAINT fk_resf_user   FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 14. resources — teacher resource library
-- ============================================================
CREATE TABLE resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_course  FOREIGN KEY (course_id)  REFERENCES courses(course_id)   ON DELETE CASCADE,
    CONSTRAINT fk_res_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 15. discussion_groups + discussion_messages (WhatsApp-style group chat)
-- A group is created by a Teacher (for their assigned Course+Batch) or by
-- Admin (for a Course across ALL batches, batch_id = NULL). All students of
-- the batch(es) + the teacher(s) + admin can see and post in the group feed.
-- ============================================================
CREATE TABLE discussion_groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    batch_id INT DEFAULT NULL,   -- NULL = applies to ALL batches (admin-created only)
    created_by INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_group (course_id, batch_id),
    CONSTRAINT fk_dg_course  FOREIGN KEY (course_id)  REFERENCES courses(course_id) ON DELETE CASCADE,
    CONSTRAINT fk_dg_batch   FOREIGN KEY (batch_id)   REFERENCES batches(batch_id)  ON DELETE CASCADE,
    CONSTRAINT fk_dg_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE discussion_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dm_group FOREIGN KEY (group_id) REFERENCES discussion_groups(group_id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 16. messages + message_files — private messaging
-- ============================================================
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE message_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    CONSTRAINT fk_mf_message FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 17. notifications
-- ============================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT DEFAULT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 18. profile_requests — pending profile-info change approvals
-- ============================================================
CREATE TABLE profile_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requested_data TEXT NOT NULL,  -- JSON: {name, phone, designation, photo, ...}
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- settings — single-row portal configuration
-- ============================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portal_name VARCHAR(150) NOT NULL DEFAULT 'CSE Department Portal',
    university_name VARCHAR(200) NOT NULL DEFAULT 'Jatiya Kabi Kazi Nazrul Islam University',
    department_name VARCHAR(200) NOT NULL DEFAULT 'Department of Computer Science and Engineering',
    description TEXT DEFAULT NULL,
    contact_info TEXT DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Settings (single row)
INSERT INTO settings (portal_name, university_name, department_name, description, contact_info) VALUES
('CSE Department Portal', 'Jatiya Kabi Kazi Nazrul Islam University', 'Department of Computer Science and Engineering',
'The Department of Computer Science and Engineering at JKKNIU is committed to producing skilled graduates in software engineering, AI, and computing research.',
'Email: cse@jkkniu.edu.bd | Phone: +880-91-67401');

-- Users (password placeholder hash — run setup_passwords.php to set real ones)
-- Admin
INSERT INTO users (name, email, password, role, status) VALUES
('System Admin', 'admin@cse.jkkniu.edu.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Teachers
INSERT INTO users (name, email, password, role, status) VALUES
('Dr. Md. Rafiqul Islam', 'rafiqul@cse.jkkniu.edu.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
('Prof. Nasrin Akter',    'nasrin@cse.jkkniu.edu.bd',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active'),
('Md. Jahangir Alam',     'jahangir@cse.jkkniu.edu.bd','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'active');

-- Students (active)
INSERT INTO users (name, email, password, role, status) VALUES
('Karim Hossain', 'karim@student.jkkniu.edu.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('Rina Begum',    'rina@student.jkkniu.edu.bd',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('Sumon Ahmed',   'sumon@student.jkkniu.edu.bd',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active'),
('Tania Islam',   'tania@student.jkkniu.edu.bd',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

-- One pending student (demonstrates approval workflow)
INSERT INTO users (name, email, password, role, status) VALUES
('Nasima Khatun', 'nasima.pending@student.jkkniu.edu.bd', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'pending');

-- Teachers profile
INSERT INTO teachers (user_id, designation, phone) VALUES
(2, 'Professor', '01711-000001'),
(3, 'Associate Professor', '01711-000002'),
(4, 'Lecturer', '01711-000003');

-- Batches
INSERT INTO batches (batch_name, session) VALUES
('2018-19', '2018-2022'),
('2019-20', '2019-2023'),
('2020-21', '2020-2024'),
('2021-22', '2021-2025');

-- Students profile (batch_id 3 = "2020-21")
INSERT INTO students (user_id, roll, registration_no, batch_id, session, phone, is_cr) VALUES
(5, '2101001', 'REG-2021-001', 3, '2020-2024', '01712-000001', 1),
(6, '2101002', 'REG-2021-002', 3, '2020-2024', '01712-000002', 0),
(7, '2101003', 'REG-2021-003', 3, '2020-2024', '01712-000003', 0),
(8, '2101004', 'REG-2021-004', 3, '2020-2024', '01712-000004', 0);

-- Pending student has no student row yet (created only after admin approval)

-- Courses
INSERT INTO courses (course_code, course_title, credit, semester, status) VALUES
('CSE-301', 'Software Engineering', 3.0, '5th Semester', 'active'),
('CSE-302', 'Database Management Systems', 3.0, '5th Semester', 'active'),
('CSE-303', 'Computer Networks', 3.0, '5th Semester', 'active'),
('CSE-304', 'Algorithm Design & Analysis', 3.0, '5th Semester', 'active'),
('CSE-305', 'Artificial Intelligence', 3.0, '6th Semester', 'active'),
('CSE-306', 'Web Technology', 3.0, '6th Semester', 'active'),
('CSE-201', 'Data Structures', 3.0, '3rd Semester', 'active'),
('CSE-202', 'Object-Oriented Programming', 3.0, '3rd Semester', 'active');

-- Course Assignments (course + batch + teacher) — batch_id 3
INSERT INTO course_assignments (course_id, batch_id, teacher_id) VALUES
(1, 3, 1),  -- Software Engineering -> Dr. Rafiqul
(2, 3, 2),  -- DBMS -> Prof. Nasrin
(3, 3, 3),  -- Networks -> Jahangir
(4, 3, 1);  -- Algorithm Design -> Dr. Rafiqul

-- Notices
INSERT INTO notices (title, description, target, batch_id, posted_by) VALUES
('Welcome to CSE Department Portal', 'Dear students and faculty, welcome to the official CSE Department Portal of JKKNIU.', 'all_students', NULL, 1),
('Midterm Exam Schedule Released', 'The midterm exam schedule for 5th semester has been published. Check the exam schedule page.', 'specific_batch', 3, 1),
('Lab Report Submission Reminder', 'Batch 2020-21: submit your Software Engineering lab reports by end of this week.', 'specific_batch', 3, 2);

-- Routines (structured, created by CR — user_id 5 is the CR student)
INSERT INTO routines (batch_id, date, day, course, teacher, start_time, end_time, created_by) VALUES
(3, NULL, 'Saturday', 'Software Engineering (CSE-301)', 'Dr. Md. Rafiqul Islam', '08:00:00', '09:30:00', 5),
(3, NULL, 'Saturday', 'Database Management Systems (CSE-302)', 'Prof. Nasrin Akter', '09:30:00', '11:00:00', 5),
(3, NULL, 'Sunday',   'Computer Networks (CSE-303)', 'Md. Jahangir Alam', '08:00:00', '09:30:00', 5),
(3, NULL, 'Sunday',   'Algorithm Design (CSE-304)', 'Dr. Md. Rafiqul Islam', '09:30:00', '11:00:00', 5);

-- Exam Schedules
INSERT INTO exam_schedules (batch_id, course_id, exam_date, exam_time) VALUES
(3, 1, '2025-07-10', '09:00:00'),
(3, 2, '2025-07-12', '09:00:00'),
(3, 3, '2025-07-14', '09:00:00'),
(3, 4, '2025-07-16', '09:00:00');

-- Results (teacher-entered, total auto-calculated as attendance+mid1+mid2+mid3)
INSERT INTO results (student_id, course_id, attendance, mid1, mid2, mid3, total, entered_by) VALUES
(1, 1, 9, 18, 17, 19, 63, 2),
(1, 2, 8, 16, 18, 17, 59, 3),
(2, 1, 10, 19, 20, 18, 67, 2),
(2, 2, 9, 17, 19, 18, 63, 3),
(3, 1, 7, 14, 15, 13, 49, 2),
(4, 1, 6, 12, 11, 10, 39, 2);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- NOTE: Default password for ALL sample users is "password"
-- Run setup_passwords.php once after import to hash properly,
-- or use these per-role passwords if you prefer distinct ones:
--   Admin:   admin@cse.jkkniu.edu.bd   / Admin@123
--   Teacher: rafiqul@cse.jkkniu.edu.bd / Teacher@123
--   Student: karim@student.jkkniu.edu.bd / Student@123
-- ============================================================
