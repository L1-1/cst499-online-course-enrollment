# Online Course Enrollment - Week 4

This extends the Week 3 PHP account system with semester course selection, registration, schedule listing, additional classes, confirmed removal, waiting lists, seat offers, and profile editing. The accounts/profiles schema is retained.

## Run locally

1. Use PHP 8.3 with pdo_mysql and mbstring, and MySQL 8.0.16 or later. Verification used PHP 8.3.6 and MySQL 8.0.46.
2. In XAMPP, start Apache and the database service, then copy this folder to `C:\xampp\htdocs\online-enrollment`. XAMPP normally distributes MariaDB; the evidence here was captured on MySQL. Recheck the migration in the database version you use.
3. For a new database, import `database/schema.sql` through phpMyAdmin. For an existing Week 3 database, retain its accounts/profiles and back up the database before applying the migration.
4. Import `database/week4.sql`, then `database/seed_courses.sql`. These scripts add tables and a fictional catalog without deleting existing accounts. Seed identifiers 1-4 are for this prototype's fresh catalog; review them before importing into a database with course data.
5. Change the example password in `database/create_app_user.example.sql`, then execute it as an administrator. This grants table-specific rights, including the offering locking permission needed in the tested MySQL version. No global or schema-changing privileges are assigned.
6. Copy `app/config.example.php` to `app/config.local.php` and enter matching database credentials. Runtime credentials are excluded from this archive.
7. Open `http://localhost/online-enrollment/public/`. Alternatively, run `php -S 127.0.0.1:4173 -t public` from the project folder and open `http://127.0.0.1:4173/`. PHP's development server is for local testing only.
8. Register through the interface, or run `php scripts/seed_demo.php`. It creates fictional users `alex`, `brooke`, and `casey`, each with password `DemoStudent2026!`, without changing any existing account with those IDs. These are local demonstration credentials only.

## Reproduce the required screenshots

Use a fresh demonstration database so enrollments begins empty. Capture phpMyAdmin itself as well as the student interface.

1. Select `online_enrollment` in phpMyAdmin and capture the database Structure tab. Open each table's Structure tab so its columns, data types, nullability, defaults, and keys are visible. For enrollments, also capture Relation view.
2. Capture the enrollments Browse tab before registration. Log in as Alex, choose Fall 2026, and register for CST499. Refresh Browse and capture the new student/offering row.
3. Use Add more courses to register for CST310. Capture the two-row table and two-class schedule. The preceding one-row capture is the before image for this addition.
4. Select Remove class for CST499, capture the confirmation page, then Confirm removal. Capture the table and refreshed schedule. CST310 must remain. The two-row capture is the before image for deletion.
5. For waiting-list evidence, Brooke registers for the one-seat CST301 offering; Alex joins its queue. Brooke removes CST301. Alex receives a reserved-seat notice and may accept or decline. Capture pending and terminal seat_offers states.

The included figures record this sequence with fictional accounts. Generated IDs may differ on another installation; compare the same student and offering across each operation.

## Package contents

- `app/EnrollmentService.php`: prepared queries, offering locks, registration, removal, queues, and offers.
- `public/`: existing account pages plus courses.php, schedule.php, drop.php, waiting.php, offer.php, profile.php, and the enroll.php POST handler.
- `database/`: base schema, additive migration, fictional catalog, and application permissions.
- `docs/week4-evidence/`: 34 actual captures with captions. Database images are phpMyAdmin views; code images display actual PHP excerpts with line numbers.
- `docs/WEEK4_RUBRIC_MAP.md`: requirement-to-evidence mapping and design refinements.
- `docs/TEST_RESULTS.md`, JSON results, and verification log: the 84-check run.
- `tests/`: authentication regression and isolated database integration test source.
- `scripts/expire_offers.php`: CLI housekeeping. An operator should schedule it once per minute for unattended expiration. Catalog and waiting-list requests also refresh offers.

## Test scope and reproduction

Run authentication regression checks with `php tests/http_smoke.php BASE_URL`. The 26 service assertions in `tests/week4_integration.php` require a disposable `online_enrollment_test` database created by importing the SQL scripts with that name. The recorded test configuration uses root with an empty password on an isolated, loopback-only database; adapt it to your dedicated test server. These tests inject a failing trigger and must never target important data.

The additional concurrency check starts two race_worker.php processes for student IDs 1 and 2, giving both the same future UNIX timestamp as their second argument, and verifies one enrollment in offering 100. This yields 27 database/concurrency checks. The 30 HTTP checks in the evidence run validate the application's actual credentials and exercise authorization, CSRF, add/remove, offer acceptance, and profile validation. JSON results and the console log record each check.

## Remaining release work

Notification tasks are stored, but no external email is sent. FR-11 delivery/retries, HTTPS deployment, login rate limiting, the 50-user/1,000-offering performance target, and stakeholder acceptance remain incomplete. The 24-hour offer policy requires approval. This local prototype is not a production deployment or a completed security audit.

Cancellation deletes active enrollment rows rather than retaining the cancelled-enrollment history from the conceptual model. Offer outcomes and recipient IDs remain recorded. No work has been submitted to the instructor automatically.
