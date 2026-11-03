# Entity–Relationship Diagram

```mermaid
erDiagram
    USERS ||--o| MEMBERS : "has profile"
    USERS ||--o| TRAINERS : "has profile"
    MEMBERS ||--o{ SUBSCRIPTIONS : buys
    PLANS ||--o{ SUBSCRIPTIONS : "sold as"
    MEMBERS ||--o{ PAYMENTS : makes
    SUBSCRIPTIONS |o--o{ PAYMENTS : "paid by"
    USERS |o--o{ PAYMENTS : records
    TRAINERS ||--o{ CLASSES : leads
    CLASSES ||--o{ BOOKINGS : has
    MEMBERS ||--o{ BOOKINGS : makes
    MEMBERS ||--o{ APPOINTMENTS : requests
    TRAINERS ||--o{ APPOINTMENTS : runs
    MEMBERS ||--o{ ATTENDANCE : visits

    USERS {
        int id PK
        varchar name
        varchar email UK
        varchar phone
        varchar password_hash
        enum role "admin|trainer|member"
    }
    MEMBERS {
        int id PK
        int user_id FK
        enum gender
        date date_of_birth
        varchar address
        date join_date
        enum status "active|inactive"
    }
    TRAINERS {
        int id PK
        int user_id FK
        varchar specialization
        tinyint experience_years
    }
    PLANS {
        int id PK
        varchar name
        tinyint duration_months
        decimal price
        bool is_active
    }
    SUBSCRIPTIONS {
        int id PK
        int member_id FK
        int plan_id FK
        date start_date
        date end_date
        enum status "active|expired|cancelled"
    }
    CLASSES {
        int id PK
        int trainer_id FK
        varchar name
        enum day_of_week
        time start_time
        smallint capacity
    }
    BOOKINGS {
        int id PK
        int class_id FK
        int member_id FK
        date class_date
        enum status
    }
    APPOINTMENTS {
        int id PK
        int member_id FK
        int trainer_id FK
        datetime appointment_at
        enum status
    }
    PAYMENTS {
        int id PK
        int member_id FK
        int subscription_id FK
        decimal amount
        enum method "cash|online"
        varchar reference
        datetime paid_at
    }
    ATTENDANCE {
        int id PK
        int member_id FK
        datetime check_in
        datetime check_out
    }
```

## Normalisation notes
- Login data lives once in `users`; role-specific data is split into `members` / `trainers` (1:1) — no repeated columns.
- `subscriptions` resolves the M:N between members and plans and keeps history (3NF: price is read from the plan,
  the amount actually paid lives in `payments`).
- `bookings` resolves the M:N between members and class sessions; `UNIQUE (class_id, member_id, class_date)` prevents double booking.
- Indexes: `subscriptions(status, end_date)` for the expiring list, `payments(paid_at)` for revenue reports,
  `attendance(check_in)` for daily lists, `appointments(trainer_id, appointment_at)` for clash checks.
