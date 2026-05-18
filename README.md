# Khadamati

Khadamati is a Laravel-based e-government platform that digitizes how citizens, municipalities, and platform administrators interact around public services.

The system centralizes identity verification, service requests, document submission, online payments, appointments, notifications, office discovery, reporting, and request tracking into one web application.

## Table of Contents

- [Project Summary](#project-summary)
- [Main Roles](#main-roles)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [How the Platform Works](#how-the-platform-works)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Local Development Notes](#local-development-notes)
- [Implementation Notes](#implementation-notes)
- [Security Notes](#security-notes)

## Project Summary

Khadamati was built to reduce paperwork and manual follow-up in municipal and administrative workflows.

It provides:

- a citizen portal for requests, payments, appointments, tracking, and communication
- a municipality portal for service management, request handling, and citizen support
- an admin portal for oversight, identity verification review, platform management, and reporting

## Main Roles

### Admin

- Manage municipalities, offices, and platform users
- Review ID verification submissions
- Oversee requests, payments, notifications, and reports
- Monitor operational statistics and revenue

### Municipality User

- Manage one assigned office
- Create and update service categories and services
- Process incoming requests and upload official responses
- Manage appointments, feedback, and citizen chat

### Citizen

- Register and complete identity verification
- Browse services and government offices
- Submit requests and upload supporting documents
- Pay online, track requests, book appointments, and leave feedback

## Key Features

### Authentication and Access Control

- Email/password login
- Email-based two-factor authentication for password users
- Google login for citizens using Laravel Socialite
- Role-based redirection after login
- Middleware and policy-based authorization

### Lebanese ID Verification

- Front and back Lebanese ID upload
- OCR using Google Cloud Vision
- Custom parser for Arabic Lebanese ID fields
- Queue-based background processing to avoid request timeouts
- Manual admin approval before citizen activation

### Service Requests

- Browse services by municipality, office, and category
- Submit requests with notes and required documents
- Track requests from the citizen dashboard
- Public request tracking with a unique tracking code and QR code

### Payments

- Card payments with Stripe Checkout
- Webhook-based payment confirmation
- Local payment records and history
- Revenue visibility in reports and dashboards

### Appointments

- Municipality-managed time slots
- Citizen booking flow
- Approval, rescheduling, and cancellation support
- Notification support for appointment updates

### Notifications and Communication

- In-app notifications using Laravel Notifications
- Email notifications for important workflow changes
- Real-time request chat using Laravel Reverb and Echo

### Reports and Documents

- Dashboard cards and charts
- Office, request, and revenue reports
- PDF receipts
- PDF official response generation
- CSV and PDF report export

### Maps and Office Discovery

- Google Maps and Google Places for office and municipality location setup
- Citizen office discovery and search
- Nearest-office filtering using geographic distance
- Public map display with Leaflet and OpenStreetMap

## Tech Stack

### Backend

- PHP 8.2
- Laravel 12
- Eloquent ORM
- Laravel Queues
- Laravel Notifications
- Laravel Reverb
- Laravel Socialite

### Frontend

- Blade templates
- AdminLTE
- Vite
- Chart.js
- Leaflet

### External Services and Libraries

- Google Cloud Vision OCR
- Google OAuth
- Google Maps / Places
- Stripe Checkout
- DomPDF
- `qrcode` npm package

## How the Platform Works

### 1. Citizen Registration and Verification

1. The citizen signs up with email/password or Google login.
2. The citizen uploads front and back Lebanese ID images.
3. The images are stored and OCR is processed in background jobs.
4. Extracted fields are reviewed by an admin.
5. After approval, the citizen account becomes active.

### 2. Service Request Flow

1. The citizen browses services by office or category.
2. Required documents are uploaded with the request.
3. The municipality reviews and updates the request status.
4. If needed, the request can move through missing-documents, review, approval, rejection, or completion states.
5. The citizen can track progress from the dashboard or through the public tracking page.

### 3. Payment Flow

1. For paid services, the citizen is redirected to Stripe Checkout.
2. A local pending payment record is created first.
3. Stripe confirms the payment through a webhook.
4. The service request is finalized after successful confirmation.

### 4. Tracking and QR Code

- Each request receives a unique tracking code.
- A QR code is generated dynamically from the public tracking URL.
- The QR image is not stored in the database; the tracking code is.

### 5. Real-Time and Live Updates

- Chat is real-time through Laravel Reverb and Echo.
- Several other parts of the app, such as badges, queues, and waiting screens, use lightweight polling and partial refresh.

## Project Structure

```text
app/
  Http/Controllers/        Request handlers for each role and feature
  Models/                  Eloquent models
  Services/                Business logic and integrations
  Jobs/                    Background processing
  Notifications/           In-app and email notifications
  Policies/                Access-control rules
  Support/                 Utility helpers

database/
  migrations/             Schema definition
  seeders/                Demo and local data seeding

resources/views/
  Admin/                  Admin UI
  Municipality/           Municipality UI
  Citizen/                Citizen UI
  Authentication/         Login, signup, verification, and 2FA views
  pdfs/                   PDF templates

routes/
  auth.php                Authentication routes
  admin.php               Admin routes
  municipality.php        Municipality routes
  citizen.php             Citizen routes
  web.php                 Shared and utility routes
```

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js and npm
- SQLite or another supported database

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure the database

By default, the project is prepared for SQLite.

Create the database file if needed:

```bash
type nul > database\database.sqlite
```

Then run:

```bash
php artisan migrate
```

### 4. Create the storage symlink

```bash
php artisan storage:link
```

### 5. Seed demo data

```bash
php artisan db:seed
```

### 6. Run the application

For the standard development workflow:

```bash
composer run dev
```

This starts:

- the Laravel local server
- the queue listener
- the log watcher
- the Vite dev server

If you want to run services separately, you can use:

```bash
php artisan serve
php artisan queue:work
php artisan reverb:start
npm run dev
```

## Environment Variables

Review these values before running the full feature set.

### Core App

```env
APP_NAME=Khadamati
APP_URL=http://127.0.0.1:8000
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb
SESSION_DRIVER=database
```

### Google Vision OCR

```env
GOOGLE_APPLICATION_CREDENTIALS=C:\absolute\path\to\vision-service-account.json
IDENTITY_CONFIDENCE_THRESHOLD=0.75
```

### Google OAuth and Maps

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=
GOOGLE_MAPS_API_KEY=
```

### Stripe

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### Mail

```env
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Reverb

```env
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Local Development Notes

- If OCR jobs seem stuck, make sure a queue worker is running.
- If chat is not updating in real time, make sure Reverb is running.
- If branding changes do not appear, run:

```bash
php artisan config:clear
```

## Implementation Notes

These points are useful both for developers and for project presentation:

- Card payments are implemented with Stripe.
- Cryptocurrency payment is not implemented in the current codebase.
- Chat is true real-time through Reverb.
- Several other live updates use AJAX polling rather than websockets.
- SMS support is only stubbed/prepared and is not connected to a real provider.
- QR codes are generated dynamically from request tracking URLs.
- Statistics are computed from the application database and rendered with Chart.js.

## Security Notes

Before pushing this project publicly, do not include:

- `.env`
- real Stripe keys
- Google OAuth client secrets
- Google service-account JSON files
- any private production credentials

Only commit safe placeholders such as `.env.example`.

