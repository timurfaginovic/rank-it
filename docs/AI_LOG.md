# AI Usage Log

## Milestone 1 – October 2026

### AI Tools Used
- ChatGPT: Design exploration, generating visual elements, project planning and coding assistance.
- Codex: Frontend implementation, responsive design, debugging and code improvements.

### How AI Was Used

I initially used AI to generate visual elements and design concepts, which I then arranged and customized in Figma to create the desired Rank It interface.

After establishing the design, I used ChatGPT and Codex to help recreate it using HTML, CSS and JavaScript. Codex assisted with implementing the SPA structure, frontend pages, game interactions and responsive layouts.

I reviewed, tested and adjusted the generated code and design throughout development to achieve the intended result. AI was also used to assist with the initial project structure, ERD and documentation.

### Key Prompts
- Implement the static frontend according to the Milestone 1 requirements using simple HTML, CSS and JavaScript.
- Recreate my provided Figma design while maintaining a genuine single-page application.
- Improve mobile responsiveness without changing the desktop design.
- Review the code, identify issues and verify the Milestone 1 requirements.

### Issues and Corrections

1. Leaderboard loading issue

The Leaderboard initially failed to load through VS Code Live Server. After identifying the issue during testing, Codex helped trace it to the SVG markup in dynamically loaded views and corrected the implementation.

2. Mobile layout issues

Some admin tables and decorative elements caused horizontal overflow on smaller screens. These problems were identified during responsive testing and corrected through CSS adjustments.

### Final Review

I reviewed the frontend design, navigation and functionality, made adjustments to the implementation and checked the project against the Milestone 1 requirements.

## Milestone 2 – October 2026

### AI Tools Used
- Codex: PHP DAO development, debugging and testing.

### How AI Was Used

For Milestone 2, I used Codex to help implement the DAO layer based on the five entities and relationships I had already designed in Milestone 1.

Codex assisted with creating the PDO database connection and implementing the BaseDao and individual DAO classes.

I also used it to check the database operations, fix issues and review the implementation against the project requirements.

### Key Prompts
- Implement the existing database design in MySQL with five related tables and sample data.
- Create a reusable BaseDao and individual PHP DAO classes using PDO prepared statements.
- Test CRUD operations and check the implementation against the Milestone 2 requirements.

### Issues and Corrections

1. Sample password issue

One of the initial sample password hashes did not match the expected password. This was found during testing and corrected by generating new hashes.

2. DAO testing issue

An initial update test used the ID field instead of an editable field. The test was corrected to properly check how the DAO handles a record that does not exist.

### Final Review

The database was successfully imported into MySQL, and CRUD operations were tested for all five entities. The SQL structure, relationships, sample data and PHP classes were reviewed against the Milestone 2 requirements.

The existing frontend remained unchanged, and the backend is ready for the next milestone.