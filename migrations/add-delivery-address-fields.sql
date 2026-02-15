-- Add delivery address fields to customers table for Speedy/Econt delivery
-- Migration: add-delivery-address-fields.sql

-- For PostgreSQL
ALTER TABLE customers ADD COLUMN IF NOT EXISTS city VARCHAR(100);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS address VARCHAR(500);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS region VARCHAR(100);
ALTER TABLE customers ADD COLUMN IF NOT EXISTS address_notes TEXT;

-- For MySQL (uncomment if using MySQL)
-- ALTER TABLE customers ADD COLUMN city VARCHAR(100);
-- ALTER TABLE customers ADD COLUMN address VARCHAR(500);
-- ALTER TABLE customers ADD COLUMN postal_code VARCHAR(20);
-- ALTER TABLE customers ADD COLUMN region VARCHAR(100);
-- ALTER TABLE customers ADD COLUMN address_notes TEXT;

-- Add indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_customers_city ON customers (city);
CREATE INDEX IF NOT EXISTS idx_customers_postal_code ON customers (postal_code);

-- Comments for documentation
COMMENT ON COLUMN customers.city IS 'City for delivery address (required for courier services)';
COMMENT ON COLUMN customers.address IS 'Full street address including building number and apartment';
COMMENT ON COLUMN customers.postal_code IS 'Postal/ZIP code for delivery';
COMMENT ON COLUMN customers.region IS 'Region/Province/Oblast for delivery';
COMMENT ON COLUMN customers.address_notes IS 'Additional delivery notes (floor, entrance, etc.)';
