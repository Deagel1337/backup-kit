CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at DATETIME NOT NULL
);

INSERT INTO users (name, email, created_at)
VALUES
    ('Max Mustermann', 'max@example.com', '2026-01-01 10:00:00'),
    ('Erika Musterfrau', 'erika@example.com', '2026-01-02 11:00:00');

INSERT INTO orders (user_id, total, created_at)
VALUES
    (1, 19.99, '2026-01-03 12:00:00'),
    (1, 49.50, '2026-01-03 12:00:00'),
    (2, 90.99, '2026-01-03 12:00:00');