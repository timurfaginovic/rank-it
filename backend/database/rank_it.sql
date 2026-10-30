CREATE DATABASE IF NOT EXISTS rank_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rank_it;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE challenges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    challenge_date DATE NOT NULL,
    status ENUM('Draft', 'Pending', 'Published') NOT NULL DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE challenge_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT UNSIGNED NOT NULL,
    item_text VARCHAR(100) NOT NULL,
    correct_position TINYINT UNSIGNED NOT NULL,
    CONSTRAINT uq_challenge_position UNIQUE (challenge_id, correct_position),
    CONSTRAINT chk_correct_position CHECK (correct_position BETWEEN 1 AND 5),
    CONSTRAINT fk_item_challenge FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    challenge_id INT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL,
    CONSTRAINT uq_user_challenge UNIQUE (user_id, challenge_id),
    CONSTRAINT chk_score CHECK (score IN (0, 20, 40, 60, 100)),
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_attempt_challenge FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attempt_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    challenge_item_id INT UNSIGNED NOT NULL,
    selected_position TINYINT UNSIGNED NOT NULL,
    CONSTRAINT uq_attempt_position UNIQUE (attempt_id, selected_position),
    CONSTRAINT uq_attempt_item UNIQUE (attempt_id, challenge_item_id),
    CONSTRAINT chk_selected_position CHECK (selected_position BETWEEN 1 AND 5),
    CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id) REFERENCES attempts(id)
        ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT fk_answer_item FOREIGN KEY (challenge_item_id) REFERENCES challenge_items(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;

INSERT INTO users (id, name, email, password_hash, role) VALUES
(1, 'Timur', 'timur@example.com', '$2y$10$67hJaJPrDyLdfq5zVwhSSe6d.EwXLM1eYCN4EJ9IQy7jj9F1KEAIG', 'user'),
(2, 'Almina', 'almina@example.com', '$2y$10$67hJaJPrDyLdfq5zVwhSSe6d.EwXLM1eYCN4EJ9IQy7jj9F1KEAIG', 'user'),
(3, 'Admin', 'admin@example.com', '$2y$10$67hJaJPrDyLdfq5zVwhSSe6d.EwXLM1eYCN4EJ9IQy7jj9F1KEAIG', 'admin');

INSERT INTO challenges (id, title, description, challenge_date, status) VALUES
(1, 'Largest countries by area', 'Rank by total area, largest first.', '2026-10-30', 'Published'),
(2, 'Planets by distance from the Sun', 'Rank these planets from closest to farthest from the Sun.', '2026-10-31', 'Pending'),
(3, 'Numbers in order', 'Rank the numbers from smallest to largest.', '2026-11-01', 'Draft');

INSERT INTO challenge_items (id, challenge_id, item_text, correct_position) VALUES
(1, 1, 'Russia', 1), (2, 1, 'Canada', 2), (3, 1, 'Brazil', 3),
(4, 1, 'Australia', 4), (5, 1, 'India', 5),
(6, 2, 'Mercury', 1), (7, 2, 'Venus', 2), (8, 2, 'Earth', 3),
(9, 2, 'Mars', 4), (10, 2, 'Jupiter', 5),
(11, 3, '10', 1), (12, 3, '20', 2), (13, 3, '30', 3),
(14, 3, '40', 4), (15, 3, '50', 5);

INSERT INTO attempts (id, user_id, challenge_id, score, completed_at) VALUES
(1, 1, 1, 60, '2026-10-30 10:15:00'),
(2, 2, 1, 100, '2026-10-30 10:20:00');

INSERT INTO attempt_items (attempt_id, challenge_item_id, selected_position) VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 4), (1, 4, 3), (1, 5, 5),
(2, 1, 1), (2, 2, 2), (2, 3, 3), (2, 4, 4), (2, 5, 5);

COMMIT;
