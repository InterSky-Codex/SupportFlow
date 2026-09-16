# SupportFlow

> A modern helpdesk ticket management system built with native PHP, PDO, Bootstrap 5, and a lightweight MVC architecture.

SupportFlow is a portfolio project designed to demonstrate the development of a structured helpdesk system with ticket management, role-based access control, workflow management, comments, secure file attachments, notifications, audit logging, and a Knowledge Base.

The application is built without Laravel or another full-stack PHP framework, using a lightweight custom MVC architecture to keep the application structure explicit and understandable.

---

## Overview

SupportFlow provides a centralized workspace for managing IT support requests from ticket creation through resolution.

The system separates responsibilities between employees, technicians, and administrators while applying authorization policies to tickets, comments, attachments, and administrative features.

### Core workflow

```text
Employee
   │
   ▼
Create Ticket
   │
   ▼
Open
   │
   ▼
Assignment
   │
   ▼
In Progress
   │
   ├── Pending
   │
   ▼
Resolved
   │
   ▼
Closed
```

Ticket status transitions and assignment operations are handled through dedicated workflow and policy logic rather than relying solely on controller-level checks.

---

## Features

### 🎫 Ticket Management

* Create and manage support tickets
* Automatic ticket numbering
* Year-based ticket sequence
* Ticket search
* Filtering
* Pagination
* Ticket assignment
* Status workflow
* Ticket detail view
* Role-based ticket visibility
* Creator and assignee authorization rules

### 💬 Ticket Comments

* Discussion threads attached to tickets
* Authorized comment creation
* Comment deletion for permitted users
* Author and administrator authorization checks

### 📎 File Attachments

* Ticket file attachments
* File size validation
* Extension validation
* MIME/file signature validation
* Additional validation for supported OOXML files
* Randomized stored filenames
* Non-executable storage filenames
* Uploads stored outside the public web directory

### 👥 User & Role Management

Three application roles are supported:

| Role              | Description                                                                |
| ----------------- | -------------------------------------------------------------------------- |
| **Employee**      | Creates and manages their own support requests                             |
| **Technician**    | Handles assigned tickets and performs technical support                    |
| **Administrator** | Manages users, tickets, assignments, settings, and administrative features |

Administrator capabilities include:

* Create users
* Edit users
* Activate/deactivate accounts
* Reset passwords
* Manage workspace settings
* Assign tickets

### 🔔 Notifications

SupportFlow includes an application notification system for relevant ticket-related events.

The notification module is designed to keep users informed about changes within the helpdesk workflow without requiring them to continuously monitor individual tickets.

### 📚 Knowledge Base

The Knowledge Base provides reusable technical documentation that can help users resolve common problems before creating or escalating support tickets.

Features include:

* Public article listing
* Article search
* Category filtering
* Pagination
* Article detail pages
* Administrator article creation
* Administrator article editing
* Role-based administrative access
* Published article management

### 📝 Audit Logging

Important application activities can be recorded through the audit logging system.

The audit module provides a foundation for tracking changes related to areas such as:

* Tickets
* Comments
* Attachments
* Administrative actions

### ⚙️ Application Settings

Administrators can manage workspace-level configuration such as:

* Workspace name
* Application timezone

---

## Security

Security was considered throughout the application rather than being limited to authentication.

### Authentication

* PHP password hashing with `password_hash()`
* Password verification with `password_verify()`
* Session ID regeneration after authentication
* Active-account validation
* Session-based authentication

### Authorization

* Role-based access control
* Centralized ticket authorization policies
* Creator/assignee access rules
* Administrator-only management features
* Workflow restrictions for closed tickets
* Authorization checks for comments and attachments

### Request Security

* CSRF protection for state-changing forms
* Server-side input validation
* Output escaping with HTML escaping helpers
* Prepared PDO statements
* Environment-based configuration

### File Upload Security

Uploaded files are subject to multiple validation layers:

* Extension validation
* MIME/file signature validation
* File size limits
* OOXML structure validation where applicable
* Randomized filenames
* Non-executable storage filenames
* Storage outside the public document root

Sensitive runtime files and local environment configuration are excluded from version control.

---

## Technology Stack

| Technology        | Usage                                       |
| ----------------- | ------------------------------------------- |
| **PHP 8.3+**      | Application backend                         |
| **MySQL**         | Relational database                         |
| **PDO**           | Database access                             |
| **Bootstrap 5.3** | Responsive UI                               |
| **Composer**      | Dependency management and PSR-4 autoloading |
| **HTML5**         | Application views                           |
| **CSS**           | UI customization                            |
| **JavaScript**    | Client-side interactions                    |
| **Git / GitHub**  | Version control                             |

### Architecture

SupportFlow uses a lightweight custom MVC architecture consisting of components such as:

```text
Application
├── Controllers
├── Models
├── Services
├── Core Infrastructure
│   ├── Router
│   ├── Request
│   ├── Response
│   ├── Session
│   ├── CSRF
│   └── Environment
└── Views
```

Business logic is separated into services and policies where appropriate, while controllers primarily handle HTTP-level application flow.

---

## Project Structure

```text
SupportFlow/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Middleware/
│   ├── Models/
│   ├── Policies/
│   └── Services/
│
├── bin/
│   └── bootstrap-admin.php
│
├── config/
│
├── database/
│   └── migrations/
│
├── public/
│   ├── assets/
│   └── index.php
│
├── resources/
│   └── views/
│
├── routes/
│
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
│
├── .env.example
├── composer.json
└── README.md
```

---

## Database

Database migrations are designed to be executed in order.

| Migration                             | Module                                    |
| ------------------------------------- | ----------------------------------------- |
| `001_create_users.sql`                | Users and authentication                  |
| `002_create_ticket_module.sql`        | Tickets, categories, priorities, statuses |
| `003_create_ticket_comments.sql`      | Ticket comments                           |
| `004_create_application_settings.sql` | Application settings                      |
| `005_create_ticket_attachments.sql`   | Ticket attachments                        |
| `006_create_audit_logs.sql`           | Audit logging                             |
| `007_create_notifications.sql`        | Notifications                             |
| `008_create_knowledge_articles.sql`   | Knowledge Base                            |

---

## Installation

### Requirements

Before installing SupportFlow, make sure the environment provides:

* PHP 8.3 or newer
* MySQL
* Composer
* A web server capable of serving PHP applications

The application is designed to use the `public/` directory as the web document root.

### 1. Clone the repository

```bash
git clone https://github.com/InterSky-Codex/SupportFlow.git
cd SupportFlow
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure the environment

Copy the example environment file:

```bash
cp .env.example .env
```

Then configure the local database and application settings inside `.env`.

Example:

```env
APP_ENV=local
APP_URL=http://localhost:8080

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=supportflow
DB_USERNAME=root
DB_PASSWORD=
```

> Do not commit `.env` to version control.

### 4. Create the database

Create the database configured in `.env`.

For example:

```sql
CREATE DATABASE supportflow;
```

### 5. Run migrations

Execute the migrations in order:

```text
001_create_users.sql
002_create_ticket_module.sql
003_create_ticket_comments.sql
004_create_application_settings.sql
005_create_ticket_attachments.sql
006_create_audit_logs.sql
007_create_notifications.sql
008_create_knowledge_articles.sql
```

### 6. Configure the web server

Set the web server document root to:

```text
SupportFlow/public/
```

For a local Laragon setup, the project can be configured as a local virtual host or served through the appropriate PHP development environment.

### 7. Create the initial administrator

Run:

```bash
php bin/bootstrap-admin.php
```

Follow the prompts to create the initial administrator account.

### 8. Start using the application

Open the configured application URL and authenticate using the administrator account.

Employees can subsequently register through:

```text
/register
```

---

## Ticket Roles & Permissions

SupportFlow applies role-aware authorization to ticket operations.

### Employee

Employees can:

* Create tickets
* View tickets they are authorized to access
* Participate in permitted ticket discussions
* Follow the progress of their requests

### Technician

Technicians can:

* Work with assigned tickets
* Handle tickets they are authorized to access
* Update permitted ticket workflow states
* Participate in ticket discussions
* Work with ticket attachments where authorized

Technicians do not have administrator-level user management privileges.

### Administrator

Administrators have broader access for system administration, including:

* User management
* Ticket management
* Ticket assignment
* Application settings
* Knowledge Base administration
* Administrative operations

Authorization is enforced by application policies and services rather than relying only on UI visibility.

---

## Ticket Status Workflow

SupportFlow currently uses the following ticket states:

```text
Open
  ↓
Assigned
  ↓
In Progress
  ├── Pending
  │     ↓
  └── In Progress
        ↓
     Resolved
        ↓
      Closed
```

Closed tickets receive additional workflow restrictions, with administrative exceptions where appropriate.

Ticket numbering follows the format:

```text
TCK-YYYY-000001
```

The sequence is maintained per year and uses database-level locking to prevent conflicting ticket numbers during concurrent creation.

---

## Development Principles

The project follows several principles throughout its implementation:

* Keep business logic outside controllers where practical
* Separate HTTP handling from application services
* Use explicit authorization policies
* Validate input on the server
* Escape output
* Use prepared database statements
* Keep sensitive configuration outside source control
* Store uploaded files outside the public directory
* Prefer small, focused changes
* Preserve existing application behavior when extending the system

---

## Project Status

SupportFlow is an actively developed portfolio project.

### Current implementation

* [x] Custom PHP MVC foundation
* [x] Authentication
* [x] Role-based authorization
* [x] Dashboard
* [x] User management
* [x] Categories
* [x] Priorities
* [x] Ticket management
* [x] Ticket workflow
* [x] Ticket comments
* [x] File attachments
* [x] Application settings
* [x] Audit logging
* [x] Notifications
* [x] Knowledge Base
* [x] Search, filtering, and pagination
* [x] Security validation for file uploads

---

## Testing

The project has been developed with regression testing and feature-specific validation during implementation.

Testing has covered areas including:

* Authentication
* Ticket authorization
* Ticket workflow
* Comments
* Attachments
* Settings
* Audit logging
* Notifications
* Knowledge Base
* Search and filtering
* Pagination
* Regression behavior across existing modules

Feature development is validated incrementally to reduce the risk of breaking existing modules.

---

## Roadmap

Potential future improvements include:

* [ ] SLA management
* [ ] Advanced ticket analytics
* [ ] Knowledge Base article versioning
* [ ] More advanced notification delivery
* [ ] Email integration
* [ ] Dashboard reporting improvements
* [ ] Automated test suite expansion
* [ ] REST API expansion

The roadmap is subject to change as the project evolves.

---

## Portfolio Context

SupportFlow was created as a practical portfolio project to demonstrate skills in:

* PHP backend development
* MVC architecture
* MySQL database design
* Authentication and authorization
* Role-based access control
* Secure file handling
* Business logic separation
* CRUD application development
* Web application security
* Git and version control
* Technical problem solving

Rather than relying on a full-stack framework, the project implements its own lightweight application structure to demonstrate understanding of the underlying concepts involved in a PHP web application.

---

## Author

**Diarra Alifa Pratama**

IT Support / Technical Support / Junior Developer

* GitHub: [InterSky-Codex](https://github.com/InterSky-Codex)
* Portfolio: [My Portfolio](https://intersky-codex.github.io/My-Portfolio/)
* LinkedIn: [Diarra Alifa Pratama](https://www.linkedin.com/in/diarra-alifa-pratama-21979628b/)

---

## License

This project is intended primarily as a portfolio and learning project.
