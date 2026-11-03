# Software Requirements Specification (SRS)
## IronCore Gym Management System — v1.0

Prepared by: Mohammed Emad ALaini (BS Software Engineering, Superior University, Lahore)
Format loosely follows IEEE 830 / ISO/IEC/IEEE 29148, simplified for a student project.

---

## 1. Introduction

### 1.1 Purpose
This document describes the functional and non-functional requirements of a web-based Gym Management System (GMS)
for a small-to-medium gym. It is intended for the developer, the course instructor, and anyone who wants to extend the system.

### 1.2 Scope
The system replaces paper registers and spreadsheets used at the front desk. It manages members, membership plans and
subscriptions, group classes and bookings, personal-training appointments, trainers, payment records and daily attendance,
and gives the owner a dashboard with key figures.

Out of scope for v1.0: real online payment processing (payments are *recorded* only), mobile apps, biometric/RFID
check-in hardware, SMS/e-mail notifications.

### 1.3 Definitions
| Term | Meaning |
|------|---------|
| Member | A customer registered at the gym |
| Plan | A membership package (duration + price) |
| Subscription | A member's purchase of a plan for a date range |
| Class | A weekly recurring group session led by a trainer |
| Booking | A member's seat in one dated session of a class |
| Appointment | A one-to-one personal training (PT) session |
| Online payment | Bank transfer / mobile wallet paid outside the system, recorded with a reference |

### 1.4 References
- IEEE Std 830-1998, Recommended Practice for Software Requirements Specifications
- Course notes: Software Engineering, Software Requirements Engineering, Database Systems

---

## 2. Overall Description

### 2.1 Product perspective
A stand-alone web application (PHP 8 + MySQL) that runs on a single server, e.g. XAMPP on the front-desk PC
or any shared hosting.

### 2.2 User classes
| Role | Description | Main goals |
|------|-------------|-----------|
| Admin (owner / front desk) | Full access | Register members, sell plans, record payments, check-in, see reports |
| Trainer | Staff member | See own classes and bookings, manage PT appointments, check members in |
| Member | Customer | See own plan, book/cancel classes, request PT sessions, see payments and visits |

### 2.3 Operating environment
- Server: PHP 8.0+ with PDO MySQL, MySQL 8 / MariaDB 10.4+
- Client: any modern browser (Chrome, Edge, Firefox), desktop or mobile

### 2.4 Constraints and assumptions
- One gym branch, currency PKR.
- Each member has at most one *active* subscription at a time.
- Staff verify online transfers manually before recording them.

---

## 3. Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-01 | The system shall authenticate users with e-mail and password and route them by role (admin / trainer / member). | High |
| FR-02 | Passwords shall be stored as salted hashes (bcrypt), never in plain text. | High |
| FR-03 | Admin shall register, edit, search, deactivate and delete members. | High |
| FR-04 | Admin shall create and edit membership plans (name, duration in months, price, availability). | High |
| FR-05 | Admin shall create a subscription for a member; the end date shall be calculated from the plan duration. | High |
| FR-06 | Creating a new subscription shall cancel the member's previous active subscription. | Medium |
| FR-07 | Subscriptions past their end date shall be marked *expired* automatically. | High |
| FR-08 | Admin shall record payments as *cash* or *online*; online payments require a transaction reference. | High |
| FR-09 | The system shall show whether each subscription is fully paid or how much is due. | Medium |
| FR-10 | Admin shall manage trainers (add, edit, remove). | High |
| FR-11 | Admin shall define weekly classes (name, trainer, day, time, duration, capacity, room). | High |
| FR-12 | A member with an active subscription shall book the next session of a class if seats are available. | High |
| FR-13 | The system shall prevent double booking of the same session and overbooking beyond capacity. | High |
| FR-14 | A member shall cancel their own booking. | Medium |
| FR-15 | A member shall request a PT appointment with a trainer at a future date/time. | Medium |
| FR-16 | The system shall reject a PT request that starts within 60 minutes of another session of the same trainer. | Medium |
| FR-17 | A trainer shall confirm, complete or cancel their appointments. | Medium |
| FR-18 | Admin/trainer shall check members in and out; check-in requires an active subscription. | High |
| FR-19 | Admin dashboard shall show active members, revenue this month, today's check-ins, subscriptions expiring in 7 days, 6-month revenue chart and recent payments. | High |
| FR-20 | Members shall see their plan, days left, visits in the last 30 days, upcoming bookings and payment history. | Medium |

---

## 4. Non-Functional Requirements

| ID | Category | Requirement |
|----|----------|-------------|
| NFR-01 | Security | All SQL uses prepared statements (PDO) to prevent SQL injection. |
| NFR-02 | Security | All output is HTML-escaped to prevent XSS. |
| NFR-03 | Security | All state-changing forms carry a CSRF token. |
| NFR-04 | Security | Pages check the user's role on the server side (HTTP 403 otherwise). |
| NFR-05 | Integrity | Foreign keys, unique and CHECK constraints protect data consistency; multi-step writes use transactions. |
| NFR-06 | Usability | A new front-desk user can register a member and sell a plan in under 2 minutes. |
| NFR-07 | Performance | Every page loads in < 1 s with 1,000 members on a normal PC (indexes on dates and status). |
| NFR-08 | Portability | Runs on XAMPP (Windows) and LAMP (Linux) without code changes; DB settings via config/env. |
| NFR-09 | Maintainability | Shared code in `includes/`, one page per module, consistent naming. |

---

## 5. Data Requirements
See [er-diagram.md](er-diagram.md) for the entity–relationship model and `database/schema.sql` for the full DDL.

## 6. Use cases and user stories
See [use-cases.md](use-cases.md) and [user-stories.md](user-stories.md).

## 7. Future work
- Real payment gateway integration (e.g. JazzCash / Easypaisa / Stripe) with webhooks
- E-mail/SMS reminders for expiring subscriptions
- QR-code self check-in
- Trainer availability calendar and member progress tracking (weight, body fat, PRs)
