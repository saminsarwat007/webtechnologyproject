-- CareerBridge — Seed Data
-- Run AFTER schema.sql:  mysql -u root -p careerbridge < database/seed.sql
--
-- Default password for ALL seeded accounts: Password123!
-- Hashes below were generated with PHP password_hash($p, PASSWORD_BCRYPT)
-- and verified with password_verify(). Cost 12.

USE careerbridge;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE post_likes;
TRUNCATE TABLE comments;
TRUNCATE TABLE posts;
TRUNCATE TABLE mock_interviews;
TRUNCATE TABLE interview_slots;
TRUNCATE TABLE applications;
TRUNCATE TABLE student_profiles;
TRUNCATE TABLE jobs;
TRUNCATE TABLE companies;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------------
INSERT INTO users (user_id, full_name, email, password_hash, role) VALUES
  (1, 'Super Admin',     'superadmin@careerbridge.my', '$2y$12$mg75/r06tuoU815LYDOkBezqJOagdTrtVEG9SFKoY85RzBgp09p9u', 'superadmin'),
  (2, 'Ahmad Farizal',   'farizal@careerbridge.my',    '$2y$12$OMd1zwyhqTDneE83UgF2LOpQ/V0Kbo3gPTrnmRjkqrX2ZVg6Vgova', 'admin'),
  (3, 'Mohammad Areeb',  'areeb@student.utm.my',       '$2y$12$SBB7PBAPZH0dyTenZ3e9EOho8ARg.0Of7KYDt2t1ryHq2frTIIBXK', 'student'),
  (4, 'Samin Sarwat',    'samin@student.utm.my',       '$2y$12$.J239s4UIMESJ5V547NKUO3qUP6DNU.YKVNcKDsOrpIvejxmT.TRu', 'student');

-- ------------------------------------------------------------------
-- COMPANIES
-- ------------------------------------------------------------------
INSERT INTO companies (company_id, name, industry, location, description, created_by) VALUES
  (1, 'TechCorp Sdn Bhd', 'Software Development',  'Kuala Lumpur', 'A leading Malaysian software house delivering enterprise web and mobile solutions across Southeast Asia.', 2),
  (2, 'Axiata Digital',   'Telecommunications',    'Kuala Lumpur', 'Digital arm of Axiata Group focusing on fintech, advertising, and digital platforms across emerging Asian markets.', 2),
  (3, 'Petronas ICT',     'Oil & Gas Technology',  'Kuala Lumpur', 'Information and communications technology subsidiary of Petronas, building digital solutions for the energy industry.', 1);

-- ==================================================================
-- SAMIN — M2: JOBS (10 records — mix of internship / fulltime / parttime)
-- Deadlines are dynamic (90+ days from now) so they remain "future" on any seed run.
-- ==================================================================
INSERT INTO jobs (company_id, posted_by, title, type, description, requirements, deadline, is_active) VALUES
  (1, 2, 'Software Engineering Intern',    'internship', 'Join our core engineering team to ship features for our SaaS product. You will pair with senior engineers, write production code, and participate in sprint planning.',                                              'Final-year CS/SE student. PHP or Node.js. Git. Familiar with REST APIs.',                  DATE_ADD(CURDATE(), INTERVAL 90 DAY),  TRUE),
  (2, 2, 'Frontend Developer',             'fulltime',   'Build polished, performant Vue.js interfaces for Axiata Digital products serving millions of users across the region.',                                                                                                  'Bachelor in CS/IT. 1+ year Vue.js or React. Strong CSS / Tailwind. Good communication.',     DATE_ADD(CURDATE(), INTERVAL 100 DAY), TRUE),
  (3, 1, 'Backend Engineer Intern',        'internship', 'Develop and maintain backend microservices that power Petronas ICT internal platforms. Exposure to high-availability systems and cloud infrastructure.',                                                            'Final-year student. PHP / Python / Go. SQL. Understands HTTP fundamentals.',                 DATE_ADD(CURDATE(), INTERVAL 95 DAY),  TRUE),
  (1, 2, 'Mobile App Developer',           'fulltime',   'Design and build cross-platform mobile applications using Flutter for our enterprise clients in finance and retail.',                                                                                                 'Bachelor in CS/SE. 2+ years mobile development. Flutter or React Native. App Store / Play Store experience.', DATE_ADD(CURDATE(), INTERVAL 110 DAY), TRUE),
  (2, 2, 'Data Analyst Intern',            'internship', 'Work with the analytics team to surface insights from billions of telco events. Build dashboards, run experiments, and present findings to stakeholders.',                                                          'Statistics / CS background. SQL. Python (pandas). Tableau or Power BI a plus.',              DATE_ADD(CURDATE(), INTERVAL 92 DAY),  TRUE),
  (3, 1, 'DevOps Engineer',                'fulltime',   'Own the CI/CD, observability, and Kubernetes platform serving Petronas ICT engineering teams. Drive reliability and reduce deploy time.',                                                                          'Bachelor in CS. 2+ years DevOps. Kubernetes, Terraform, Prometheus. AWS or Azure.',          DATE_ADD(CURDATE(), INTERVAL 105 DAY), TRUE),
  (1, 2, 'UI/UX Designer Intern',          'internship', 'Collaborate with product and engineering on user research, wireframes, and high-fidelity Figma designs for our web and mobile products.',                                                                            'Design student. Portfolio with Figma work. Understanding of design systems.',                DATE_ADD(CURDATE(), INTERVAL 88 DAY),  TRUE),
  (2, 2, 'Cybersecurity Analyst',          'fulltime',   'Monitor, investigate, and respond to security events across Axiata Digital infrastructure. Conduct vulnerability assessments and incident response.',                                                              'Bachelor in CS / Cybersecurity. CompTIA Security+ or equivalent. SIEM tools. Network fundamentals.', DATE_ADD(CURDATE(), INTERVAL 115 DAY), TRUE),
  (3, 1, 'Database Administrator Intern',  'internship', 'Assist the DBA team in tuning, backup, and replication for production Oracle and PostgreSQL clusters supporting upstream operations.',                                                                              'CS/IT student. SQL fluency. Familiarity with at least one RDBMS. Linux command line.',       DATE_ADD(CURDATE(), INTERVAL 93 DAY),  TRUE),
  (1, 2, 'Project Manager',                'fulltime',   'Lead delivery of multi-team projects for our top enterprise clients. Manage scope, schedule, and stakeholder communication end-to-end.',                                                                            'Bachelors degree. 3+ years PM experience in software. PMP or Scrum certification preferred.', DATE_ADD(CURDATE(), INTERVAL 120 DAY), TRUE);

-- ------------------------------------------------------------------
-- STUDENT PROFILES (linked to the 2 student users)
-- ------------------------------------------------------------------
INSERT INTO student_profiles (user_id, matric_no, programme, cgpa, skills, resume_text) VALUES
  (3, 'A22EC4041', 'Bachelor of Computer Science (Software Engineering)', 3.65,
   'PHP, Laravel, Vue.js, MySQL, Git, REST APIs, Docker',
   'Final-year SE student at UTM. Strong backend foundation with Laravel and Slim. Built two full-stack academic projects deployed on shared hosting. Active in IEEE student chapter.'),
  (4, 'A22EC4040', 'Bachelor of Computer Science (Software Engineering)', 3.78,
   'JavaScript, Vue 3, Tailwind CSS, Node.js, Figma, Git',
   'Frontend-focused SE student at UTM. Comfortable with component-driven design and modern build tooling. Internship-ready and seeking exposure to product engineering teams.');

-- ==================================================================
-- SAMIN — M3: APPLICATIONS (5 records — mix of statuses)
-- ==================================================================
INSERT INTO applications (job_id, user_id, cover_letter, status) VALUES
  (1, 3, 'I am very interested in the Software Engineering Intern position. My final-year project gave me strong exposure to PHP and REST API design, and I would love to contribute to TechCorp.', 'pending'),
  (3, 3, 'Backend systems are my strongest area. I have built REST services with Slim and Laravel and am eager to work on production-grade infrastructure at Petronas ICT.',                          'reviewed'),
  (5, 4, 'Data analytics aligns with my coursework on statistics and Python. I have shipped pandas-based reports for student-life dashboards at UTM.',                                                'accepted'),
  (7, 4, 'I have been building Figma prototypes for the past two semesters and would love to apply that to TechCorp products as a UI/UX intern.',                                                     'rejected'),
  (9, 3, 'Strong SQL background and curious about database internals. Looking forward to learning from the Petronas DBA team.',                                                                       'pending');

-- ------------------------------------------------------------------
-- POSTS  (M7 — Monika)  — tag is a simple free-text string.
-- ------------------------------------------------------------------
INSERT INTO posts (post_id, user_id, title, content, tag) VALUES
  (1, 3, 'How do you prepare for technical interviews at Malaysian tech companies?',
     'I have an interview lined up next month with TechCorp. Has anyone here done a system design or coding interview with them? Any tips on what to focus on — algorithms, system design, or behavioural rounds?', 'Interview Tips'),
  (2, 4, 'Resume tips for fresh graduates with no work experience',
     'My CV currently only lists my university projects and some MOOCs. How do I make it stand out for software engineering internships? Should I include side projects, hackathons, or open-source contributions?', 'Resume Advice'),
  (3, 3, 'My summer internship at Petronas ICT — AMA',
     'Just finished a 10-week backend internship at Petronas ICT. Happy to answer questions about the application process, the team, the work, and what to expect. AMA!', 'Internship Stories'),
  (4, 4, 'What is a fair starting salary for a fresh SE graduate in KL?',
     'Recruiters keep asking my expected salary and I have no idea what is fair for an entry-level software engineer in Kuala Lumpur in 2026. Would love to hear what offers you have seen or received.', 'Salary & Offers'),
  (5, 3, 'Axiata Digital — is it worth applying as a frontend developer?',
     'Looking at the Frontend Developer posting at Axiata Digital. Anyone here worked there or knows someone who has? Curious about culture, tech stack, and growth opportunities.', 'Companies');

-- ------------------------------------------------------------------
-- COMMENTS  (M7 — Monika)
-- ------------------------------------------------------------------
INSERT INTO comments (post_id, user_id, content) VALUES
  (1, 4, 'I interviewed with TechCorp last year — expect a coding round on data structures plus one system design question. Behavioural was pretty standard.'),
  (1, 3, 'Thanks! Did they ask LeetCode-style questions or more practical ones?'),
  (2, 3, 'Include your final-year project at the top and add metrics (users, perf gains, etc.) where you can. Recruiters skim — make every line count.'),
  (3, 4, 'How did you find the application process? Was it competitive?'),
  (3, 3, 'Very competitive — about 200 applicants for 15 spots. The technical screen filters most out.'),
  (4, 3, 'From what I have heard, fresh grad SE in KL is RM 3,500 – RM 5,500 depending on the company. MNCs pay higher.');

-- ------------------------------------------------------------------
-- POST LIKES  (so the like counters above are consistent)
-- ------------------------------------------------------------------
INSERT INTO post_likes (post_id, user_id) VALUES
  (1, 2), (1, 4), (1, 1),
  (2, 1), (2, 2), (2, 3), (2, 4),
  (3, 1), (3, 2), (3, 4),
  (4, 1), (4, 2),
  (5, 4);

-- ------------------------------------------------------------------
-- INTERVIEW SLOTS  (M8 — Monika)
-- All scheduled at +N days from today so they remain "future" on any seed run.
-- Slots 1-3 are unbooked, slot 4 is booked (slot.is_booked = TRUE).
-- ------------------------------------------------------------------
INSERT INTO interview_slots (slot_id, interviewer_id, scheduled_at, is_booked) VALUES
  (1, 2, DATE_ADD(NOW(), INTERVAL  7 DAY), FALSE),
  (2, 2, DATE_ADD(NOW(), INTERVAL 10 DAY), FALSE),
  (3, 1, DATE_ADD(NOW(), INTERVAL 14 DAY), FALSE),
  (4, 2, DATE_ADD(NOW(), INTERVAL  3 DAY), TRUE),
  (5, 1, DATE_ADD(NOW(), INTERVAL 21 DAY), FALSE);

-- ------------------------------------------------------------------
-- MOCK INTERVIEWS  (M8 — Monika)
-- One pending booking (slot 4) and one already-completed historical record
-- so the My Sessions page has data for the seeded student.
-- ------------------------------------------------------------------
INSERT INTO mock_interviews (slot_id, student_id, job_category, status, feedback_text, score) VALUES
  (4, 3, 'Software Engineering', 'pending',   NULL, NULL);
