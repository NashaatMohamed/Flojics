# Requirement Analysis

## Product Owner Questions
- Do we auto-escalate after a certain SLA limit?
- Should user profiles define their default channels, or is it global?
- Any timezone rules for sending notifications?
- Who should receive the notifications (customer, agent, or supervisor)?

## Assumptions
- Customers and agents are assumed to exist.
- Recipient address is resolved dynamically inside the queue job.
- Re-escalating an already escalated ticket returns a 409 Conflict.
- Notifications are isolated so duplicate worker runs don't cause double alerts.

## Recommendations
- Add signature verification for Slack.
- Limit email sending rates globally.
