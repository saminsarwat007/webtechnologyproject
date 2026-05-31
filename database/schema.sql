-- CareerBridge — Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- Run:  mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS careerbridge
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE careerbridge;

-- Drop in dependency order so re-running the script is safe
DROP TABLE IF EXISTS post_likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS mock_interviews;
DROP TABLE IF EXISTS interview_slots;
DrOP TABLE IF EXISTS labels;


CREATE TABLE careerbridge.users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE careerbridge.companies (
  company_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  industry VARCHAR(100) NOT NULL,
  location VARCHAR(150) NOT NULL,
  description TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE careerbridge.jobs (
  job_id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  posted_by INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  type ENUM('internship','fulltime','parttime') NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT,
  deadline DATE NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(company_id),
  FOREIGN KEY (posted_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE careerbridge.student_profiles (
  profile_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  matric_no VARCHAR(20) NOT NULL UNIQUE,
  programme VARCHAR(100) NOT NULL,
  cgpa DECIMAL(3,2),
  skills TEXT,
  resume_text TEXT,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE careerbridge.applications (
  application_id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  user_id INT NOT NULL,
  cover_letter TEXT,
  status ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(job_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  UNIQUE KEY unique_application (job_id, user_id)
) ENGINE=InnoDB;

-- labels
CREATE TABLE labels (
  label_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;
-- ------------------------------------------------------------------
-- M7 — Forum & Discussion  (Owner: Monika)
-- ------------------------------------------------------------------
CREATE TABLE careerbridge.posts (
  post_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  label_id INT NULL,
  title VARCHAR(150) NOT NULL,
  content TEXT NOT NULL,
  likes INT NOT NULL DEFAULT 0,
  is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(user_id),
  FOREIGN KEY (label_id) REFERENCES labels(label_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE careerbridge.comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Per-user post likes (supports toggle behaviour)
CREATE TABLE careerbridge.post_likes (
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE careerbridge.interview_slots (
  slot_id INT AUTO_INCREMENT PRIMARY KEY,
  interviewer_id INT,
  scheduled_at DATETIME NOT NULL,
  is_booked BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (interviewer_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE careerbridge.mock_interviews (
  interview_id INT AUTO_INCREMENT PRIMARY KEY,
  slot_id INT,
  student_id INT,
  job_category VARCHAR(100) NOT NULL,
  status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  feedback_text TEXT,
  score INT,
  FOREIGN KEY (slot_id) REFERENCES interview_slots(slot_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(user_id)
) ENGINE=InnoDB;