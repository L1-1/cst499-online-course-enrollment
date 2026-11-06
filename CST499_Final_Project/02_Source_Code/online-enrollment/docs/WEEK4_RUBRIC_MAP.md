# Week 4 submission evidence

The paper is CST499_Week4_Louis_Murphy.docx. Submit its companion CST499_Week4_Evidence.pdf with it. The paper has four double-spaced narrative pages, plus a title page and references. Screenshots are separate to keep every table field and code excerpt readable.

| Rubric requirement | Implementation and evidence |
| --- | --- |
| Revise Week 3 from instructor feedback | Actual phpMyAdmin Structure views for accounts and profiles, Figures 3 and 4; the separate Week 3 correction file contains those views. |
| Create the remaining MySQL tables | database/week4.sql adds seven InnoDB tables. Figures 1 and 2 show before/after migration; Figures 3-12 show columns, indexes, and enrollment foreign keys. |
| Create remaining pages from the design | courses.php, schedule.php, drop.php, waiting.php, offer.php, profile.php; enroll.php handles POST actions. Figures 14-15, 18, 20-21, 23-27, and 30 show rendered pages. |
| Before/after database data | First registration: 16 to 17. Additional registration: 17 to 19. Deletion: 19 to 22. Offer decision: 28 to 29. |
| PHP code screenshots | Figures 31-34: transaction/rollback, enrollment/deletion, protected request handler, and semester-filtered schedule query. |
| Implementation experience | Paper discusses evidence, permission debugging, transaction testing, and the remaining release work. |
| Introduction, thesis, and conclusion | Opening paragraph ends with the purpose; the final section concludes the paper. |
| Scholarly support | Course text plus three scholarly publications, each used in the body. |

## Scope and provenance

Screenshots were captured from running PHP 8.3.6 and MySQL 8.0.46 on Linux, using phpMyAdmin 5.2.1 for database views. Qt WebKit rendered actual HTTP responses; no database table image was manually drawn. Student and course records are fictional. The source-code images display actual file excerpts with line numbers.

Week 3 accounts and profiles definitions were reused unchanged. The Week 2 conceptual UUIDs are implemented as the existing numeric keys. Courses and semesters are normalized from the conceptual Offering class. Cancellation physically deletes an active enrollment, rather than retaining the cancelled status from the conceptual model. Seat offers retain terminal status and recipient history. These are explicit implementation refinements.

FR-11 external email delivery/retries, HTTPS deployment, the 50-user performance target, and stakeholder acceptance are not complete. Pending email notification tasks are stored but no email is sent. The 24-hour offer policy remains a prototype policy requiring approval. The scheduler script must be configured by the operator for unattended expiration.
