CREATE DATABASE IF NOT EXISTS student_fees_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_fees_management;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_semesters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    semester_name VARCHAR(80) NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_semester_course FOREIGN KEY (course_id) REFERENCES courses(id),
    CONSTRAINT uq_course_semester UNIQUE (course_id, semester_name),
    CONSTRAINT chk_semester_fee CHECK (fee_amount > 0),
    INDEX idx_semesters_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    roll_number VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    course_semester_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_student_semester FOREIGN KEY (course_semester_id) REFERENCES course_semesters(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_fees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_semester_id BIGINT UNSIGNED NOT NULL,
    course_name VARCHAR(140) NOT NULL,
    semester_name VARCHAR(80) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fee_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_fee_semester FOREIGN KEY (course_semester_id) REFERENCES course_semesters(id),
    CONSTRAINT chk_fee_amount CHECK (amount > 0),
    INDEX idx_fees_user (user_id, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_fee_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bank_transfer', 'cash', 'other') NOT NULL,
    reference VARCHAR(120) NULL,
    receipt_number VARCHAR(32) NOT NULL UNIQUE,
    status ENUM('pending', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_fee FOREIGN KEY (student_fee_id) REFERENCES student_fees(id),
    CONSTRAINT fk_payment_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    INDEX idx_payments_status_date (status, created_at),
    INDEX idx_payments_fee_status (student_fee_id, status)
) ENGINE=InnoDB;

INSERT INTO courses (name, code) VALUES
('Bachelor of Computer Applications', 'BCA'),
('Bachelor of Business Administration', 'BBA')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO course_semesters (course_id, semester_name, fee_amount)
SELECT c.id, seed.semester_name, seed.fee_amount
FROM (
    SELECT 'BCA' AS course_code, 'Semester 1' AS semester_name, 1800.00 AS fee_amount
    UNION ALL SELECT 'BCA', 'Semester 2', 1800.00
    UNION ALL SELECT 'BBA', 'Semester 1', 1600.00
    UNION ALL SELECT 'BBA', 'Semester 2', 1600.00
) AS seed
JOIN courses c ON c.code = seed.course_code
ON DUPLICATE KEY UPDATE fee_amount = VALUES(fee_amount);
