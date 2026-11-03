-- =============================================================
--  IronCore Gym Management System — database schema
--  MySQL 8 / MariaDB 10.4+ (works with XAMPP)
-- =============================================================
CREATE DATABASE IF NOT EXISTS gym_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gym_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance, payments, appointments, bookings, classes, subscriptions, plans, members, trainers, users;
SET FOREIGN_KEY_CHECKS = 1;

-- Every person who can log in (admin, trainer or member).
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'trainer', 'member') NOT NULL DEFAULT 'member',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE trainers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL UNIQUE,
    specialization   VARCHAR(100),
    experience_years TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bio              TEXT,
    CONSTRAINT fk_trainer_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE members (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    gender            ENUM('male', 'female'),
    date_of_birth     DATE,
    address           VARCHAR(255),
    emergency_contact VARCHAR(100),
    join_date         DATE NOT NULL,
    status            ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_member_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE plans (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(60) NOT NULL,
    duration_months TINYINT UNSIGNED NOT NULL,
    price           DECIMAL(10, 2) NOT NULL,
    description     VARCHAR(255),
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_plan_price CHECK (price > 0),
    CONSTRAINT chk_plan_duration CHECK (duration_months > 0)
) ENGINE = InnoDB;

CREATE TABLE subscriptions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    member_id  INT NOT NULL,
    plan_id    INT NOT NULL,
    start_date DATE NOT NULL,
    end_date   DATE NOT NULL,
    status     ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans (id),
    CONSTRAINT chk_sub_dates CHECK (end_date >= start_date),
    INDEX idx_sub_status_end (status, end_date)
) ENGINE = InnoDB;

-- Weekly recurring group classes.
CREATE TABLE classes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(80) NOT NULL,
    trainer_id   INT NOT NULL,
    day_of_week  ENUM('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') NOT NULL,
    start_time   TIME NOT NULL,
    duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    capacity     SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    room         VARCHAR(40),
    CONSTRAINT fk_class_trainer FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- A member's seat in one session (date) of a class.
CREATE TABLE bookings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    class_id   INT NOT NULL,
    member_id  INT NOT NULL,
    class_date DATE NOT NULL,
    status     ENUM('booked', 'cancelled', 'attended') NOT NULL DEFAULT 'booked',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_class FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT uq_booking UNIQUE (class_id, member_id, class_date)
) ENGINE = InnoDB;

-- One-to-one personal training sessions.
CREATE TABLE appointments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    member_id      INT NOT NULL,
    trainer_id     INT NOT NULL,
    appointment_at DATETIME NOT NULL,
    notes          VARCHAR(255),
    status         ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_appt_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_appt_trainer FOREIGN KEY (trainer_id) REFERENCES trainers (id) ON DELETE CASCADE,
    INDEX idx_appt_trainer_time (trainer_id, appointment_at)
) ENGINE = InnoDB;

-- Payments are recorded, not processed (cash at the desk or online transfer with a reference).
CREATE TABLE payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    member_id       INT NOT NULL,
    subscription_id INT NULL,
    amount          DECIMAL(10, 2) NOT NULL,
    method          ENUM('cash', 'online') NOT NULL,
    reference       VARCHAR(60),
    paid_at         DATETIME NOT NULL,
    recorded_by     INT NULL,
    CONSTRAINT fk_pay_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL,
    CONSTRAINT fk_pay_user FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_pay_amount CHECK (amount > 0),
    INDEX idx_pay_date (paid_at)
) ENGINE = InnoDB;

CREATE TABLE attendance (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    check_in  DATETIME NOT NULL,
    check_out DATETIME NULL,
    CONSTRAINT fk_att_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    INDEX idx_att_checkin (check_in)
) ENGINE = InnoDB;

-- Handy reporting view used in the docs.
CREATE OR REPLACE VIEW v_active_members AS
SELECT m.id AS member_id, u.name, u.email, p.name AS plan, s.start_date, s.end_date,
       DATEDIFF(s.end_date, CURDATE()) AS days_left
FROM members m
JOIN users u ON u.id = m.user_id
JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
JOIN plans p ON p.id = s.plan_id;
