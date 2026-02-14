-- =====================================================
-- NORMALIZED PRODUCTS DATABASE SCHEMA
-- =====================================================
-- This schema splits product data into logical tables
-- for better data organization and scalability
-- =====================================================

-- Main Products Table (Core Information Only)
CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(50) PRIMARY KEY,
    sku VARCHAR(100) UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(20) DEFAULT 'published', -- published, draft, archived
    featured BOOLEAN DEFAULT false,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_featured ON products(featured);

-- Product Names and Descriptions (Multi-language support)
CREATE TABLE IF NOT EXISTS product_descriptions (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    language_code VARCHAR(5) DEFAULT 'bg', -- bg, en, etc.
    name VARCHAR(255) NOT NULL,
    short_description TEXT,
    description TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(product_id, language_code)
);

CREATE INDEX idx_product_descriptions_product ON product_descriptions(product_id);
CREATE INDEX idx_product_descriptions_lang ON product_descriptions(language_code);

-- Product Images
CREATE TABLE IF NOT EXISTS product_images (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    image_url TEXT NOT NULL,
    image_type VARCHAR(20) DEFAULT 'primary', -- primary, gallery, thumbnail
    alt_text VARCHAR(255),
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX idx_product_images_product ON product_images(product_id);
CREATE INDEX idx_product_images_type ON product_images(image_type);
CREATE INDEX idx_product_images_order ON product_images(product_id, sort_order);

-- Product Videos
CREATE TABLE IF NOT EXISTS product_videos (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    platform VARCHAR(20) NOT NULL, -- youtube, tiktok, instagram
    video_url TEXT NOT NULL,
    video_id VARCHAR(100),
    thumbnail_url TEXT,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX idx_product_videos_product ON product_videos(product_id);
CREATE INDEX idx_product_videos_platform ON product_videos(platform);

-- Product Prices (Price History Support)
CREATE TABLE IF NOT EXISTS product_prices (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    compare_price DECIMAL(10, 2), -- Original price for showing discount
    currency VARCHAR(3) DEFAULT 'EUR',
    valid_from TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valid_to TIMESTAMP, -- NULL means current price
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX idx_product_prices_product ON product_prices(product_id);
CREATE INDEX idx_product_prices_active ON product_prices(product_id, is_active);
CREATE INDEX idx_product_prices_validity ON product_prices(product_id, valid_from, valid_to);

-- Product Inventory
CREATE TABLE IF NOT EXISTS product_inventory (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    warehouse_location VARCHAR(100) DEFAULT 'main',
    quantity INTEGER DEFAULT 0,
    reserved_quantity INTEGER DEFAULT 0, -- Reserved for pending orders
    available_quantity INTEGER GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    low_stock_threshold INTEGER DEFAULT 5,
    last_restocked TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(product_id, warehouse_location)
);

CREATE INDEX idx_product_inventory_product ON product_inventory(product_id);
CREATE INDEX idx_product_inventory_location ON product_inventory(warehouse_location);
CREATE INDEX idx_product_inventory_stock ON product_inventory(product_id, quantity);

-- Product Categories (Many-to-Many Relationship)
CREATE TABLE IF NOT EXISTS product_category_links (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    category_id VARCHAR(50) NOT NULL,
    is_primary BOOLEAN DEFAULT false, -- One primary category per product
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE(product_id, category_id)
);

CREATE INDEX idx_product_category_product ON product_category_links(product_id);
CREATE INDEX idx_product_category_category ON product_category_links(category_id);
CREATE INDEX idx_product_category_primary ON product_category_links(product_id, is_primary);

-- Product Attributes (Flexible key-value pairs for additional data)
CREATE TABLE IF NOT EXISTS product_attributes (
    id SERIAL PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    attribute_key VARCHAR(100) NOT NULL, -- color, size, weight, material, etc.
    attribute_value TEXT NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX idx_product_attributes_product ON product_attributes(product_id);
CREATE INDEX idx_product_attributes_key ON product_attributes(attribute_key);

-- =====================================================
-- VIEWS FOR EASY QUERYING
-- =====================================================

-- Complete Product View (Bulgarian)
CREATE OR REPLACE VIEW v_products_complete_bg AS
SELECT 
    p.id,
    p.sku,
    p.slug,
    p.status,
    p.featured,
    pd.name,
    pd.description,
    pd.short_description,
    pd.meta_description,
    pp.price,
    pp.compare_price,
    pp.currency,
    pi.quantity as stock,
    pi.available_quantity,
    (SELECT image_url FROM product_images WHERE product_id = p.id AND image_type = 'primary' LIMIT 1) as primary_image,
    (SELECT json_agg(json_build_object('url', image_url, 'type', image_type, 'alt', alt_text) ORDER BY sort_order) 
     FROM product_images WHERE product_id = p.id) as all_images,
    (SELECT json_agg(json_build_object('platform', platform, 'url', video_url, 'thumbnail', thumbnail_url) ORDER BY sort_order) 
     FROM product_videos WHERE product_id = p.id) as videos,
    (SELECT json_agg(json_build_object('id', c.id, 'name', c.name, 'slug', c.slug) ORDER BY pcl.sort_order)
     FROM product_category_links pcl 
     JOIN categories c ON pcl.category_id = c.id 
     WHERE pcl.product_id = p.id) as categories,
    p.created_at,
    p.updated_at
FROM products p
LEFT JOIN product_descriptions pd ON p.id = pd.product_id AND pd.language_code = 'bg'
LEFT JOIN product_prices pp ON p.id = pp.product_id AND pp.is_active = true AND pp.valid_to IS NULL
LEFT JOIN product_inventory pi ON p.id = pi.product_id AND pi.warehouse_location = 'main';

-- Complete Product View (English)
CREATE OR REPLACE VIEW v_products_complete_en AS
SELECT 
    p.id,
    p.sku,
    p.slug,
    p.status,
    p.featured,
    pd.name,
    pd.description,
    pd.short_description,
    pd.meta_description,
    pp.price,
    pp.compare_price,
    pp.currency,
    pi.quantity as stock,
    pi.available_quantity,
    (SELECT image_url FROM product_images WHERE product_id = p.id AND image_type = 'primary' LIMIT 1) as primary_image,
    (SELECT json_agg(json_build_object('url', image_url, 'type', image_type, 'alt', alt_text) ORDER BY sort_order) 
     FROM product_images WHERE product_id = p.id) as all_images,
    (SELECT json_agg(json_build_object('platform', platform, 'url', video_url, 'thumbnail', thumbnail_url) ORDER BY sort_order) 
     FROM product_videos WHERE product_id = p.id) as videos,
    (SELECT json_agg(json_build_object('id', c.id, 'name', c.name, 'slug', c.slug) ORDER BY pcl.sort_order)
     FROM product_category_links pcl 
     JOIN categories c ON pcl.category_id = c.id 
     WHERE pcl.product_id = p.id) as categories,
    p.created_at,
    p.updated_at
FROM products p
LEFT JOIN product_descriptions pd ON p.id = pd.product_id AND pd.language_code = 'en'
LEFT JOIN product_prices pp ON p.id = pp.product_id AND pp.is_active = true AND pp.valid_to IS NULL
LEFT JOIN product_inventory pi ON p.id = pi.product_id AND pi.warehouse_location = 'main';

-- =====================================================
-- MIGRATION FUNCTIONS
-- =====================================================

-- Function to migrate data from old single-table structure
CREATE OR REPLACE FUNCTION migrate_old_products_to_normalized()
RETURNS void AS $$
DECLARE
    old_product RECORD;
    new_product_id VARCHAR(50);
BEGIN
    -- Check if old products table exists with old structure
    IF EXISTS (
        SELECT FROM information_schema.columns 
        WHERE table_name = 'products_old' AND column_name = 'name'
    ) THEN
        FOR old_product IN SELECT * FROM products_old LOOP
            -- Insert into main products table
            INSERT INTO products (id, sku, slug, status, featured, created_at, updated_at)
            VALUES (
                old_product.id,
                old_product.sku,
                old_product.slug,
                old_product.status,
                COALESCE(old_product.featured, false),
                old_product.created_at,
                old_product.updated_at
            )
            ON CONFLICT (id) DO NOTHING;
            
            -- Insert descriptions (Bulgarian - default)
            INSERT INTO product_descriptions (product_id, language_code, name, description, meta_description)
            VALUES (
                old_product.id,
                'bg',
                old_product.name,
                old_product.description,
                old_product.meta_description
            )
            ON CONFLICT (product_id, language_code) DO NOTHING;
            
            -- Insert price
            INSERT INTO product_prices (product_id, price, compare_price, currency, is_active)
            VALUES (
                old_product.id,
                COALESCE(old_product.price, 0),
                old_product.compare_price,
                'EUR',
                true
            );
            
            -- Insert inventory
            INSERT INTO product_inventory (product_id, warehouse_location, quantity)
            VALUES (
                old_product.id,
                'main',
                COALESCE(old_product.stock, 0)
            )
            ON CONFLICT (product_id, warehouse_location) DO NOTHING;
            
            -- Insert primary image if exists
            IF old_product.images IS NOT NULL AND old_product.images != '' THEN
                INSERT INTO product_images (product_id, image_url, image_type, sort_order)
                VALUES (old_product.id, old_product.images, 'primary', 0);
            END IF;
            
            -- Link to category if exists
            IF old_product.category IS NOT NULL AND old_product.category != '' THEN
                -- Try to find category by slug or name
                INSERT INTO product_category_links (product_id, category_id, is_primary)
                SELECT old_product.id, c.id, true
                FROM categories c
                WHERE c.slug = old_product.category OR c.name = old_product.category
                LIMIT 1
                ON CONFLICT (product_id, category_id) DO NOTHING;
            END IF;
        END LOOP;
        
        RAISE NOTICE 'Migration completed successfully!';
    ELSE
        RAISE NOTICE 'Old products_old table not found. Nothing to migrate.';
    END IF;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- HELPER FUNCTIONS
-- =====================================================

-- Get current price for a product
CREATE OR REPLACE FUNCTION get_product_current_price(p_product_id VARCHAR(50))
RETURNS DECIMAL(10, 2) AS $$
    SELECT price 
    FROM product_prices 
    WHERE product_id = p_product_id 
      AND is_active = true 
      AND (valid_to IS NULL OR valid_to > CURRENT_TIMESTAMP)
    ORDER BY valid_from DESC 
    LIMIT 1;
$$ LANGUAGE SQL;

-- Get available stock for a product
CREATE OR REPLACE FUNCTION get_product_available_stock(p_product_id VARCHAR(50))
RETURNS INTEGER AS $$
    SELECT COALESCE(SUM(available_quantity), 0)::INTEGER
    FROM product_inventory 
    WHERE product_id = p_product_id;
$$ LANGUAGE SQL;

-- Update product timestamp trigger
CREATE OR REPLACE FUNCTION update_product_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE products SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.product_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create triggers for automatic timestamp updates
CREATE TRIGGER trg_product_descriptions_updated
    AFTER INSERT OR UPDATE ON product_descriptions
    FOR EACH ROW EXECUTE FUNCTION update_product_timestamp();

CREATE TRIGGER trg_product_images_updated
    AFTER INSERT OR UPDATE ON product_images
    FOR EACH ROW EXECUTE FUNCTION update_product_timestamp();

CREATE TRIGGER trg_product_prices_updated
    AFTER INSERT OR UPDATE ON product_prices
    FOR EACH ROW EXECUTE FUNCTION update_product_timestamp();

CREATE TRIGGER trg_product_inventory_updated
    AFTER INSERT OR UPDATE ON product_inventory
    FOR EACH ROW EXECUTE FUNCTION update_product_timestamp();
