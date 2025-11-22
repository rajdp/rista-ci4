-- Add fee-related fields to student_self_registration_courses table

ALTER TABLE student_self_registration_courses
ADD COLUMN IF NOT EXISTS start_date DATE NULL AFTER decision_notes,
ADD COLUMN IF NOT EXISTS fee_term TINYINT(1) NULL COMMENT '1 = one-time, 2 = recurring' AFTER start_date,
ADD COLUMN IF NOT EXISTS next_billing_date DATE NULL AFTER fee_term,
ADD COLUMN IF NOT EXISTS deposit DECIMAL(12,2) NULL AFTER next_billing_date,
ADD COLUMN IF NOT EXISTS onboarding_fee DECIMAL(12,2) NULL AFTER deposit,
ADD COLUMN IF NOT EXISTS registration_fee DECIMAL(12,2) NULL AFTER onboarding_fee,
ADD COLUMN IF NOT EXISTS prorated_fee DECIMAL(12,2) NULL AFTER registration_fee,
ADD COLUMN IF NOT EXISTS class_id BIGINT UNSIGNED NULL AFTER prorated_fee;



