-- init.sql for OopsyHealth vulnerable lab (English usernames, bcrypt password hashes)

DROP DATABASE IF EXISTS oopsy_db;

CREATE DATABASE oopsy_db;
USE oopsy_db;

-- users table (passwords stored using bcrypt)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  role ENUM('patient','pharmacist','doctor','admin') NOT NULL DEFAULT 'patient',
  phone VARCHAR(30),
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- password_resets intentionally does NOT record the email/user association (vulnerability)
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  otp CHAR(3),
  expires_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- reports table for practicing IDOR (owner_id links to users.id)
CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL,
  title VARCHAR(255),
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert initial users (bcrypt hashes)

-- patientpass
-- sdhGSK7299kb@#$$jsljdlsj
-- ndoasyr0qHHEQ3Y0
-- mlsAklNLCSyskSd62klG
INSERT INTO users (username, email, role, phone, password_hash) VALUES ('alice.smith', 'alice.smith@oopsyhealth.com', 'patient', '+441234567890', '$2b$12$I3l.gEJFDUdO97acELYQpu8luFtgaJO0jFEfbfS5Xh9koDVSfpg9G');
INSERT INTO users (username, email, role, phone, password_hash) VALUES ('bob.jones', 'bob.jones@oopsyhealth.com', 'patient', '+441234567891', '$2b$12$ee/2HFhmU3KU9w08BypaGOzKpsw43f/nuSDeTVE/ILBSqgjnPSpgW');
INSERT INTO users (username, email, role, phone, password_hash) VALUES ('carla.miller', 'carla.miller@oopsyhealth.com', 'pharmacist', '+441234567892', '$2b$12$vNoXYObSU7sFkAl1Kczfpe/El0v6wSsF.jGFVAaB7S4FgJq74WQqm');
INSERT INTO users (username, email, role, phone, password_hash) VALUES ('david.bennett', 'david.bennett@oopsyhealth.com', 'doctor', '+441234567893', '$2b$12$01kXUmlszBejl6HfC5W30e0jyPvJAG9enLalceQM7hKNh1nz8hAcK');

-- Sample reports for patients (IDs will match users inserted above assuming auto-increment starts at 1)
INSERT INTO reports (owner_id, title, content) VALUES
  (1, 'Blood Test - March 2025', 'Hemoglobin: 13.5 g/dL\nNotes: Normal'),
  (1, 'Prescription - April 2025', 'Amoxicillin 500mg, take one every 8 hours for 7 days'),
  (2, 'Lab Results - June 2025', 'Cholesterol: 210 mg/dL\nNotes: Borderline high');

-- emails table: simulated mailbox for the lab
CREATE TABLE IF NOT EXISTS emails (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert some welcome emails (for patients) and a password reset message containing the token + OTP
INSERT INTO emails (to_email, subject, body) VALUES
  ('alice.smith@oopsyhealth.com', 'Welcome to OopsyHealth', 'Hello Alice,\n\nWelcome to OopsyHealth. \n\nRegards,\nOopsyHealth Team'),
  ('bob.jones@oopsyhealth.com', 'Welcome to OopsyHealth', 'Hello Bob,\n\nWelcome to OopsyHealth. \n\nRegards,\nOopsyHealth Team');