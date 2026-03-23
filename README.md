# Louvr - Lead Management for UBlinds

## Overview
Lead management platform for UBlinds (ublinds.com.au). Tracks leads from capture through to won/lost, with sales rep assignment, O365 calendar booking, and SMS notifications.

## Client
- **Company:** UBlinds (ublinds.com.au)
- **Industry:** Window furnishings - blinds, shutters, curtains, awnings
- **Users:** ~30 sales reps + admin

## Tech Stack
- PHP, MySQL, Tailwind CSS
- Azure App Service hosting
- Microsoft Graph API (O365 calendar integration)
- SMS provider (TBD - Twilio/MessageMedia/BurstSMS)

## Features

### Lead Capture
- Manual entry (phone leads)
- Web form integration (ublinds.com.au)
- Fields: name, address, phone, email, products of interest, property type, source, notes

### Pipeline
- Stages: New → Assigned → Booked → Quoted → Won/Lost
- Stage history with timestamps
- Notes/activity log per lead

### Sales Rep Management
- 30 reps with individual logins
- Admin assigns leads to reps
- Rep dashboard showing their leads
- Performance tracking

### Calendar Integration
- Books appointment in rep's O365 calendar via Microsoft Graph
- Date, time, duration, location (customer address)

### SMS Notifications
- On booking: "Your appointment with [rep] is booked for [date/time]"
- Day before: reminder SMS "See you tomorrow at [time]"
- Cron job for day-before reminders

### Dashboard
- Lead pipeline overview (counts per stage)
- Rep performance (leads assigned, conversion rate)
- Source tracking (web form vs phone)
- Time-to-contact metrics

## Status
Parked - spec phase. Not yet built.
