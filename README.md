# Online Course Enrollment

A PHP and MySQL web application for student account registration, semester-based course enrollment, and waiting-list management.

**Author:** Louis Murphy  
**Course:** CST499: Capstone for Computer Software Technology  
**Institution:** The University of Arizona Global Campus  
**Instructor:** Charmelia Butler

## Project Overview

This capstone follows the software development lifecycle from requirements and UML modeling through implementation and verification. The application allows students to create accounts, browse semester offerings, register for available classes, manage their schedules, and join waiting lists for full offerings.

The repository includes the application source, database scripts, revised academic documents, implementation evidence, and final presentation. The current deliverable is a local academic prototype with documented verification results and remaining release work.

## Features

- Account registration with input validation and duplicate-identifier checks.
- Login, logout, password hashing, and session-based access control.
- Profile editing for the signed-in student.
- Course listings filtered by semester.
- Enrollment with capacity checks and duplicate-registration prevention.
- A personal schedule with a confirmation step before class removal.
- Waiting lists ordered by server timestamp and entry ID.
- In-app reserved-seat offers with acceptance, decline, and expiration.
- Persistent notification tasks for a future email worker.

The central enrollment rule is:

**Active enrollments + pending seat reservations must not exceed offering capacity.**

Payments, grading, course delivery, prerequisite enforcement, and an administrative interface are outside this prototype's scope. Staff populate offerings through the supplied database scripts.

## Technology

| Component | Implementation |
| --- | --- |
| Application | PHP with PDO and the MySQL driver |
| Database | MySQL with InnoDB tables |
| Interface | HTML and CSS rendered by PHP |
| Database inspection | phpMyAdmin |
| Local execution | Apache/XAMPP or PHP's development server |
| Design documentation | Software requirements specification and five UML models |

The recorded verification environment used PHP 8.3.6, MySQL 8.0.46, and phpMyAdmin 5.2.1 on Linux. The Windows/XAMPP instructions describe a local setup route; they do not represent a separate completed verification run. XAMPP commonly includes MariaDB, so check the supplied SQL scripts against the version installed locally.

Download the complete submission package: [CST499_Final_Project.zip](CST499_Final_Project.zip). The video is submitted separately in Canvas.

## Repository Contents

| Location | Contents |
| --- | --- |
| [01_Documents](01_Documents/) | Revised SRS, UML design, interface development, MySQL/class registration, PHP explanation, consolidated APA references, and project test plan |
| [02_Source_Code/online-enrollment](02_Source_Code/online-enrollment/) | PHP application, SQL scripts, setup guide, test scripts, and recorded results |
| [03_Evidence](03_Evidence/) | Implementation evidence and actual phpMyAdmin table-structure screenshots |
| [04_Presentation](04_Presentation/) | Final PowerPoint with slide-specific narration notes and a matching narration script |
| [START_HERE.md](START_HERE.md) | Final-package overview and submission guidance |

### Key Documents

- [Software Requirements Specification](01_Documents/01_SRS_Revised.docx)
- [UML Design Model](01_Documents/02_UML_Design_Model_Revised.docx)
- [Landing, Login, and Enrollment Pages](01_Documents/03_Landing_Login_Enrollment_Revised.docx)
- [MySQL Database and Class Registration](01_Documents/04_MySQL_Class_Registration_Revised.docx)
- [PHP Code Explanation](01_Documents/05_PHP_Code_Explanation.docx)
- [Consolidated References](01_Documents/06_Consolidated_References.docx)
- [Project Test Plan](01_Documents/07_Project_Test_Plan.docx)
- [Final Presentation](04_Presentation/CST499_Final_Presentation.pptx)

## Local Setup

### Prerequisites

- PHP 8.3 with the `pdo_mysql` and `mbstring` extensions.
- MySQL 8.0.16 or later; the recorded baseline used MySQL 8.0.46.
- phpMyAdmin or another MySQL administration client.
- A browser and a local PHP-capable server.

### 1. Place the Application Files

Download or clone this repository. For XAMPP on Windows, copy the contents of `02_Source_Code/online-enrollment` into:

```text
C:\xampp\htdocs\online-enrollment
```

Start Apache and the database service in the XAMPP Control Panel.

### 2. Create and Populate the Database

For a fresh local installation, import these scripts in order through phpMyAdmin or your database client:

1. `database/schema.sql`
2. `database/week4.sql`
3. `database/seed_courses.sql`

These paths are relative to the application folder. The first script creates the initial account schema, the second adds enrollment and waiting-list tables, and the third supplies a fictional course catalog.

For an existing Week 3 database, back up its data and follow the migration instructions in the [application README](02_Source_Code/online-enrollment/README.md). Review seed IDs before importing into a database that already contains course data.

### 3. Configure Database Access

Change the example password in `database/create_app_user.example.sql`, then execute that script using a database administrator account. It grants the application the table-specific permissions required by the prototype.

Copy:

```text
app/config.example.php
```

To:

```text
app/config.local.php
```

Enter the matching host, port, database name, application username, and password. Keep `config.local.php` out of GitHub; the application folder includes a `.gitignore` entry for it.

### 4. Open the Application

With XAMPP, visit:

```text
http://localhost/online-enrollment/public/
```

Alternatively, from the application folder, start PHP's local development server:

```bash
php -S 127.0.0.1:4173 -t public
```

Then visit `http://127.0.0.1:4173/`. This server is intended for local development.

### 5. Create Demonstration Accounts

Register through the interface, or run this command from the application folder:

```bash
php scripts/seed_demo.php
```

The script creates the fictional users `alex`, `brooke`, and `casey`, each with the local demonstration password `DemoStudent2026!`. It does not change existing accounts with those IDs.

## Demonstration Workflow

1. Log in and select Fall 2026 in the course catalog.
2. Register for a course with an available seat.
3. Open the schedule and verify the new class.
4. Add another course, then remove the first through the confirmation page.
5. Check the `enrollments` table in phpMyAdmin to confirm the stored changes.
6. To demonstrate waiting lists, fill the one-seat CST301 offering with one student and join its queue with another. Cancel the enrolled student's class, then accept or decline the waiting student's reserved-seat offer.

Use a fresh demonstration database to reproduce the included evidence sequence. Account and enrollment IDs may differ between installations.

## Database Design

| Table | Purpose |
| --- | --- |
| `accounts` | Login identifiers, email, password hashes, and account metadata |
| `profiles` | Student names and phone numbers linked to accounts |
| `courses` | Course codes, titles, and credits |
| `semesters` | Academic terms and years |
| `offerings` | Course sections, semester associations, and capacity |
| `enrollments` | Active student registrations |
| `waiting_list` | Queue membership and ordering information |
| `seat_offers` | Reserved seats, recipients, expiration, and outcomes |
| `notification_tasks` | Pending notification work for future email processing |

Foreign keys link related records. Unique constraints prevent duplicate student-offering enrollments. The application locks an offering before a seat-changing operation and commits related changes together.

The physical design refines the original UML model: numeric database keys replace conceptual UUIDs, courses and semesters are separate tables, and cancellation deletes the active enrollment row. Terminal seat-offer outcomes retain their recipient history. The revised UML document explains these mappings.

## PHP Organization and Security Controls

- `app/Database.php` configures and exposes the PDO connection.
- `app/RegistrationService.php` validates and persists account registration.
- `app/EnrollmentService.php` handles seat calculations, registration, cancellation, queue membership, and offers.
- `app/bootstrap.php` supplies shared session, authorization, CSRF, and output-escaping helpers.
- `public/` contains the entry pages and protected student workflows.
- `scripts/expire_offers.php` performs offer-expiration housekeeping. Schedule it once per minute for unattended expiration; catalog and waiting-list requests also refresh offers.

Implemented controls include prepared SQL statements, password hashing, session-based ownership checks, a 30-minute inactivity timeout, CSRF validation, HTML escaping, and transaction rollback. These controls do not constitute a completed security audit.

## Recorded Verification

The retained October 28, 2026 baseline reports:

| Test suite | Passed | Failed |
| --- | ---: | ---: |
| Authentication regression | 27 | 0 |
| Database and concurrency | 27 | 0 |
| Application HTTP | 30 | 0 |
| **Total** | **84** | **0** |

Checks covered duplicate rejection, foreign keys, capacity, competing requests for one seat, queue ordering, offer transitions, ownership enforcement, and rollback after an injected failure. These are historical results for the included baseline, not a newly executed acceptance run.

See the [verification summary](02_Source_Code/online-enrollment/docs/TEST_RESULTS.md), [verification log](02_Source_Code/online-enrollment/docs/VERIFICATION_LOG.txt), and JSON result files in the same directory.

To run authentication regression checks against the PHP development server, use the application folder:

```bash
php tests/http_smoke.php http://127.0.0.1:4173
```

Database integration tests require a disposable `online_enrollment_test` database and test-specific configuration. They inject failures and change test data. Follow the [application README](02_Source_Code/online-enrollment/README.md) before running integration or concurrency tests; do not target a database containing important records.

## Remaining Release Work

- Implement external email delivery, delivery timing, and retry processing for notification tasks.
- Configure and verify HTTPS deployment.
- Add login rate limiting and complete broader security testing.
- Measure the proposed performance target with 50 concurrent users and 1,000 offerings.
- Complete stakeholder acceptance testing and release approval.
- Obtain institutional approval for the proposed 24-hour seat-offer policy.

The current application creates notification tasks and in-app notices but does not send external email. The recorded test results do not establish the unverified performance or deployment requirements.

## Final Project Revisions

The final compilation adds actual phpMyAdmin Structure screenshots to address instructor feedback about physical database evidence. It also clarifies conceptual-to-physical UML mappings, distinguishes requirements from implemented behavior, and includes a standalone PHP explanation.

The presentation contains 12 content slides, a title slide, and two reference slides. Each slide has spoken narration in its notes. The recorded presentation is submitted separately through the course's video-submission page.

## Academic References

APA-formatted references appear in the individual documents and the [consolidated reference document](01_Documents/06_Consolidated_References.docx). The project draws on the course texts and scholarly work on requirements, design, transactions, security, and software testing.
