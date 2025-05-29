-- Rename expenses table to transactions
RENAME TABLE expenses TO transactions;

-- Add transaction_type column
ALTER TABLE transactions ADD COLUMN transaction_type ENUM('income', 'expense') NOT NULL DEFAULT 'expense';

-- Update categories table to include type and color
ALTER TABLE categories ADD COLUMN type ENUM('income', 'expense') NOT NULL DEFAULT 'expense';
ALTER TABLE categories ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#4F46E5';

-- Add date_created and last_login to user table
ALTER TABLE user 
ADD COLUMN date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;

-- Insert default income categories
INSERT INTO categories (name, type, color) VALUES 
('Salary', 'income', '#10B981'),
('Freelance', 'income', '#F59E0B'),
('Investments', 'income', '#8B5CF6'),
('Gifts', 'income', '#EC4899');

-- Insert default expense categories
INSERT INTO categories (name, type, color) VALUES 
('Food', 'expense', '#EF4444'),
('Transportation', 'expense', '#84CC16'),
('Housing', 'expense', '#F97316'),
('Utilities', 'expense', '#6366F1'),
('Entertainment', 'expense', '#4F46E5'),
('Shopping', 'expense', '#10B981'),
('Healthcare', 'expense', '#F59E0B'),
('Education', 'expense', '#8B5CF6'); 