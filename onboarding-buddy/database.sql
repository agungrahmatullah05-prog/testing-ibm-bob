-- Onboarding Buddy System Database Schema
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS onboarding_buddy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE onboarding_buddy;

-- Users table (roles: admin, buddy, new_hire)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'buddy', 'new_hire') NOT NULL DEFAULT 'new_hire',
    department VARCHAR(100),
    position VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    start_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buddy assignments table
CREATE TABLE IF NOT EXISTS buddy_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    new_hire_id INT NOT NULL,
    buddy_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed') DEFAULT 'active',
    FOREIGN KEY (new_hire_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buddy_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (new_hire_id)
);

-- Onboarding task templates
CREATE TABLE IF NOT EXISTS task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    day_target INT DEFAULT 1,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Onboarding progress (per new hire)
CREATE TABLE IF NOT EXISTS onboarding_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    new_hire_id INT NOT NULL,
    task_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'done') DEFAULT 'pending',
    notes TEXT,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (new_hire_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES task_templates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (new_hire_id, task_id)
);

-- Messages between new hire and buddy
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed: default admin
INSERT INTO users (name, email, password, role, department, position) VALUES
('Admin HR', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Human Resources', 'HR Manager');

-- Seed: sample buddies
INSERT INTO users (name, email, password, role, department, position, start_date) VALUES
('Andi Pratama', 'andi@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buddy', 'Engineering', 'Senior Developer', '2023-01-10'),
('Sari Wulandari', 'sari@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buddy', 'Marketing', 'Marketing Lead', '2022-06-15'),
('Budi Santoso', 'budi@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buddy', 'Finance', 'Finance Analyst', '2023-03-20');

-- Seed: default onboarding tasks
INSERT INTO task_templates (title, description, category, day_target) VALUES
('Perkenalan Tim', 'Berkenalan dengan seluruh anggota tim di departemen Anda', 'Sosial', 1),
('Setup Perangkat Kerja', 'Install semua software yang diperlukan dan konfigurasi akun', 'IT', 1),
('Baca Kebijakan Perusahaan', 'Pelajari handbook dan kebijakan HR perusahaan', 'Administrasi', 2),
('Tur Kantor', 'Tur gedung kantor bersama buddy Anda', 'Orientasi', 1),
('Meeting 1-on-1 dengan Manager', 'Jadwalkan dan laksanakan pertemuan pertama dengan atasan langsung', 'Manajerial', 3),
('Setup Email & Komunikasi', 'Konfigurasi email kantor, Slack/Teams, dan tools komunikasi lainnya', 'IT', 1),
('Pahami Alur Kerja Tim', 'Pelajari proses dan workflow tim Anda', 'Operasional', 5),
('Ikuti Pelatihan Wajib', 'Selesaikan semua modul pelatihan onboarding yang diwajibkan', 'Training', 7),
('Review Proyek Pertama', 'Diskusikan dan mulai kerjakan proyek onboarding pertama Anda', 'Pekerjaan', 14),
('Check-in 30 Hari', 'Evaluasi dan refleksi setelah 30 hari bergabung', 'Evaluasi', 30);

-- Default password for all seeded users: "password"
-- (hashed with bcrypt, cost 10)
