-- ==========================
-- OopsyHealth Seed Data (updated)
-- ==========================

-- Users
-- Passwords hashed with bcrypt for lab simulation
-- We'll use python to generate actual bcrypt hashes for deployment

INSERT INTO users (name, surname, username, email, password_hash, role, phone) VALUES
('Alice', 'Smith', 'alice.smith', 'alice.smith@oopsyhealth.com', '$2b$12$examplehash1', 'patient', '555-001'),
('Bob', 'Jones', 'bob.jones', 'bob.jones@oopsyhealth.com', '$2b$12$examplehash2', 'patient', '555-002'),
('Carol', 'Pharm', 'carol.pharm', 'carol.pharm@oopsyhealth.com', '$2b$12$examplehash3', 'pharmacist', '555-100'),
('Dan', 'Doctor', 'dan.doctor', 'dan.doctor@oopsyhealth.com', '$2b$12$examplehash4', 'doctor', '555-200');

-- Reports
INSERT INTO reports (owner_id, title, content) VALUES
(1, 'Blood Test', 'Hemoglobin: 14 g/dL\nWBC: 6.5 x10^9/L'),
(1, 'X-Ray', 'Chest X-Ray: normal'),
(2, 'Prescription', 'Take 1 tablet of Ibuprofen 200mg daily');

-- Uploaded files (empty initially)

-- Drugs
INSERT INTO drugs (name, stock) VALUES
('Aspirin', 42),
('Ibuprofen', 10),
('Amoxicillin', 5),
('Paracetamol', 20);

-- Messages (empty initially)

-- Inbox messages for patients
INSERT INTO inbox (owner_id, subject, content) VALUES
(1, 'Welcome to OopsyHealth', 'Dear Alice, welcome to OopsyHealth. Your account is ready.'),
(1, 'Lab Results Available', 'Your recent blood test is now available in your reports section.'),
(2, 'Welcome to OopsyHealth', 'Dear Bob, welcome to OopsyHealth. Your account is ready.');

-- Reset tokens / OTPs (empty initially)

-- Inbox messages for patients
INSERT INTO inbox (owner_id, subject, content) VALUES
(1, 'Welcome to OopsyHealth', 'Dear Alice, welcome to OopsyHealth. Your account is ready.'),
(1, 'Lab Results Available', 'Your recent blood test is now available in your reports section.'),
(2, 'Welcome to OopsyHealth', 'Dear Bob, welcome to OopsyHealth. Your account is ready.');
