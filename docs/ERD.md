# Rank It: entity-relationship diagram

The original Milestone 1 model is implemented in `backend/database/rank_it.sql` for Milestone 2. The five entities and fields are preserved.

```mermaid
erDiagram
    users ||--o{ attempts : submits
    challenges ||--o{ challenge_items : contains
    challenges ||--o{ attempts : receives
    attempts ||--o{ attempt_items : records
    challenge_items ||--o{ attempt_items : appears_in

    users {
        int id PK
        varchar name
        varchar email UK
        varchar password_hash
        enum role "user or admin"
    }
    challenges {
        int id PK
        varchar title
        text description
        date challenge_date
        enum status "Draft, Pending, Published"
    }
    challenge_items {
        int id PK
        int challenge_id FK
        varchar item_text
        int correct_position
    }
    attempts {
        int id PK
        int user_id FK
        int challenge_id FK
        int score
        datetime completed_at
    }
    attempt_items {
        int id PK
        int attempt_id FK
        int challenge_item_id FK
        int selected_position
    }
```

## Relationships and planned rules

- A user submits many attempts; each attempt belongs to one user. Admins are users with `role = admin`.
- A challenge contains many challenge items and receives many attempts. Each item belongs to one challenge. A published challenge must have exactly five items, with unique correct positions 1-5.
- An attempt contains five attempt items when completed. Each selected item references one challenge item. The item must belong to the same challenge as the attempt.
- A challenge item can appear in many players' attempt items. These rows preserve each player's selected order separately from the answer.

SQL constraints: unique email; unique `(user_id, challenge_id)` for one attempt per user/challenge; unique `(challenge_id, correct_position)`; unique `(attempt_id, selected_position)` and `(attempt_id, challenge_item_id)`. Position checks allow only 1-5, and scores allow 0, 20, 40, 60 or 100. Only one published challenge should be available for each date. Drafts may share a proposed date, so publication needs a future service-level date check.

Scores are computed by the backend in a later milestone: matching positions multiplied by 20, from 0 to 100. For a permutation of five distinct items, exactly four correct positions is impossible; 80 is therefore not a possible completed score under this rule.

Drafts and attempts may temporarily have fewer than five items while being assembled, hence the zero-to-many physical relationship. Future services must create a completed attempt and its five answers atomically, compute its score and enforce publication rules.

All tables explicitly use InnoDB and utf8mb4_unicode_ci. IDs and foreign keys are unsigned INTs; positions and scores are unsigned TINYINTs. Names are VARCHAR(100), emails VARCHAR(254), password hashes VARCHAR(255), titles VARCHAR(200), item text VARCHAR(100), descriptions TEXT, challenge dates DATE and completion times DATETIME. All listed columns are NOT NULL. Roles default to user; challenge status defaults to Draft.

Foreign keys restrict deleting referenced users, challenges and challenge items. Deleting an attempt cascades to its attempt items. Primary key changes are restricted. Same-challenge consistency across the two answer references is checked by the DAOs, including edits to parent references; the five foreign keys do not enforce that cross-table rule on arbitrary direct SQL. Future services must also protect published answers once history exists. The frontend fixtures remain separate from the database.

Future AI suggestions become ordinary draft challenges and challenge items after review. This does not require an additional core entity.
