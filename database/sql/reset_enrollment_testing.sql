-- Reset enrollment test data only.
-- This keeps users, grade levels, shifts, and school fee setup intact.
-- Run manually against the AMIS enrollment database when you need a clean test flow.

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE student_account_payments;
TRUNCATE TABLE soa_monthly_billings;
TRUNCATE TABLE student_accounts;
TRUNCATE TABLE payments;
TRUNCATE TABLE students;
TRUNCATE TABLE enrollment_applicants;

SET FOREIGN_KEY_CHECKS = 1;
