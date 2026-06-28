# 1. Open MySQL command line
mysql -u root

# 2. See all databases
SHOW DATABASES;

# 3. Use the CareerBridge database
USE careerbridge;

# 4. Show all tables
SHOW TABLES;

# 5. See the structure of my tables (M2 + M3)
DESCRIBE jobs;
DESCRIBE applications;

# 6. See the data inside my tables
SELECT * FROM jobs;
SELECT * FROM applications;

# 7. Show how my tables connect (jobs + companies + who posted)
SELECT j.job_id, j.title, j.type, c.name AS company, j.deadline, j.is_active
FROM jobs j
JOIN companies c ON c.company_id = j.company_id;

# 8. Show applications with job + student info
SELECT a.application_id, u.full_name AS student, j.title AS job, a.status, a.applied_at
FROM applications a
JOIN users u ON u.user_id = a.user_id
JOIN jobs j ON j.job_id = a.job_id;

# 9. Count applications by status (shows on admin dashboard chart)
SELECT status, COUNT(*) AS count FROM applications GROUP BY status;