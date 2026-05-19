# API Design

## Purpose
Define the routes and data flow used by the enrollment portal.

## Public Routes
- `GET /login` shows login page
- `POST /login` authenticates user
- `GET /register` shows registration page
- `POST /register` creates applicant account
- `POST /logout` ends user session

## Applicant Routes
- `GET /enrollment/dashboard` shows applicant dashboard
- `GET /enrollment/status` returns draft/application progress
- `GET /enroll` shows enrollment form
- `POST /enroll` submits final enrollment application
- `POST /enroll/draft` saves application draft
- `GET /enrollment/success` shows submission success
- `GET /enrollment/payment` shows payment page
- `POST /enrollment/payment` submits payment proof

## Data Models
- User
- EnrollmentApplicant
- Payment
- Student
- StudentAccount
- SchoolFee

## Response Patterns
- Page routes return Blade views.
- Draft/status routes may return JSON for progress updates.
- Form submissions redirect with success or validation errors.

## API Design Rules
- Validate every form request on the server.
- Keep uploaded file paths private through controlled storage links.
- Return simple JSON for autosave status.
- Use named routes to avoid hardcoded URLs in Blade templates.
