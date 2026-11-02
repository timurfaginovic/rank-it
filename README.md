# Rank It – Daily Ranking Challenge

Rank It is a daily ranking puzzle game where players arrange five items in the correct order. Each correct position gives 20 points, with a maximum of 100 points per challenge.

The game includes daily challenges, leaderboards, player profiles, and an admin panel for managing challenges.

## Technologies

- Frontend: HTML, CSS, JavaScript
- Backend: PHP, PDO (FlightPHP planned)
- Database: MySQL
- Other: REST API, JWT, external API (planned)

## Features

- Daily ranking challenges
- Drag-and-drop and button-based ordering
- Score calculation and results
- Daily, weekly, and all-time leaderboards
- Player profiles and challenge history
- Login and registration pages
- Admin panel
- Responsive design

The frontend currently uses sample data. Backend integration and authentication will be added in later milestones.

## Database

The database consists of five tables:

- Users – player and admin accounts
- Challenges – daily ranking challenges
- Challenge Items – items that belong to each challenge
- Attempts – player attempts and scores
- Attempt Items – submitted answers

The tables are connected using primary and foreign keys.

The database diagram can be found in [docs/ERD.md](docs/ERD.md).

## Running the Project

1. Open the project in VS Code.
2. Install the Live Server extension.
3. Open `frontend/index.html` with Live Server.

The frontend works as a single-page application, so navigation does not reload the entire page.

## Milestone 2 – Database and DAO

Milestone 2 adds the MySQL database and PHP DAO classes for accessing and managing data.

### Database Setup

The project requires MySQL and PHP with PDO MySQL enabled.

First, import the SQL file from the project folder:

```bash
/usr/local/mysql/bin/mysql -u root -p < backend/database/rank_it.sql
```

The SQL file creates the `rank_it` database and includes the tables and sample data. It should be imported into a new or empty database.

Next, copy `backend/config/config.example.php` to `backend/config/config.php` and enter your local MySQL connection details. The actual configuration file is excluded from Git.

To test the database and DAO classes, run:

```bash
/Applications/XAMPP/xamppfiles/bin/php backend/database/verify.php
```

### DAO Classes

The backend includes a `Database.php` class for connecting to MySQL and a reusable `BaseDao.php` class containing the basic CRUD operations.

Each table also has its own DAO class:

- UserDao.php
- ChallengeDao.php
- ChallengeItemDao.php
- AttemptDao.php
- AttemptItemDao.php

These classes handle creating, reading, updating, and deleting records using PDO prepared statements.

The database includes sample users, challenges, items, and player attempts. All five DAO classes were tested successfully, including database relationships and constraints.

## Project Progress

### Milestone 1 – Completed

Created the frontend SPA with the game pages, leaderboards, profiles, login and registration forms, and admin panel using sample data.

### Milestone 2 – Completed

Created the MySQL database with five related tables, sample data, and PHP DAO classes supporting CRUD operations.

### Milestone 3 – Planned

Business logic, FlightPHP REST API, Swagger documentation, and external API integration.

### Milestone 4 – Planned

JWT authentication, user permissions, middleware, logging, and frontend-backend integration.

### Milestone 5 – Planned

Final testing, validation, security improvements, and deployment.