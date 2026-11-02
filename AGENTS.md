# Rank It – Project Guidelines

## Project Overview

Rank It is a daily ranking game where players arrange five items in the correct order. Each correct position gives 20 points, with a maximum score of 100.

The website includes daily challenges, leaderboards, user profiles, and an admin panel for managing game content.

## Technologies

- Frontend: HTML, CSS, JavaScript
- Backend: PHP, PDO, FlightPHP (planned)
- Database: MySQL
- Other: REST API, JWT, external API (planned)

Milestone 1 covers the frontend and database planning. Milestone 2 adds the MySQL database and PHP DAO classes.

## Project Structure

The project is organized into three main folders.

### frontend/

Contains the website interface and functionality.

- `index.html` – Main HTML file
- `css/` – Styles
- `js/` – JavaScript and sample data
- `views/` – Website pages
- `assets/` – Images, icons, and fonts

### backend/

Contains the database connection and backend code.

- `config/` – Database configuration
- `database/` – SQL file and database testing scripts
- `dao/` – Database operations
- `services/` – Business logic
- `routes/` – API endpoints
- `middleware/` – Authentication and request handling
- `logs/` – Application logs

The database and DAO layer are implemented in Milestone 2. The remaining backend functionality will be added in later milestones.

### docs/

Contains the database diagram and required project documentation.

## How the Website Works

Rank It uses a Single Page Application (SPA) structure. JavaScript handles navigation between pages without reloading the entire website.

The frontend currently uses sample data and is not yet connected to the database.

The layout is responsive and works on desktop and mobile devices.

## Coding Guidelines

- Keep the code simple and easy to understand.
- Use clear names for variables, functions, and classes.
- Use camelCase in JavaScript and snake_case for database fields.
- Keep HTML, CSS, and JavaScript in their appropriate files.
- Avoid unnecessary libraries and complicated solutions.
- Only add comments when needed.
- Keep the existing design and navigation consistent.
- Make sure changes work on desktop and mobile.
- Keep passwords and private configuration files out of Git.
- Test new functionality before committing.

## Database and DAO Guidelines

The database contains five tables: users, challenges, challenge_items, attempts, and attempt_items.

The database structure is documented in `docs/ERD.md`, and the SQL file is located at `backend/database/rank_it.sql`.

The PHP DAO classes are stored in `backend/dao/`.

- `Database.php` handles the MySQL connection using PDO.
- `BaseDao.php` contains the shared CRUD methods.
- Each database table has its own DAO class.

Use PDO prepared statements for database queries. Keep table and column names controlled by the DAO classes, and validate which fields can be modified.

The actual database credentials belong in `backend/config/config.php`, which is excluded from Git. The example configuration is stored in `config.example.php`.

Use the verification script in `backend/database/verify.php` to test database operations without permanently changing the sample data.

Do not delete or overwrite existing databases without permission.

## Future Development

Milestone 3 will introduce services, FlightPHP routes, Swagger documentation, and external API integration.

Milestone 4 will add authentication, user permissions, middleware, logging, and frontend-backend integration.

Milestone 5 will focus on final testing, validation, security, and deployment.

Keep the code organized so future functionality can be added without breaking existing features.

Update the required project documentation when changes are made, including `docs/AI_LOG.md` where applicable.