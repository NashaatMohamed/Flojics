# Testing Documentation

## Test Cases
- **Successful escalation**: Ticket status changes and returns 202.
- **Ticket not found**: Returns 404.
- **Validation fail**: Missing channels returns 422.
- **Already escalated**: Returns 409.
- **Email/Slack notifications**: Asserts mail was queued and Slack HTTP post was made.
- **Retry behavior**: Job retries up to 5 minutes if it fails, then transitions to failed status.

## How we tested
We used Pest feature and unit tests with fakes:
- `Mail::fake()` to mock emails.
- `Http::fake()` to intercept Slack webhook requests.
- `Queue::fake()` to verify job dispatches.
- `Event::fake()` to assert dispatch of events.
