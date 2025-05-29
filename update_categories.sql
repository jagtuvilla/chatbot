-- Add user_id column to categories table
ALTER TABLE categories
ADD COLUMN user_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Update existing categories to be associated with the first user (if any exists)
UPDATE categories c
JOIN users u ON u.id = (SELECT MIN(id) FROM users)
SET c.user_id = u.id
WHERE c.user_id = 0; 