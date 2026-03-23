# Louvr - Lead Management for UBlinds

## Overview
Lead management and dispatch platform for UBlinds (ublinds.com.au). Tracks leads from capture through to won/lost, with sales rep dispatch, O365 calendar integration, and SMS notifications.

## Client
- **Company:** UBlinds (ublinds.com.au)
- **Industry:** Window furnishings - blinds, shutters, curtains, awnings
- **Users:** ~30 sales reps + admin
- **Website:** https://ublinds.com.au/

## Tech Stack
- PHP 8.2+ with SQLite (local dev) / MySQL (Azure production)
- Tailwind CSS via CDN
- Microsoft Graph API (O365 calendar - application permissions)
- SMS provider (TBD - Twilio/MessageMedia/BurstSMS)
- Azure App Service hosting

## Architecture
```
public/index.php          Single entry point, routing
src/
  Router.php              URL routing
  Database.php            PDO wrapper (SQLite/MySQL portable)
  Auth.php                Session auth + RBAC
  Schema.php              DB migrations + seeds
  Controllers/            One per feature
  Models/                 Data access layer
  Middleware/             CSRF, rate limiting
templates/                PHP views
  layouts/                Base layout
config/                   App configuration
data/                     SQLite DB (local dev)
```

## Security
- CSRF tokens on all forms
- Bcrypt password hashing
- Prepared statements only (zero SQL concatenation)
- Rate limiting on login (brute force protection)
- Session hardening (httponly, secure, regenerate on login)
- XSS prevention (htmlspecialchars on all output)
- Role-based access control (admin vs rep)
- API key auth for web form endpoint
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options)

## Features

### Lead Capture
- Manual entry (phone leads, walk-ins)
- API endpoint: POST /api/leads (X-API-Key auth) for website integration
- Fields: name, email, phone, address, suburb, state, postcode, property type, products, source, notes
- All field options configurable in Settings

### Pipeline
- Configurable stages (default: New > Assigned > Booked > Quoted > Won > Lost)
- Stage history with timestamps
- Notes/activity log per lead
- Colour coded throughout UI

### Dispatch Board (admin)
- Week and day view with all reps as rows
- Drag and drop leads onto time slots
- Visual availability - see who's free, who's packed
- Colour coded by lead status/product type
- Click slot to book, drag to reschedule
- Unassigned leads sidebar - drag onto a rep's row to assign + book
- Filter by rep, date range
- Conflict detection (double-booking warning)

### Rep View
- Only sees their own assigned leads
- Today's appointments front and centre
- Upcoming appointments list
- Lead detail with status updates and notes
- Cannot see other reps' data

### Calendar Integration (O365)
- Azure AD app registration with application permissions (Calendars.ReadWrite)
- No per-user OAuth - admin grants consent once
- Match rep email in Louvr to O365 mailbox
- Read rep's calendar via Graph API (real-time, no sync)
- Create appointments directly in rep's O365 calendar
- Appointment shows customer name, address, products, notes

### SMS Notifications (Phase 2)
- On booking: "Your appointment with [rep] is booked for [date/time] at [address]"
- Day before: "See you tomorrow at [time]. [Rep] from UBlinds will be visiting."
- Cron job for day-before reminders
- Provider TBD

### Sales Rep Management
- Admin CRUD for reps (add, edit, activate/deactivate)
- 30 reps with individual logins
- Performance tracking (leads assigned, conversion rate, revenue)

### Dashboard
- Pipeline overview cards (count per stage)
- Recent leads
- Rep performance leaderboard
- Source tracking (web form vs phone vs referral)
- Conversion rates
- Revenue tracking (quoted vs won)

### Settings (admin)
- Company info (name, phone, email)
- Lead sources (configurable list)
- Property types (configurable list)
- Products (configurable list)
- Pipeline statuses (configurable list)
- Default appointment duration
- O365 integration config (tenant ID, client ID, client secret)

### API
- POST /api/leads - create lead from web form (X-API-Key auth)
- Returns JSON with lead ID and status

## Run (local dev)
```bash
cd public && php -S 0.0.0.0:8080
```
- Auto-creates SQLite DB on first run
- Seeds admin user: admin@louvr.app / LouvR2026!
- Seeds sample reps and leads for demo

## Status
- [x] Core app (auth, routing, database, security)
- [x] Lead CRUD with pipeline
- [x] Rep management
- [x] Dashboard
- [x] API endpoint
- [x] Configurable options (settings)
- [ ] Dispatch board (drag & drop)
- [ ] O365 calendar integration
- [ ] SMS notifications
- [ ] Azure deployment
