# Habit Tracker

A PHP and MySQL habit tracking web app where users can create habits, mark them complete once per day, earn XP, level up, and review their progress history. The project also includes an optional Google Calendar integration that reads calendar busyness and stores a daily multiplier value.

# Link to Website

https://cise.ufl.edu/~kaleb.zhai/cis4930/group/

## Overview

This project was built as a gamified habit tracker. Instead of only checking habits off a list, users gain XP for completing habits and progress through levels over time.

The app supports:
- user registration and login
- habit creation, editing, and deletion
- daily habit completion tracking
- XP rewards based on habit difficulty
- level progression based on total XP
- history and activity stats
- optional Google Calendar busyness syncing

## Features

### Core habit tracking
- Create habits with a name, description, and difficulty weight
- Edit and delete existing habits
- Mark a habit as completed once per day
- Prevent duplicate completion on the same day

### Gamification
- Easy, Medium, and Hard habit difficulty levels
- XP rewards based on habit weight
- Level progression system with scaling XP requirements
- Progress bar showing XP toward the next level

### History page
- View habit completion logs
- See total logs, completed logs, XP earned, and active days
- Filter history by all time, last 7 days, or last 30 days

### Google Calendar integration
- Connect a Google account with read-only Calendar access
- Pull busy time from the user's primary calendar
- Store daily busy minutes, busy blocks, and a multiplier value
- Show busyness information on the dashboard

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **UI framework:** Bootstrap 5
- **External API:** Google Calendar FreeBusy API

## Project Structure

```text
Habit-Tracker-main/
├── README.md
└── group/
    ├── index.php                  # Login page
    ├── register.php               # Registration page
    ├── dashboard.php              # Main dashboard
    ├── manage_habit.php           # Add/edit habits
    ├── complete_habit.php         # Marks a habit complete via AJAX
    ├── history.php                # Habit history and stats
    ├── connect_google.php         # Starts Google OAuth flow
    ├── calendar_busyness.php      # Calendar syncing and multiplier logic
    ├── logout.php                 # Ends user session
    ├── db.php                     # Loads database config and connects
    ├── testdb.php                 # Simple database connection test
    ├── includes/
    │   ├── dashboard_header.php
    │   ├── dashboard_level_card.php
    │   ├── dashboard_busyness_card.php
    │   ├── dashboard_habit_list.php
    │   ├── dashboard_helpers.php
    │   └── delete_modal.php
    ├── js/
    │   └── dashboard.js
    └── styles/
        └── styles.css
```

## How the XP System Works

### Habit difficulty
- **Easy** = weight `1` = `10 XP`
- **Medium** = weight `2` = `20 XP`
- **Hard** = weight `3` = `30 XP`

### Level progression
The project uses a scaling XP curve:
- base XP required for level 1 to 2 is `100`
- each next level uses a growth rate of `1.20`

The helper logic is implemented in:
- `group/includes/dashboard_helpers.php`
- `group/complete_habit.php`

## Expected Database Tables

Based on the current code, the application expects these tables:

- `users`
- `habits`
- `habit_logs`
- `google_calendar_tokens`
- `calendar_busyness`

### Main columns used by the app

#### `users`
- `user_id`
- `name`
- `email`
- `password`
- `xp`
- `level`

#### `habits`
- `habit_id`
- `user_id`
- `habit_name`
- `description`
- `weight`
- `is_active`
- `created_at`
- `last_completed_date`

#### `habit_logs`
- `log_id`
- `habit_id`
- `log_date`
- `completed`
- `xp_earned`
- `busyness_multiplier`

#### `google_calendar_tokens`
- `user_id`
- `access_token`
- `refresh_token`
- `expires_at`

#### `calendar_busyness`
- `user_id`
- `busyness_date`
- `busy_minutes`
- `busy_blocks`
- `multiplier`
- `updated_at`

## Secret / Private Files Not Included

This repository is missing secret files on purpose.

### 1. Database config
`group/db.php` loads credentials from:

```ini
../../../db_config.ini
```

Example format:

```ini
servername = localhost
username = your_mysql_username
password = your_mysql_password
dbname = your_database_name
port = 3306
```

### 2. Google OAuth config
Google-related files require a `google_config.php` file that is not included in the repository.

Expected helper functions:

```php
<?php
function googleClientId() {
    return "YOUR_GOOGLE_CLIENT_ID";
}

function googleClientSecret() {
    return "YOUR_GOOGLE_CLIENT_SECRET";
}

function googleRedirectUri() {
    return "YOUR_GOOGLE_REDIRECT_URI";
}
?>
```

## Setup Instructions

### 1. Clone or download the project
Place the project in your PHP web server directory.

### 2. Create the database
Create a MySQL database and the required tables listed above.

### 3. Add the database config file
Create `db_config.ini` outside the public web folder and update the values to match your MySQL setup.

### 4. Add Google config if using Calendar sync
Create `google_config.php` with your Google OAuth credentials.

### 5. Make sure PHP has these enabled
- `mysqli`
- `session`
- `openssl`
- `json`

### 6. Run the project
Open the app through your local or hosted PHP server and start from:

```text
group/index.php
```

## Main Pages

- **Login:** `group/index.php`
- **Register:** `group/register.php`
- **Dashboard:** `group/dashboard.php`
- **Add/Edit Habit:** `group/manage_habit.php`
- **History:** `group/history.php`
- **Logout:** `group/logout.php`

## Notes About the Current Codebase

- The dashboard displays a busyness-adjusted XP value for habits.
- In the current `complete_habit.php` logic, the awarded XP is still the base XP from the habit weight, while the busyness multiplier is stored in the log and shown in the UI.
- `settings.php` is referenced in the dashboard header but is not present in this upload.
- The Google OAuth start route exists (`connect_google.php`), but the callback handling file is not included in this upload.

## Future Improvements

Some possible next steps for the project:
- apply the calendar multiplier directly to awarded XP
- add streak tracking
- add habit categories or tags
- add charts for progress trends
- add account settings page
- improve validation and error handling
- add CSRF protection and stronger session hardening

## Author Note

This repository does not include private credentials or secret configuration files. If you are running the project yourself, create local config files with your own values and keep them out of version control.
