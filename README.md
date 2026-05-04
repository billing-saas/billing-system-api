# Billing System API

A modern billing and invoicing backend built with Laravel.

This API powers the billing platform with client management, invoice lifecycle handling, PDF generation, Stripe payments, email reminders, dashboard metrics, and external authentication.

## Stack

- Laravel
- MySQL
- Stripe
- Laravel Scheduler
- Laravel Queue

## Authentication

Authentication is handled by an external Authentication-as-a-Service (AaaS).

The API validates access tokens against the external auth service and retrieves user identity and permissions before granting access.

AaaS repository: https://github.com/patrick-rakotoharilalao/auth-service-project

## Features

- Client management (CRUD)
- Invoice management
- Invoice status workflow (`draft`, `sent`, `paid`, `overdue`)
- PDF invoice generation
- Stripe Checkout payments
- Stripe webhook reconciliation
- Automated email reminders
- Billing dashboard metrics

## Environment Variables

Add the following variables to your `.env` file in addition to Laravel's default configuration:

```env
AAAS_URL=http://localhost:3001/api/v1
X_API_KEY=

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

FRONTEND_URL=http://localhost:3000

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@facturo.com
MAIL_FROM_NAME="Facturo"

QUEUE_CONNECTION=database
```

## Installation

```bash
git clone https://github.com/billing-saas/billing-system-api
cd billing-system-api

composer install
cp .env.example .env
php artisan key:generate
```

Configure your database, then run:

```powershell
php artisan migrate
```

## Run the project

```powershell
## Start the Laravel server
php artisan serve 

## Run queue worker:
php artisan queue:work

## Run scheduler locally:
php artisan schedule:work
```

The API will be available at:

```
http://localhost:8000
```

## Notes
This project depends on the external AaaS service being available before accessing protected endpoints.