CREATE DATABASE skillmap;
USE skillmap;

-- ==========================
-- 1. USERS TABLE
-- ==========================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    edu_level VARCHAR(100),
    experience_level ENUM('Fresher', 'Junior', 'Mid', 'Senior') DEFAULT 'Fresher',
    preferred_track VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- 2. USER_SKILLS TABLE
-- ==========================
CREATE TABLE user_skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==========================
-- 3. USER_EXPERIENCES TABLE
-- ==========================
CREATE TABLE user_experiences (
    exp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exp_title VARCHAR(150) NOT NULL,
    description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==========================
-- 4. USER_CV TABLE
-- ==========================
CREATE TABLE user_cv (
    cv_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cv_text LONGTEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ==========================
-- 5. JOBS TABLE
-- ==========================
CREATE TABLE jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    job_title VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    location VARCHAR(100) DEFAULT 'Remote',
    required_skills TEXT,
    exp_level ENUM('Fresher', 'Junior', 'Mid', 'Senior') DEFAULT 'Fresher',
    job_type ENUM('Internship', 'Part-time', 'Full-time', 'Freelance') DEFAULT 'Full-time',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- 6. COURSES TABLE
-- ==========================
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_title VARCHAR(150) NOT NULL,
    platform VARCHAR(100),
    course_url VARCHAR(255),
    related_skills TEXT,
    cost_type ENUM('Free', 'Paid') DEFAULT 'Free'
);
