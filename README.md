# billing-system-api

> Laravel REST API for the Facturo billing system — handles clients, invoices, PDF generation, Stripe payments, and automated email notifications.

---

## Tech Stack

- **PHP 8.5** / **Laravel 12**
- **MySQL** — primary database
- **mPDF** — PDF generation
- **Stripe** — online payments
- **Laravel Queues** — async email jobs
- **Laravel Scheduler** — automated reminders
- **[Auth as a Service](https://github.com/patrick-rakotoharilalao/auth-service-project)** — external JWT authentication

---

## Architecture

```
Routes → Middleware → FormRequest → Controller → Service → Repository → Model
```

Authentication is fully delegated to the external AaaS. Every protected route passes through a custom `VerifyJwtToken` middleware that calls the AaaS `/auth/verify` endpoint to validate the JWT.

---

## Prerequisites

- PHP >= 8.2
- Composer
- MySQL
- [AaaS](https://github.com/patrick-rakotoharilalao/auth-service-project) running locally
- Stripe account (test mode)
- Mailtrap account (for email testing)

---

## Getting Started

```bash
# Clone the repo
git clone https://github.com/billing-saas/billing-system-api.git
cd billing-system-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Start the server
php artisan serve
```

---

## Environment Variables

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=billing_system
DB_USERNAME=root
DB_PASSWORD=

# AaaS — https://github.com/patrick-rakotoharilalao/auth-service-project
AAAS_URL=http://localhost:3001/api/v1
AAAS_API_KEY=your_aaas_api_key

# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Mail (Mailtrap for development)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@facturo.com
MAIL_FROM_NAME="Facturo"

# Queue
QUEUE_CONNECTION=database
```

---

## API Endpoints

### Auth (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/register` | Register a new user |

### Clients
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/clients` | List clients (paginated) |
| `POST` | `/api/v1/clients` | Create a client |
| `GET` | `/api/v1/clients/{id}` | Get a client |
| `PUT` | `/api/v1/clients/{id}` | Update a client |
| `DELETE` | `/api/v1/clients/{id}` | Delete a client |

### Invoices
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/invoices` | List invoices (paginated) |
| `POST` | `/api/v1/invoices` | Create an invoice |
| `GET` | `/api/v1/invoices/{id}` | Get an invoice |
| `PUT` | `/api/v1/invoices/{id}` | Update an invoice |
| `DELETE` | `/api/v1/invoices/{id}` | Delete an invoice |
| `POST` | `/api/v1/invoices/{id}/send` | Send invoice + create Stripe payment link |
| `POST` | `/api/v1/invoices/{id}/pay` | Mark invoice as paid (manual) |
| `GET` | `/api/v1/invoices/{id}/download` | Download invoice PDF |

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/dashboard/stats` | Get dashboard statistics |

### Webhooks (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/webhooks/stripe` | Stripe payment webhook |

---

## Invoice Workflow

```
Draft → Sent → Paid
              ↑
         Overdue (auto via Scheduler)
```

- **Draft** — editable, not visible to client
- **Sent** — PDF generated, Stripe payment link created, email sent to client
- **Paid** — payment confirmed via Stripe webhook, confirmation email sent
- **Overdue** — automatically set by the scheduler when due date is passed

---

## Running Queues & Scheduler

```bash
# Process queued email jobs
php artisan queue:work

# Run scheduled commands manually (for testing)
php artisan invoices:check-overdue
php artisan invoices:send-reminders
```

In production, configure a cron job to run the Laravel scheduler every minute:

```
* * * * * php /path/to/artisan schedule:run
```

---

## Testing Stripe Webhooks

```bash
# Install Stripe CLI and login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/v1/webhooks/stripe

# Trigger a test payment
stripe trigger checkout.session.completed
```
