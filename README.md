# Rank It – Daily Ranking Challenge

Rank It is a daily ranking puzzle game where players arrange five items in the correct order. Each correct position earns 20 points, with a maximum of 100 points per challenge.

The website includes daily challenges, leaderboards, player profiles, and an admin panel for managing game content.

## Technologies

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP with FlightPHP (planned)
- **Database:** MySQL with PDO (planned)
- **Other:** REST API, JWT authentication, external API integration (planned)

## Features

- Daily ranking challenges with five items
- Drag-and-drop and button-based item ordering
- Score calculation and results
- Daily, weekly, and all-time leaderboards
- Player profiles and challenge history
- Login and registration pages
- Admin panel for managing challenges, users, and results
- Responsive design for desktop and mobile

Milestone 1 includes the static SPA frontend with sample data. Backend functionality will be added in later milestones.

## Database Structure

The database is planned around five entities:

| Entity | Description |
|---|---|
| Users | Player and admin accounts |
| Challenges | Daily ranking puzzles |
| Challenge Items | Items belonging to each challenge |
| Attempts | Player scores for individual challenges |
| Attempt Items | Answers submitted for each attempt |

A user can have multiple attempts, each challenge contains multiple items and attempts, and each attempt contains multiple submitted items.

The database diagram is available in [docs/ERD.md](docs/ERD.md).

## User Roles

**User:** Play daily challenges, view results, check leaderboards, and access personal history.

**Admin:** Manage users, challenges, challenge items, and player attempts.

Authentication and role permissions will be implemented in later milestones.

## External API

The planned external API will generate suggestions for new ranking challenges. Admins will be able to review and edit generated content before publishing it.

## Running the Project

1. Open the repository in VS Code.
2. Install the Live Server extension if needed.
3. Open `frontend/index.html` using **Open with Live Server**.
4. The website will open in your browser.

The frontend uses JavaScript-based SPA navigation, allowing users to switch between pages without full-page reloads.

## Future Development

The next milestones will include the MySQL database, PHP backend, authentication, API integration, and deployment.

Database import instructions, configuration setup, test accounts, Swagger documentation, and the deployed website link will be added when those features are available.