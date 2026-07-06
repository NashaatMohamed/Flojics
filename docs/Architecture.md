# Architecture Notes

## Files added/modified
- `App\Actions\EscalateTicketAction` - handles ticket status/date update.
- `App\Data\EscalateTicketData` - request DTO.
- `App\Http\Controllers\TicketController` - renders frontend page.
- `App\Http\Controllers\Api\TicketEscalationController` - endpoint for POST /api/tickets/{id}/escalate.
- `App\Jobs\SendEscalationNotificationJob` - handles queue dispatches.
- `App\Services\Notification` - holds drivers for Email and Slack.

## Key Choices
- Strategy pattern used for notification channels (`NotificationManager` handles registration).
- Eager load relations and use `lockForUpdate` on tickets and notifications to avoid concurrent worker conflicts.
- Job queue connection and name configured dynamically in the constructor.

## Adding a new channel (e.g. SMS)
1. Add case to `App\Enums\NotificationChannel`.
2. Write driver class implementing `NotificationChannelInterface`.
3. Register the class inside `AppServiceProvider`.
