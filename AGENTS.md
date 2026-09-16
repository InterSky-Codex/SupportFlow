# AGENTS.md

# SupportFlow Development Guide

## Project Overview

SupportFlow is a modern Helpdesk Ticket Management System built as a portfolio-quality project.

The objective is NOT to build a CRUD tutorial.

The objective is to build software that resembles a real internal business application while following professional software engineering practices.

Target audience:

- Junior PHP Developer Portfolio
- Full Stack Developer Portfolio
- IT Support Portfolio

---

# Technology Stack

Backend

- PHP 8.3
- MySQL
- PDO
- Composer

Frontend

- HTML5
- Bootstrap 5.3
- Bootstrap Icons
- JavaScript ES6
- Chart.js
- SweetAlert2

Future

- PHPMailer
- Dark Mode
- PWA

No Framework.

Laravel is intentionally NOT used.

---

# Architecture

Always use lightweight MVC.

Never place business logic inside views.

Never write SQL inside HTML.

Controllers

Responsible for

- Request
- Validation
- Calling Services
- Returning Views

Models

Responsible for

- Database interaction only

Services

Responsible for

- Business Logic

Helpers

Responsible for

- Utility functions

Views

Responsible only for presentation.

---

# Folder Structure

supportflow/

app/

Controllers/

Models/

Services/

Core/

Helpers/

Middleware/

config/

database/

public/

assets/

css/

js/

img/

uploads/

routes/

views/

layouts/

components/

auth/

dashboard/

tickets/

profile/

storage/

logs/

cache/

vendor/

---

# Coding Standards

Use PSR-12.

Use namespaces.

Use Composer autoload.

Keep classes small.

Keep methods short.

Prefer composition over inheritance.

Avoid duplicated code.

Always create reusable components.

Do not hardcode values.

Avoid magic numbers.

---

# Naming Convention

Classes

PascalCase

Example

TicketController

UserService

TicketRepository

Methods

camelCase

Examples

createTicket()

updateStatus()

login()

Variables

camelCase

Constants

UPPER_CASE

Database

snake_case

Tables

tickets

users

ticket_comments

Columns

created_at

assigned_to

ticket_number

Routes

kebab-case

tickets/create

tickets/edit

dashboard

---

# Database Rules

Always use PDO.

Always use Prepared Statements.

Never concatenate SQL.

Always validate user input.

Use foreign keys.

Use timestamps.

Every table should have:

id

created_at

updated_at

---

# Security Rules

Always use

password_hash()

password_verify()

Prepared Statements

CSRF Token

Session Validation

Role Validation

Output Escaping

File Validation

Never trust user input.

---

# UI Guidelines

Inspired by

GitHub

Linear

Notion

Freshservice

Jira

Use

Bootstrap Grid

Bootstrap Cards

Bootstrap Icons

Rounded corners

Soft shadows

Minimal colors

Lots of whitespace

Responsive layout

Consistent spacing

---

# Components

Prefer reusable components.

Examples

Navbar

Sidebar

Footer

Statistic Card

Modal

Toast

Table

Pagination

Breadcrumb

Empty State

Search Bar

Filter

Do not duplicate HTML.

---

# JavaScript Rules

Use ES6.

Prefer Classes.

Split files by responsibility.

Example

ticket.js

dashboard.js

toast.js

table.js

modal.js

validation.js

Do not write one giant app.js.

---

# CSS Rules

Use CSS variables.

Organize sections.

Example

Variables

Layout

Navbar

Sidebar

Cards

Tables

Forms

Buttons

Utilities

Responsive

Avoid inline CSS.

---

# Git Rules

Commit after every completed feature.

Commit message examples

feat: add authentication

feat: implement ticket CRUD

fix: resolve session timeout

style: improve dashboard spacing

refactor: simplify ticket service

docs: update README

---

# Sprint Workflow

Sprint 1

Project setup

Composer

MVC

Routing

Authentication

Dashboard Layout

Sprint 2

Ticket CRUD

Category

Priority

Status

Sprint 3

Comments

Timeline

Attachments

Sprint 4

Dashboard Charts

Statistics

Search

Filter

Pagination

Sprint 5

Settings

Profile

Notifications

Email

Dark Mode

---

# Before Creating New Code

Always search the project first.

Reuse existing components.

Do not duplicate controllers.

Do not duplicate CSS.

Do not duplicate JavaScript.

If functionality already exists,

extend it instead of rewriting it.

---

# Before Finishing Any Task

Ensure

No duplicated code

No warnings

No unused imports

Consistent formatting

Responsive layout

Security considered

Reusable implementation

---

# Code Quality Goal

Every file should look like it belongs in a production application.

Never write tutorial code.

Never generate placeholder implementations unless explicitly requested.

Think like a Senior Software Engineer before writing code.

Maintainability is more important than writing code quickly.