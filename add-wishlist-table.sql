-- Create customer_wishlist table if not exists
CREATE TABLE IF NOT EXISTS customer_wishlist (
    id SERIAL PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(customer_id, product_id)
);

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_customer_wishlist_customer ON customer_wishlist(customer_id);
CREATE INDEX IF NOT EXISTS idx_customer_wishlist_product ON customer_wishlist(product_id);

-- Show table structure
\d customer_wishlist
