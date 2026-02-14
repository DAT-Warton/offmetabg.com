-- Reset migration to retry with fixed script
DROP TABLE IF EXISTS product_attributes CASCADE;
DROP TABLE IF EXISTS product_category_links CASCADE;
DROP TABLE IF EXISTS product_inventory CASCADE;
DROP TABLE IF EXISTS product_prices CASCADE;
DROP TABLE IF EXISTS product_videos CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS product_descriptions CASCADE;
DROP TABLE IF EXISTS products CASCADE;

-- Drop views
DROP VIEW IF EXISTS v_products_complete_en CASCADE;
DROP VIEW IF EXISTS v_products_complete_bg CASCADE;

-- Drop functions
DROP FUNCTION IF EXISTS update_product_timestamp() CASCADE;
DROP FUNCTION IF EXISTS get_product_available_stock(VARCHAR) CASCADE;
DROP FUNCTION IF EXISTS get_product_current_price(VARCHAR) CASCADE;

-- Restore old table
ALTER TABLE products_old_backup RENAME TO products;

-- Verify
SELECT COUNT(*) as products_restored FROM products;
