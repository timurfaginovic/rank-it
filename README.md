# Rank It – Daily Ranking Challenge

Rank It is a daily ranking puzzle game where players arrange five items in the correct order. Each correct position gives 20 points, with a maximum of 100 points per challenge.

The game includes daily challenges, leaderboards, player profiles, and an admin panel for managing challenges.

## Technologies

- Frontend: HTML, CSS, JavaScript
- Backend: PHP, PDO and FlightPHP
- Database: MySQL
- Other: OpenAPI/Swagger and World Bank API; JWT planned for M4

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

A user can submit many attempts. Each challenge has its own items and receives attempts. Each attempt stores the player's selected order in attempt items, which reference the challenge items.

The database diagram can be found in [docs/ERD.md](docs/ERD.md).

## Running the Project

1. Open `frontend/` in VS Code so Live Server serves only the frontend directory.
2. Install the Live Server extension.
3. Open `frontend/index.html` with Live Server.

The frontend works as a single-page application, so navigation does not reload the entire page.

## Milestone 2 – Database and DAO

Milestone 2 adds the MySQL database and PHP DAO classes for accessing and managing data.

### Database Setup

The project requires MySQL and PHP with PDO MySQL enabled.

First, import the SQL file from the project folder:

```bash
/usr/local/mysql/bin/mysql --protocol=TCP -h 127.0.0.1 -P 3306 -u root -p < backend/database/rank_it.sql
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

## Milestone 3 - Backend API

Milestone 3 adds the service layer, FlightPHP routes, OpenAPI documentation, and external API integration.

### Services and Routes

Each of the five database entities has its own service and CRUD endpoints. Services handle validation and game rules, while routes process requests and return JSON responses.

The backend also handles ranking submissions, calculates scores, and checks that challenges and attempts follow the game rules.

### OpenAPI and Swagger

All API endpoints are documented using OpenAPI and can be viewed and tested through Swagger.

Swagger is available locally at `http://127.0.0.1:8000/docs/`.

### External API

The project uses the World Bank API to generate population-based ranking challenges. It retrieves population data for five countries and prepares them in the correct order.

The integration also handles connection errors, timeouts, and missing data.

### Backend Setup

After setting up the database, install the PHP dependencies using Composer and start the local backend server.

The backend uses FlightPHP and requires PHP with PDO MySQL and cURL enabled.

### Testing

The backend was tested for CRUD operations, validation, scoring, database relationships, and external API errors.

All five services and 25 CRUD operations were verified successfully. The existing frontend and database structure remain unchanged.