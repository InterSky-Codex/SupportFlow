# SupportFlow

SupportFlow is a modern helpdesk ticket management system built with native PHP, PDO, Bootstrap, and a lightweight MVC architecture.

## Features

- [x] Dashboard UI (Role-scoped metrics)
- [x] Ticket Creation (Validation & security)
- [x] Ticket Listing (Search, filter, pagination)
- [x] Ticket View (State transition, assignment)
- [x] Ticket Comments (Authorized discussion threads)
- [x] Administrator Users Management (Create, edit, activate/deactivate, and password reset)
- [x] Administrator Settings Management (Workspace name and timezone)
- [ ] Comment Attachments
- [ ] Activity Timeline

## Foundation

- PHP 8.3+ with Composer PSR-4 autoloading
- Front controller and explicit web routes
- Separation of controllers, core infrastructure, services, models, and views
- Environment-based configuration and production-safe PHP error logging
- Responsive reusable application layout
- CSRF-protected registration, login, and logout with PHP password hashing

## Local setup

1. Copy `.env.example` to `.env` and set the local URL and database credentials.
2. Run `composer install`.
3. Create the database selected in `.env`, then run the migrations in order: [001_create_users.sql](database/migrations/001_create_users.sql), [002_create_ticket_module.sql](database/migrations/002_create_ticket_module.sql), [003_create_ticket_comments.sql](database/migrations/003_create_ticket_comments.sql), and [004_create_application_settings.sql](database/migrations/004_create_application_settings.sql).
4. Configure the web server document root to `public/`.
5. Run `php bin/bootstrap-admin.php` to create the initial administrator account.
6. Open `/register` to create additional employee accounts.

The dashboard requires an authenticated account. User management is available only to administrators from the main navigation. The existing users table contains the role and activation fields required by this module, so no additional migration is required.
