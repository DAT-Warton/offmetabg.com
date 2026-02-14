-- Add OAuth support to customers table
-- Migration to add oauth_provider and oauth_provider_id columns

-- Add oauth_provider column (google, facebook, instagram, discord, twitch, tiktok, kick, or NULL for traditional auth)
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(50) DEFAULT NULL;

-- Add oauth_provider_id column (the unique ID from the OAuth provider)
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS oauth_provider_id VARCHAR(255) DEFAULT NULL;

-- Add avatar column for OAuth profile pictures
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS avatar VARCHAR(500) DEFAULT NULL;

-- Create index for faster OAuth user lookups
CREATE INDEX IF NOT EXISTS idx_customers_oauth ON customers(oauth_provider, oauth_provider_id);

-- Comments
COMMENT ON COLUMN customers.oauth_provider IS 'OAuth provider name (google, facebook, instagram, discord, twitch, tiktok, kick) or NULL for traditional login';
COMMENT ON COLUMN customers.oauth_provider_id IS 'Unique user ID from the OAuth provider';
COMMENT ON COLUMN customers.avatar IS 'URL to user profile picture from OAuth provider or uploaded avatar';

-- Allow password to be NULL for OAuth-only accounts
ALTER TABLE customers
ALTER COLUMN password DROP NOT NULL;

COMMENT ON COLUMN customers.password IS 'Hashed password for traditional login, NULL for OAuth-only accounts';
