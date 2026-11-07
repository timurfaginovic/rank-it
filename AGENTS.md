# Rank It - Project Guidelines

## Project Overview

Rank It is a daily ranking game where players arrange five items in the correct order. Each correct position gives 20 points, with a maximum score of 100.

The application includes daily challenges, leaderboards, player profiles, and an admin panel for managing challenges and game content.

## Technologies

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP, FlightPHP, PDO
- **Database:** MySQL
- **API:** REST API, OpenAPI/Swagger, World Bank API

The frontend is built as a Single Page Application (SPA), allowing users to navigate between pages without reloading the website.

## Project Structure

The project is divided into three main folders.

### frontend/

Contains the website interface, including:

- `index.html` - Main page
- `css/` - Website styling
- `js/` - JavaScript functionality and sample data
- `views/` - Individual pages
- `assets/` - Images, icons, and fonts

### backend/

Contains the database connection, application logic, and API.

- `config/` - Database settings
- `database/` - SQL database and testing files
- `dao/` - Database operations
- `services/` - Business logic and validation
- `routes/` - API endpoints
- `public/` - Backend entry point and Swagger documentation
- `middleware/` - Reserved for authentication and other middleware
- `logs/` - Application logs

### docs/

Contains the Entity Relationship Diagram (ERD) and project documentation.

## Database

Rank It uses five related tables:

1. `users`
2. `challenges`
3. `challenge_items`
4. `attempts`
5. `attempt_items`

The database structure is documented in `docs/ERD.md`, and the SQL file is located at `backend/database/rank_it.sql`.

The database connection uses PHP PDO. Each table has its own DAO class, while `BaseDao.php` contains common CRUD methods.

Database queries should use prepared statements. Private database credentials are stored in `backend/config/config.php`, which is excluded from Git. An example configuration is provided separately.

## Backend and API

Milestone 3 introduces the service layer and REST API using FlightPHP.

The backend follows a simple structure:

**Routes → Services → DAOs → Database**

Routes receive requests and return JSON responses. Services handle validation and game rules, while DAOs communicate with MySQL.

The API supports CRUD operations for all five entities. This includes managing challenges, users, attempts, and their related records.

The main game rules include validating ranking positions, preventing duplicate attempts, protecting completed results, and calculating scores.

The project also uses the World Bank API to prepare population-based ranking challenges. API endpoints are documented using OpenAPI and can be tested through Swagger.

## Coding Guidelines

- Keep the code simple, readable, and organized.
- Use clear names for variables, functions, and classes.
- Follow existing naming conventions, including camelCase in JavaScript and snake_case for database fields.
- Keep frontend and backend code separate.
- Put business logic in services rather than routes.
- Reuse existing methods when possible.
- Validate user input and handle errors appropriately.
- Use comments when they help explain the code.
- Avoid unnecessary libraries or complicated solutions.
- Keep passwords, configuration files, and other sensitive information out of Git.
- Test changes before committing them.
- Preserve existing functionality when adding new features.

## Current Project Status

**Milestone 1:** Created the frontend, SPA navigation, responsive pages, and initial database design.

**Milestone 2:** Implemented the MySQL database, five DAO classes, CRUD operations, and database testing.

**Milestone 3:** Added services with business logic, FlightPHP routes, JSON responses, OpenAPI/Swagger documentation, and World Bank API integration.

The frontend currently uses sample data. Authentication, user permissions, and the connection between the frontend and backend are planned for Milestone 4.

## Development Guidelines

New functionality should follow the existing project structure and remain consistent with the database and application design.

Changes should be tested to make sure existing features continue working correctly. Avoid modifying unrelated files or adding functionality outside the current milestone.

Keep the README and other required documentation updated when significant changes are made.