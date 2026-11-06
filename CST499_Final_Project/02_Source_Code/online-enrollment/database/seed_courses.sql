-- Fictional course catalog for local demonstration, not an official UAGC schedule.
USE online_enrollment;
INSERT INTO courses (id,code,title,credits) VALUES
 (1,'CST499','Software Technology Capstone',3),
 (2,'CST310','Database Systems',3),
 (3,'CST301','Software Engineering',3),
 (4,'CST320','Web Application Development',3)
ON DUPLICATE KEY UPDATE title=VALUES(title),credits=VALUES(credits);
INSERT INTO semesters (id,year,term) VALUES (1,2026,'Fall'),(2,2027,'Spring'),(3,2027,'Summer')
ON DUPLICATE KEY UPDATE year=VALUES(year),term=VALUES(term);
INSERT INTO offerings (id,course_id,semester_id,section,capacity) VALUES
 (1,1,1,'01',2),(2,2,1,'01',3),(3,3,1,'01',1),(4,4,2,'01',3)
ON DUPLICATE KEY UPDATE section=VALUES(section);
