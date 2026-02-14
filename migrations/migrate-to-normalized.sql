-- =====================================================
-- MIGRATION SCRIPT: Old Products Table to Normalized
-- =====================================================
-- This script safely migrates data from the old single
-- products table to the new normalized structure
-- =====================================================

-- Step 1: Rename old products table as backup
ALTER TABLE IF EXISTS products RENAME TO products_old_backup;

-- Step 2: Run the normalized schema
\i normalized-products-schema.sql

-- Step 3: Migrate data from old table to new tables
DO $$
DECLARE
    old_product RECORD;
    category_id VARCHAR(50);
BEGIN
    RAISE NOTICE 'Starting migration...';
    
    FOR old_product IN SELECT * FROM products_old_backup LOOP
        BEGIN
            -- Insert into main products table
            INSERT INTO products (id, sku, slug, status, featured, created_at, updated_at)
            VALUES (
                old_product.id,
                old_product.sku,
                old_product.slug,
                COALESCE(old_product.status, 'published'),
                COALESCE(old_product.featured, false),
                COALESCE(old_product.created_at, CURRENT_TIMESTAMP),
                COALESCE(old_product.updated_at, CURRENT_TIMESTAMP)
            )
            ON CONFLICT (id) DO NOTHING;
            
            -- Insert descriptions (Bulgarian)
            INSERT INTO product_descriptions (product_id, language_code, name, description, meta_description)
            VALUES (
                old_product.id,
                'bg',
                old_product.name,
                old_product.description,
                old_product.meta_description
            )
            ON CONFLICT (product_id, language_code) DO UPDATE
            SET name = EXCLUDED.name,
                description = EXCLUDED.description,
                meta_description = EXCLUDED.meta_description;
            
            -- Insert price
            INSERT INTO product_prices (product_id, price, compare_price, currency, is_active, valid_from)
            VALUES (
                old_product.id,
                COALESCE(old_product.price, 0),
                old_product.compare_price,
                'EUR',
                true,
                COALESCE(old_product.created_at, CURRENT_TIMESTAMP)
            );
            
            -- Insert inventory
            INSERT INTO product_inventory (product_id, warehouse_location, quantity, low_stock_threshold)
            VALUES (
                old_product.id,
                'main',
                COALESCE(old_product.stock, 0),
                5
            )
            ON CONFLICT (product_id, warehouse_location) DO UPDATE
            SET quantity = EXCLUDED.quantity;
            
            -- Parse and insert images from JSON or text field
            IF old_product.images IS NOT NULL AND old_product.images != '' THEN
                -- Try to parse as JSON array
                BEGIN
                    IF old_product.images::text LIKE '[%' OR old_product.images::text LIKE '{%' THEN
                        -- It's JSON
                        DECLARE
                            img_data JSON;
                            img_item JSON;
                            img_counter INTEGER := 0;
                        BEGIN
                            img_data := old_product.images::JSON;
                            
                            -- Handle array of images
                            IF json_typeof(img_data) = 'array' THEN
                                FOR img_item IN SELECT * FROM json_array_elements(img_data) LOOP
                                    INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                                    VALUES (
                                        old_product.id,
                                        img_item::text,
                                        CASE WHEN img_counter = 0 THEN 'primary' ELSE 'gallery' END,
                                        img_counter
                                    );
                                    img_counter := img_counter + 1;
                                END LOOP;
                            -- Handle object with images
                            ELSIF json_typeof(img_data) = 'object' THEN
                                IF img_data ? 'primary' THEN
                                    INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                                    VALUES (old_product.id, img_data->>'primary', 'primary', 0);
                                END IF;
                                IF img_data ? 'image' THEN
                                    INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                                    VALUES (old_product.id, img_data->>'image', 'primary', 0);
                                END IF;
                            END IF;
                        END;
                    ELSE
                        -- Plain text URL
                        INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                        VALUES (old_product.id, old_product.images::text, 'primary', 0);
                    END IF;
                EXCEPTION
                    WHEN OTHERS THEN
                        -- Fallback: treat as plain text
                        INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                        VALUES (old_product.id, old_product.images::text, 'primary', 0);
                END;
            END IF;
            
            -- Link to category
            IF old_product.category IS NOT NULL AND old_product.category != '' THEN
                -- Find category by slug or name (case-insensitive)
                SELECT c.id INTO category_id
                FROM categories c
                WHERE LOWER(c.slug) = LOWER(old_product.category) 
                   OR LOWER(c.name) = LOWER(old_product.category)
                LIMIT 1;
                
                IF category_id IS NOT NULL THEN
                    INSERT INTO product_category_links (product_id, category_id, is_primary, sort_order)
                    VALUES (old_product.id, category_id, true, 0)
                    ON CONFLICT (product_id, category_id) DO NOTHING;
                END IF;
            END IF;
            
            RAISE NOTICE 'Migrated product: % - %', old_product.id, old_product.name;
            
        EXCEPTION
            WHEN OTHERS THEN
                RAISE WARNING 'Failed to migrate product %: %', old_product.id, SQLERRM;
        END;
    END LOOP;
    
    RAISE NOTICE 'Migration completed!';
END $$;

-- Step 4: Verify migration
SELECT 
    (SELECT COUNT(*) FROM products_old_backup) as old_count,
    (SELECT COUNT(*) FROM products) as new_count,
    (SELECT COUNT(*) FROM product_descriptions) as descriptions_count,
    (SELECT COUNT(*) FROM product_prices) as prices_count,
    (SELECT COUNT(*) FROM product_inventory) as inventory_count,
    (SELECT COUNT(*) FROM product_images) as images_count,
    (SELECT COUNT(*) FROM product_category_links) as category_links_count;

-- Step 5: Show sample data from new structure
SELECT * FROM v_products_complete_bg LIMIT 5;

-- =====================================================
-- OPTIONAL: Drop old backup table after verification
-- =====================================================
-- Uncomment the line below ONLY after verifying data migration is successful
-- DROP TABLE IF EXISTS products_old_backup;

-- =====================================================
-- NOTES
-- =====================================================
-- 1. Review the migration result carefully
-- 2. Test the application with new structure
-- 3. Only drop products_old_backup after confirming everything works
-- 4. Update your PHP functions to use the new views (v_products_complete_bg)
