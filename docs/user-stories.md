# User Stories & Acceptance Criteria

Format: *As a <role>, I want <goal> so that <benefit>.* Acceptance criteria use Given/When/Then.

## Admin / Front desk

**US-01 Register member** — As an admin, I want to register a new member with contact and emergency details so that the gym has their record.
- Given I am on *Members*, when I submit name + valid unique e-mail, then the member appears in the list with status *active*.
- Given the e-mail already exists, when I submit, then I see "That email is already registered."

**US-02 Sell a plan** — As an admin, I want to assign a plan to a member and take payment in one step so that the desk is fast.
- When I create a subscription with *Record payment now*, then a subscription and a payment linked to it are saved and the row shows *Paid*.

**US-03 See who is expiring** — As the owner, I want to see subscriptions ending within 7 days so that I can call members to renew.
- The dashboard card and table list only active subscriptions ending between today and today + 7.

**US-04 Revenue overview** — As the owner, I want monthly revenue for the last 6 months so that I can track the business.

**US-05 Record online transfer** — As an admin, I want to record an online payment with its reference so that I can match it to the bank statement.
- Given method = online and empty reference, then the system refuses with a clear message.

## Trainer

**US-06 My schedule** — As a trainer, I want to see my weekly classes and how many people are booked so that I can prepare.
**US-07 Handle PT requests** — As a trainer, I want to confirm or cancel PT requests so that members know if the session is on.
- Pending → Confirm/Cancel buttons; Confirmed → Complete/Cancel buttons.

## Member

**US-08 Book a class** — As a member, I want to book a seat in a class so that I am guaranteed a place.
- The *Book* button is disabled when the class is full; booking twice is not possible.
**US-09 Cancel booking** — As a member, I want to cancel a booking so that someone else can take my seat.
**US-10 Track my plan** — As a member, I want to see my plan, days left and visits so that I stay consistent.
**US-11 Request PT** — As a member, I want to request a personal training session with a chosen trainer.
**US-12 Payment history** — As a member, I want to see all my payments so that I have a record.

## Product backlog (prioritised)

| # | Story | Priority | Estimate (pts) | Status |
|---|-------|----------|----------------|--------|
| 1 | US-01 Register member | Must | 3 | Done |
| 2 | US-02 Sell a plan | Must | 5 | Done |
| 3 | US-08 Book a class | Must | 5 | Done |
| 4 | US-05 Record online transfer | Must | 2 | Done |
| 5 | US-03 Expiring list | Should | 2 | Done |
| 6 | US-04 Revenue overview | Should | 3 | Done |
| 7 | US-07 Handle PT requests | Should | 3 | Done |
| 8 | US-10 Track my plan | Should | 2 | Done |
| 9 | QR self check-in | Could | 8 | Backlog |
| 10 | SMS renewal reminders | Could | 5 | Backlog |
