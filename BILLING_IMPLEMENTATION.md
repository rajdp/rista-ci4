# Recurring Billing & Fee Terms Implementation Guide

## Overview

This document provides implementation details, examples, and setup instructions for the EdQuill Recurring Billing system.

## Database Schema

### New Tables

1. **t_billing_schedule** - Per-enrollment billing state
2. **t_fee_policy** - School-scoped onboarding fee defaults
3. **t_deposit_ledger** - Deposit charge/refund/credit tracking
4. **t_billing_run** - Daily run idempotency tracking
5. **t_billing_run_item** - Per-schedule processing audit
6. **t_invoice_item** - Invoice line items

### Updated Tables

- **invoices** - Added `school_id`, `enrollment_id`, `total_cents`, and `failed` status

## Proration Examples

### Monthly Proration

**Formula:** `prorated_cents = round(fee_monthly_cents * (days_used / days_in_month))`

**Example 1:**
- Monthly fee: $100 (10,000 cents)
- Start date: January 15, 2025
- Anchor date: February 1, 2025
- Days used: 17 (Jan 15 to Jan 31, inclusive)
- Days in month: 31
- Proration: `round(10000 * (17/31)) = 5,484 cents = $54.84`

**Example 2:**
- Monthly fee: $150 (15,000 cents)
- Start date: February 20, 2025
- Anchor date: March 1, 2025
- Days used: 9 (Feb 20 to Feb 28, inclusive)
- Days in month: 28 (2025 is not a leap year)
- Proration: `round(15000 * (9/28)) = 4,821 cents = $48.21`

### Yearly Proration

**Formula:** `prorated_cents = round(fee_yearly_cents * (days_used / days_in_year))`

**Example:**
- Yearly fee: $1,200 (120,000 cents)
- Start date: March 15, 2025
- Anchor date: January 1, 2026
- Days used: 292 (Mar 15 to Dec 31, inclusive)
- Days in year: 365
- Proration: `round(120000 * (292/365)) = 96,000 cents = $960.00`

## Anchor Date Rules

### Monthly Billing

- Default anchor = enrollment date's day of month
- If anchor day > last day of month, bill on month end
- Examples:
  - Anchor 31 → bills on 30/28/29 depending on month
  - Anchor 15 → bills on 15th of each month
  - Enrollment Jan 20 → anchor = 20, next billing = Feb 20

### Yearly Billing

- Default anchor = enrollment date (month + day)
- If anchor day > last day of target month, bill on month end
- Examples:
  - Enrollment Jan 31 → anchor = (1, 31), next billing = Jan 31 next year
  - Enrollment Feb 29 (leap year) → anchor = (2, 29), next billing = Feb 28 (non-leap) or Feb 29 (leap)

## Daily Billing Run Setup

### Supervisor Configuration

Create `/etc/supervisor/conf.d/edquill-billing.conf`:

```ini
[program:edquill-billing]
command=/usr/bin/php /path/to/rista_ci4/spark billing:run --school=%(process_num)s
process_name=%(program_name)s_%(process_num)02d
numprocs=1
directory=/path/to/rista_ci4
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/edquill/billing.log
```

### Cron Alternative

For single-school setups, add to crontab:

```bash
0 2 * * * cd /path/to/rista_ci4 && php spark billing:run --school=1 >> /var/log/edquill/billing.log 2>&1
```

### Manual Run

```bash
cd /path/to/rista_ci4
php spark billing:run --school=1
php spark billing:run --school=1 --date=2025-11-21  # For specific date
```

## API Endpoints

### GET /api/billing/summary

Get billing summary grouped by term and next_billing_date.

**Query Parameters:**
- `from` (optional) - Start date filter (YYYY-MM-DD)
- `to` (optional) - End date filter (YYYY-MM-DD)
- `term` (optional) - Filter by term (one_time, monthly, yearly)

**Headers:**
- `X-School-Id` (required)

**Response:**
```json
{
  "success": true,
  "data": {
    "grouped": {
      "monthly": {
        "2025-11-20": [...],
        "2025-12-20": [...]
      }
    },
    "totals": {
      "due_today": {"count": 5, "total_cents": 50000},
      "next_7_days": {"count": 10, "total_cents": 100000},
      "overdue": {"count": 2, "total_cents": 20000}
    }
  }
}
```

### POST /api/billing/enrollment/{enrollmentId}/seed

Create or update billing schedule for an enrollment.

**Body:**
```json
{
  "start_date": "2025-11-20",
  "deposit_policy": "refundable",
  "deposit_cents": 5000,
  "anchor_day": 15,
  "anchor_month": 11
}
```

### POST /api/billing/schedule/{scheduleId}/invoice-now

Generate invoice immediately for a schedule.

### POST /api/billing/run

Trigger daily billing run (admin only).

**Body:**
```json
{
  "run_date": "2025-11-20"  // Optional, defaults to today
}
```

### GET /api/billing/schedules

List billing schedules with filters.

**Query Parameters:**
- `term` - Filter by term
- `status` - Filter by status (active, paused, ended)
- `course_id` - Filter by course
- `student_id` - Filter by student
- `from_date` - Start date filter
- `to_date` - End date filter

## Feature Flags

Feature flags are stored in `t_feature_flag` table:

- `billing.proration.method` - `'daily'` or `'half_up'` (default: `'daily'`)
- `billing.deposit.enabled` - `'true'` or `'false'` (default: `'true'`)
- `billing.onboarding.enabled` - `'true'` or `'false'` (default: `'true'`)
- `billing.email.enabled` - `'true'` or `'false'` (default: `'true'`)

### Setting Feature Flags

```sql
INSERT INTO t_feature_flag (school_id, flag_key, flag_value) 
VALUES (1, 'billing.email.enabled', 'false')
ON DUPLICATE KEY UPDATE flag_value = 'false';
```

## Idempotency

The daily billing run is idempotent:

1. Each run creates/claims a `t_billing_run` record with `(school_id, run_date)` unique key
2. Each processed schedule creates a `t_billing_run_item` with `(run_id, schedule_id)` unique key
3. Re-running the same day will skip already-processed schedules

## Testing

### Unit Tests

Run proration tests:
```bash
php spark test --filter ProrationServiceTest
```

### Integration Tests

Test daily run idempotency:
```bash
php spark test --filter BillingRunTest
```

### Manual Testing

1. Create test enrollment with fee
2. Verify schedule is created
3. Run billing command
4. Verify invoice is created
5. Re-run command - should skip (idempotency)
6. Verify next_billing_date advanced correctly

## Troubleshooting

### Invoice Not Generated

- Check schedule status is 'active'
- Verify next_billing_date <= today
- Check run_item table for errors

### Proration Incorrect

- Verify enrollment_date and anchor_date
- Check proration method feature flag
- Review ProrationService calculations

### Email Not Sending

- Check `billing.email.enabled` feature flag
- Verify student email exists
- Check email configuration in `app/Config/Email.php`
- Review application logs

## Migration Order

1. Run `2025-11-20-000001_CreateRecurringBillingTables.php`
2. Run `2025-11-20-000002_UpdateInvoiceTableForBilling.php`
3. Run `2025-11-20-000003_SeedBillingFeatureFlags.php`

```bash
cd /path/to/rista_ci4
php spark migrate
```

## Acceptance Criteria Checklist

- [x] Admin sees breakdown by term & next billing date
- [x] Enrollment seeding creates correct schedule
- [x] First invoice includes proration, onboarding, deposit
- [x] Daily run processes due schedules exactly once
- [x] Idempotency: re-running same day creates no duplicates
- [x] Email sends on invoice creation (if enabled)
- [x] Student Account Summary shows due amounts/dates
- [x] MySQL 5.7 compatible
- [x] Accessibility ≥ 90 on new pages


