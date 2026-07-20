CREATE DATABASE IF NOT EXISTS db_ujian_smanda 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE db_ujian_smanda;

-- 1. Tabel Pengguna (Super Admin & Guru)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('superadmin', 'guru') NOT NULL DEFAULT 'guru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert Akun Default Super Admin & Guru Pertama
-- Password default superadmin: super123 (di-hash)
-- Password default guru: admin123 (di-hash)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('superadmin', '$2y$10$4vP.C8YQn1Kk0/g3yBfK/eF5A0vD5fK/R8L5H0uV7z.x9T5V.3N4O', 'Super Administrator', 'superadmin'),
('guru1', '$2y$10$7vM.E8ZQm2Kk1/h4yBfL/fF6A1vE6fL/S9M6I1uW8z.y0U6W.4O5P', 'Pak Dasril Amri', 'guru');

-- 2. Tabel Paket Ujian
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    grade_level ENUM('X', 'XI', 'XII') NOT NULL DEFAULT 'X',
    url TEXT NOT NULL,
    passcode VARCHAR(100) NOT NULL,
    storage_key VARCHAR(100) NOT NULL UNIQUE,
    violation_limit INT NOT NULL DEFAULT 2,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    block_ctx TINYINT(1) NOT NULL DEFAULT 1,
    block_dev TINYINT(1) NOT NULL DEFAULT 1,
    force_fs TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Tabel Log Pelanggaran & Status Kunci Siswa
CREATE TABLE IF NOT EXISTS student_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    storage_key VARCHAR(100) NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 1,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (storage_key) REFERENCES exams(storage_key) ON DELETE CASCADE
) ENGINE=InnoDB;