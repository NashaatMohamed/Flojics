# Ticket Escalation System

Scaffold for escalating tickets and sending email/Slack notifications.

## Requirements
- PHP 8.3+
- Node.js 20+
- MySQL

## How to set up

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Copy env:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure MySQL in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=flojics
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Migrate and seed default ticket data:
   ```bash
   php artisan migrate --seed
   ```

5. Run servers:
   ```bash
   php artisan serve
   npm run dev
   php artisan queue:work --queue=escalations
   ```

To test, go to: `http://127.0.0.1:8000/tickets/1`

Note: It will redirect you to register or log in first (default: `test@example.com` / `password`). This policy check was added as a bonus safety step.
# Flojics
