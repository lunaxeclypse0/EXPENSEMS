-- One-time additive migration for the original project schema. Back up the database before running it.
USE `em-system`;

-- The original schema updated created_at on every edit. Preserve creation time and add a real updated_at column.
ALTER TABLE users MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add missing category records before creating referential integrity, then remove duplicate category names.
INSERT INTO categories (name)
SELECT DISTINCT e.category
FROM expenses AS e
LEFT JOIN categories AS c ON c.name = e.category
WHERE c.id IS NULL;
DELETE c1 FROM categories AS c1 INNER JOIN categories AS c2 ON c1.name = c2.name AND c1.id > c2.id;
ALTER TABLE categories ADD UNIQUE KEY uq_categories_name (name);

ALTER TABLE expenses MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE expenses ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
ALTER TABLE expenses ADD KEY idx_expenses_owner_date (user_id, expense_date), ADD KEY idx_expenses_category_date (category, expense_date);
ALTER TABLE expenses ADD CONSTRAINT chk_expenses_positive_amount CHECK (amount > 0);
ALTER TABLE expenses ADD CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE expenses ADD CONSTRAINT fk_expenses_category FOREIGN KEY (category) REFERENCES categories (name) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE notifications ADD COLUMN user_id INT(11) NULL AFTER id;
UPDATE notifications AS n JOIN (SELECT MIN(id) AS id FROM users WHERE role = 'admin') AS a SET n.user_id = a.id WHERE n.user_id IS NULL;
ALTER TABLE notifications MODIFY user_id INT(11) NOT NULL;
ALTER TABLE notifications MODIFY type ENUM('info', 'expense', 'user') NOT NULL DEFAULT 'info';
ALTER TABLE notifications MODIFY is_read TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE notifications ADD KEY idx_notifications_user_read_created (user_id, is_read, created_at);
ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_tokens_hash (token_hash),
    KEY idx_remember_tokens_user_expires (user_id, expires_at),
    CONSTRAINT fk_remember_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
