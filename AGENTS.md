# Rank It – Project Guidelines

## Project Overview

Rank It is a daily ranking game where users have to arrange five items in the correct order. Each correct position gives 20 points, with a maximum of 100 points per game.

The website includes daily challenges, a leaderboard, user profiles, and an admin panel for managing challenges and users.

## Technologies

The frontend is made using HTML, CSS, and JavaScript.

For the next milestones, the project will also use:
- PHP with FlightPHP for the backend
- MySQL and PDO for the database
- REST API and JWT for authentication
- An external API for generating ranking challenges

Milestone 1 only includes the static frontend, project structure, and database planning.

## Project Structure

The project is divided into three main folders:

**frontend/** – Contains everything related to the website's appearance and interactions.
- `index.html` – Main HTML file
- `css/` – Styles
- `js/` – JavaScript code and sample data
- `views/` – HTML content for different pages
- `assets/` – Images, icons, and fonts

**backend/** – Prepared for future milestones.
- `routes/` – API routes
- `services/` – Application logic
- `dao/` – Database operations
- `middleware/` – Authentication and other checks
- `config/` – Configuration files
- `logs/` – Application logs

**docs/** – Contains the ERD and AI usage documentation.

## How the Website Works

Rank It is a Single Page Application (SPA). This means that users can navigate between pages without reloading the entire website.

JavaScript handles navigation and loads the content of different pages into the main HTML file.

For now, the website uses sample data. The database and backend will be connected in later milestones.

The same website works on desktop and mobile, with CSS adjusting the layout depending on screen size.

## Coding Rules

- Keep the code simple, organized, and easy to understand.
- Use clear names for variables and functions.
- Use camelCase for JavaScript and snake_case for database fields.
- Keep HTML, CSS, and JavaScript organized in their appropriate files.
- Avoid unnecessary libraries and complicated code.
- Only add comments when they are actually useful.
- Keep the current design, colors, and logo consistent.
- Make sure all pages work properly on desktop and mobile.
- Do not break the SPA navigation when adding or changing pages.
- Keep passwords and API keys out of GitHub.
- Test changes before committing them.
