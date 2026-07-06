# Database Design

## Tables

### 1. `escalation_notifications`
- `id` (bigint PK)
- `ticket_id` (bigint FK -> tickets)
- `channel` (varchar)
- `recipient` (varchar, nullable)
- `status` (varchar, pending/sent/failed)

### 2. `escalation_notification_attempts`
- `id` (bigint PK)
- `escalation_notification_id` (bigint FK -> escalation_notifications, named `esc_notif_attempts_fk`)
- `attempt_number` (int)
- `status` (varchar)
- `error_message` (text, nullable)
- `executed_at` (timestamp)

*Unique constraint on `(escalation_notification_id, attempt_number)` named `esc_notif_attempts_uniq` to avoid duplicate insertions.*
