# Class / Module Diagram

The application is written in procedural PHP with a thin shared layer. The diagram shows the logical
design (each page is a controller + view for one module) and the domain model it works on.

```mermaid
classDiagram
    direction LR
    class Database {
        <<includes/db.php>>
        +db() PDO
        +q(sql, params) PDOStatement
    }
    class Auth {
        <<includes/auth.php>>
        +current_user() array
        +attempt_login(email, password) bool
        +require_role(roles...) array
        +logout() void
    }
    class Helpers {
        <<includes/helpers.php>>
        +e(value) string
        +csrf_field() string
        +verify_csrf() void
        +flash(msg, type)
        +money(amount) string
        +badge(status) string
    }
    class User {
        +int id
        +string name
        +string email
        +Role role
    }
    class Member {
        +date joinDate
        +Status status
        +activeSubscription() Subscription
    }
    class Trainer {
        +string specialization
        +int experienceYears
    }
    class Plan {
        +string name
        +int durationMonths
        +decimal price
    }
    class Subscription {
        +date startDate
        +date endDate
        +status
        +isPaid() bool
    }
    class GymClass {
        +string name
        +string dayOfWeek
        +time startTime
        +int capacity
        +nextSessionDate() date
        +seatsLeft(date) int
    }
    class Booking {
        +date classDate
        +status
    }
    class Appointment {
        +datetime at
        +status
        +hasClash() bool
    }
    class Payment {
        +decimal amount
        +Method method
        +string reference
    }
    class Attendance {
        +datetime checkIn
        +datetime checkOut
        +duration() int
    }

    User <|-- Member
    User <|-- Trainer
    Member "1" --> "*" Subscription
    Plan "1" --> "*" Subscription
    Member "1" --> "*" Payment
    Subscription "1" --> "*" Payment
    Trainer "1" --> "*" GymClass
    GymClass "1" --> "*" Booking
    Member "1" --> "*" Booking
    Member "1" --> "*" Appointment
    Trainer "1" --> "*" Appointment
    Member "1" --> "*" Attendance
    Auth ..> Database
    Auth ..> Helpers
```

## Page → module map
| Page | Roles | Responsibility |
|------|-------|----------------|
| `index.php` | public | Login form |
| `dashboard.php` | all | Role-specific dashboard |
| `members.php` | admin | Member CRUD + search |
| `trainers.php` | admin | Trainer CRUD |
| `plans.php` | admin | Plan CRUD |
| `subscriptions.php` | admin | Sell/cancel plans, auto-expire |
| `classes.php` | all | Schedule, booking, add class |
| `appointments.php` | all | PT requests and status workflow |
| `payments.php` | admin, member | Record and list payments |
| `attendance.php` | all | Check-in/out, visit history |
