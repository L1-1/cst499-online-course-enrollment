-- Change the example password before running. Execute as a database administrator.
-- Separate localhost and TCP identities support common XAMPP/MySQL configurations.
CREATE USER IF NOT EXISTS 'enrollment_app'@'localhost' IDENTIFIED BY 'ChangeThisLocalPassword!';
GRANT SELECT,INSERT ON online_enrollment.accounts TO 'enrollment_app'@'localhost';
GRANT SELECT,INSERT,UPDATE ON online_enrollment.profiles TO 'enrollment_app'@'localhost';
GRANT SELECT ON online_enrollment.courses TO 'enrollment_app'@'localhost';
GRANT SELECT ON online_enrollment.semesters TO 'enrollment_app'@'localhost';
GRANT SELECT,UPDATE ON online_enrollment.offerings TO 'enrollment_app'@'localhost';
GRANT SELECT,INSERT,DELETE ON online_enrollment.enrollments TO 'enrollment_app'@'localhost';
GRANT SELECT,INSERT,DELETE ON online_enrollment.waiting_list TO 'enrollment_app'@'localhost';
GRANT SELECT,INSERT,UPDATE ON online_enrollment.seat_offers TO 'enrollment_app'@'localhost';
GRANT SELECT,INSERT ON online_enrollment.notification_tasks TO 'enrollment_app'@'localhost';
GRANT UPDATE(email) ON online_enrollment.accounts TO 'enrollment_app'@'localhost';

CREATE USER IF NOT EXISTS 'enrollment_app'@'127.0.0.1' IDENTIFIED BY 'ChangeThisLocalPassword!';
GRANT SELECT,INSERT ON online_enrollment.accounts TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,INSERT,UPDATE ON online_enrollment.profiles TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT ON online_enrollment.courses TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT ON online_enrollment.semesters TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,UPDATE ON online_enrollment.offerings TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,INSERT,DELETE ON online_enrollment.enrollments TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,INSERT,DELETE ON online_enrollment.waiting_list TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,INSERT,UPDATE ON online_enrollment.seat_offers TO 'enrollment_app'@'127.0.0.1';
GRANT SELECT,INSERT ON online_enrollment.notification_tasks TO 'enrollment_app'@'127.0.0.1';
GRANT UPDATE(email) ON online_enrollment.accounts TO 'enrollment_app'@'127.0.0.1';

-- UPDATE on offerings permits SELECT ... FOR UPDATE in the tested MySQL server.
-- No global privileges or DDL privileges are granted to the application.
