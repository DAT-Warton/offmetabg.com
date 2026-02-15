-- Add profile_picture and additional fields to customers table
-- Migration: add-profile-fields.sql

-- For PostgreSQL
ALTER TABLE customers ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS full_name VARCHAR(255);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS phone VARCHAR(50);

-- For MySQL (uncomment if using MySQL)
-- ALTER TABLE customers ADD COLUMN profile_picture VARCHAR(500);
-- ALTER TABLE customers ADD COLUMN full_name VARCHAR(255);
-- ALTER TABLE customers ADD COLUMN phone VARCHAR(50);

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_customers_profile_picture ON customers (profile_picture);

-- Comments for documentation
COMMENT ON COLUMN customers.profile_picture IS 'Path to user profile picture (relative to root)';
COMMENT ON COLUMN customers.full_name IS 'User full name for display and correspondence';
COMMENT ON COLUMN customers.phone IS 'User phone number for contact and delivery';
