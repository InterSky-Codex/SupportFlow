# PROJECT_RULES.md

# SupportFlow Product Rules

Version: 1.0

---

# Vision

SupportFlow is a modern IT Helpdesk Ticket Management System designed as a real-world internal business application.

This project exists to demonstrate software engineering skills rather than simply CRUD implementation.

The application should feel like software used inside medium-sized companies.

Every feature should have a real business purpose.

---

# Product Goals

Primary Goals

- Modern UI
- Professional Architecture
- Maintainable Code
- Responsive Design
- Portfolio Quality

Secondary Goals

- Security
- Performance
- Accessibility
- Reusable Components
- Easy Future Expansion

---

# Target Users

Employee

Can

- Create Ticket
- View Own Ticket
- Upload Attachment
- Add Comment
- Receive Notifications

Technician

Can

- View Assigned Ticket
- Change Status
- Add Internal Notes
- Upload Solution
- Resolve Ticket

Administrator

Can

- Manage Users
- Manage Categories
- Manage Priorities
- Manage Statuses
- Assign Technician
- Configure System

---

# Product Modules

Authentication

Dashboard

Tickets

Comments

Attachments

Notifications

Reports

Settings

Profile

Activity Logs

---

# MVP Scope

The first version should include

Authentication

Dashboard

Ticket CRUD

Categories

Priorities

Statuses

Comments

Attachments

Search

Filter

Pagination

Profile

Logout

Nothing more.

Do not implement AI features in MVP.

---

# Future Features

AI Ticket Summary

AI Suggested Solution

Email Notification

Dark Mode

Knowledge Base

Asset Management

Live Chat

PWA

API

Mobile App

---

# Dashboard Requirements

Dashboard must display

Open Tickets

Assigned Tickets

Resolved Tickets

Closed Tickets

Critical Tickets

Recent Activities

Recent Tickets

Quick Actions

Charts

Dashboard should never feel empty.

If no data exists,

show informative empty states.

---

# Ticket Rules

Every ticket must have

Ticket Number

Title

Description

Category

Priority

Status

Creator

Assigned Technician

Created Date

Updated Date

Optional

Due Date

Attachment

Resolution

---

# Ticket Number Format

Example

TCK-2026-000001

Never use auto increment IDs for display.

---

# Status Flow

Open

↓

Assigned

↓

In Progress

↓

Pending

↓

Resolved

↓

Closed

Status should always follow this flow.

---

# Priority Levels

Low

Medium

High

Critical

Priority colors

Low

Gray

Medium

Blue

High

Orange

Critical

Red

---

# Categories

Hardware

Software

Printer

Internet

Network

Email

CCTV

Security

Access

Request

Other

Categories should be manageable from Admin Panel.

---

# Comments

Comments must display

Avatar

Name

Role

Time

Message

Support multiline comments.

---

# Attachments

Allowed

jpg

jpeg

png

pdf

docx

xlsx

Maximum size

10 MB

Store uploads outside application logic.

Never trust uploaded files.

---

# Search

Search should support

Ticket Number

Title

User

Category

Status

Priority

Technician

---

# Filters

Status

Priority

Category

Date

Assigned User

Multiple filters should work together.

---

# Tables

Every table should include

Search

Filter

Pagination

Sorting

Responsive Layout

Empty State

Loading State

---

# UI Rules

Keep UI clean.

Avoid unnecessary colors.

Whitespace is important.

Use Bootstrap Cards.

Use Bootstrap Icons.

Buttons must be consistent.

Spacing should be consistent.

---

# Responsive Rules

Desktop

Laptop

Tablet

Mobile

Every screen should work properly.

---

# UX Rules

Always confirm destructive actions.

Use Toast Notifications.

Never use browser alert().

Use SweetAlert2.

Loading indicators should be visible.

---

# Security Rules

Never expose passwords.

Never expose SQL errors.

Never expose stack traces.

Validate all inputs.

Escape all outputs.

Use CSRF tokens.

Use Prepared Statements.

---

# Error Handling

Every error should have

Readable message

Developer log

User-friendly feedback

Never show raw PHP errors.

---

# Logging

Track

Login

Logout

Create Ticket

Update Ticket

Delete Ticket

Assign Ticket

Change Status

Store

User

Date

IP

Action

---

# Notifications

Version 1

In-App Notification

Version 2

Email

Version 3

Push Notification

---

# Performance

Avoid duplicate queries.

Use pagination.

Keep JavaScript modular.

Reuse components.

Avoid unnecessary libraries.

---

# Accessibility

Use semantic HTML.

All buttons need labels.

Forms need labels.

Images need alt attributes.

Keyboard navigation should work.

---

# Code Review Checklist

Before completing any feature

✔ Responsive

✔ No duplicated code

✔ Security considered

✔ Uses reusable components

✔ Uses Bootstrap utilities

✔ No inline CSS

✔ No inline JavaScript

✔ No SQL in Views

✔ No Business Logic in Views

✔ Follows MVC

---

# Git Workflow

Every completed feature must be committed.

Examples

feat: authentication

feat: dashboard

feat: ticket crud

feat: comments

fix: upload validation

refactor: simplify routing

docs: update readme

---

# Portfolio Goal

When someone opens this repository they should think

"This looks like software that could actually be deployed inside a company."

Not

"This is another CRUD tutorial."

Every decision should support this goal.