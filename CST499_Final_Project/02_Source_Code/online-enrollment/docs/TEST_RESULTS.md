# Week 4 Verification Results

Verification environment: PHP 8.3.6, MySQL 8.0.46, phpMyAdmin 5.2.1 on Linux. Date: October 28, 2026. Synthetic accounts and a fictional course catalog were used.

| Suite | Passed | Failed | Result file |
| --- | ---: | ---: | --- |
| Authentication regression | 27 | 0 | week3-regression-results.json |
| Database and concurrency | 27 | 0 | week4-integration-results.json |
| Application HTTP | 30 | 0 | week4-http-results.json |

Total: 84 checks passed. PHP syntax checks also passed for application files.

The integration suite verifies uniqueness, foreign keys, positive capacity, semester filtering, idempotent changes, queue ordering, transactional failure rollback, reservations, expiration, ownership, and persistence on a new connection. Two simultaneous PHP workers compete for one remaining seat; exactly one enrollment is stored.

The HTTP run uses the restricted application database account. This exposed and resolved a SELECT FOR UPDATE permission failure that the administrator-level service tests did not expose. The final HTTP run passed all 30 checks. These results do not prove performance under 50 users, external email delivery, comprehensive security, HTTPS configuration, or stakeholder acceptance.
