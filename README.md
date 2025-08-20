# Laravel Test — Google Sheets Sync Demo

A Laravel application demonstrating CRUD for simple records and a two-way integration with Google Sheets:
- Sync only Allowed records to a Google Sheet (sheet tab name: "Лист1").
- Pull comments from the Google Sheet back into the local DB (by record ID).
- OAuth 2.0 flow to store a reusable token (token.json) for Google API calls.

Current date/time: 2025-08-20 11:44

## Features
- Records CRUD (title, description, status, optional comment)
- Mass generation and truncation utilities for testing
- Google OAuth helper routes to generate and store token.json
- Console commands:
  - google:sync — push Allowed records to Google Sheets and update comments from the sheet
  - google:fetch [count] — read IDs and comments from the sheet and print to console
- Scheduler configured to run google:sync every minute

## Tech
- Laravel
- Google API PHP Client (Sheets API)
- PHP 8+, Composer, MySQL/SQLite (your choice)

## Getting Started
1) Install dependencies
- composer install
- npm install (optional if you work with assets)

2) Configure environment
- cp .env.example .env
- Set DB connection in .env
- php artisan key:generate

3) Run migrations
- php artisan migrate

Note about comments column
- The code uses a comment attribute on Record. Ensure your records table includes a nullable string column named comment if you plan to sync comments from Google Sheets. Example migration snippet:
  $table->string('comment')->nullable();

4) Create Google credentials
- Create directory: storage/app/google
- Put your Google OAuth client credentials file as storage/app/google/credentials.json
  - Enable Google Sheets API for your project in Google Cloud Console

5) Set the target Google Sheet URL
- The app stores the sheet URL in storage/app/config/sheet_url.txt
- You can set it from the UI (POST /set-url) or manually create the file and paste the full Google Sheet URL
- The app extracts the Spreadsheet ID from the URL and uses sheet/tab name "Лист1"

6) Authorize with Google (OAuth)
- Visit /authorize in your browser, sign in and grant access
- After redirection to /oauth2callback the token will be saved as storage/app/google/token.json

7) Run the app
- php artisan serve
- Visit http://localhost:8000/

## Usage
UI routes
- GET / — list records (records.index)
- POST /records — create a record
- DELETE /records/{id} — delete a record
- POST /generate — generate 1000 sample records via factory
- POST /truncate — truncate records table
- POST /set-url — set and persist the Google Sheet URL

OAuth routes
- GET /authorize — start OAuth flow (redirects to Google)
- GET /oauth2callback — callback; saves storage/app/google/token.json

Utility route
- GET /fetch/{count?} — runs google:fetch and returns its console output as HTML

Artisan commands
- php artisan google:sync
  - Pushes only records with status = Allowed to the sheet (tab: "Лист1")
  - Reads existing rows to update local comments by matching on ID
  - Clears the sheet and writes header: [id, title, description, status, comment]

- php artisan google:fetch {count?}
  - Reads rows (A1:Z1000) from the sheet (tab: "Лист1"), shows: ID | Comment
  - Optional count limits how many data rows (after the header) are read

## Scheduler
- The scheduler is set to run google:sync every minute (see app/Console/Kernel.php)
- To run the scheduler locally, use: php artisan schedule:work

## Storage Layout
- storage/app/google/credentials.json — Google OAuth client credentials
- storage/app/google/token.json — Saved OAuth token after /authorize flow
- storage/app/config/sheet_url.txt — Text file with the full Google Sheet URL

## Notes & Assumptions
- Sheet tab name is hardcoded as "Лист1"; adjust in code if your sheet uses a different name.
- The app extracts the Spreadsheet ID from a standard Google Sheet URL (…/d/<ID>/…)
- Timezone configured in config/app.php: Asia/Phnom_Penh

## License
MIT
