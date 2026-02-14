# Normalized Products Database Structure

## Overview
Продуктовата база данни е реорганизирана в множество таблици за по-добра организация, мащабируемост и гъвкавост.

## Database Tables

### 1. **products** - Main Products Table
**Цел:** Съхранява основната информация за продукта

| Column | Type | Description |
|--------|------|-------------|
| id | VARCHAR(50) | Уникален идентификатор (PRIMARY KEY) |
| sku | VARCHAR(100) | SKU код (UNIQUE) |
| slug | VARCHAR(255) | URL slug (UNIQUE) |
| status | VARCHAR(20) | published, draft, archived |
| featured | BOOLEAN | Препоръчан продукт |
| created_at | TIMESTAMP | Дата на създаване |
| updated_at | TIMESTAMP | Последна актуализация |

### 2. **product_descriptions** - Names and Descriptions
**Цел:** Поддръжка на множество езици за име и описание

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products (FOREIGN KEY) |
| language_code | VARCHAR(5) | bg, en, etc. |
| name | VARCHAR(255) | Име на продукта |
| short_description | TEXT | Кратко описание |
| description | TEXT | Пълно описание |
| meta_title | VARCHAR(255) | SEO заглавие |
| meta_description | TEXT | SEO описание |

**UNIQUE:** (product_id, language_code)

### 3. **product_images** - Product Images
**Цел:** Множество снимки с приоритет и тип

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| image_url | TEXT | URL на снимката |
| image_type | VARCHAR(20) | primary, gallery, thumbnail |
| alt_text | VARCHAR(255) | ALT текст за SEO |
| sort_order | INTEGER | Ред на показване |

### 4. **product_videos** - Product Videos
**Цел:** YouTube, TikTok, Instagram видеа

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| platform | VARCHAR(20) | youtube, tiktok, instagram |
| video_url | TEXT | URL на видеото |
| video_id | VARCHAR(100) | ID на видеото в платформата |
| thumbnail_url | TEXT | Thumbnail снимка |
| sort_order | INTEGER | Ред на показване |

### 5. **product_prices** - Price History
**Цел:** История на цени, промоции, валидност

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| price | DECIMAL(10,2) | Актуална цена |
| compare_price | DECIMAL(10,2) | Оригинална цена (за отстъпка) |
| currency | VARCHAR(3) | EUR, BGN, USD |
| valid_from | TIMESTAMP | От кога е валидна |
| valid_to | TIMESTAMP | До кога е валидна (NULL = текуща) |
| is_active | BOOLEAN | Активна ли е |

### 6. **product_inventory** - Stock Management
**Цел:** Управление на наличности по складове

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| warehouse_location | VARCHAR(100) | Склад (main, warehouse2, etc.) |
| quantity | INTEGER | Общо количество |
| reserved_quantity | INTEGER | Резервирано за поръчки |
| available_quantity | INTEGER | Налично (AUTO COMPUTED) |
| low_stock_threshold | INTEGER | Праг за ниски наличности |
| last_restocked | TIMESTAMP | Последно попълване |

**UNIQUE:** (product_id, warehouse_location)

### 7. **product_category_links** - Product-Category Relations
**Цел:** Many-to-Many връзка между продукти и категории

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| category_id | VARCHAR(50) | Връзка към categories |
| is_primary | BOOLEAN | Основна категория |
| sort_order | INTEGER | Ред в категорията |

**UNIQUE:** (product_id, category_id)

### 8. **product_attributes** - Flexible Attributes
**Цел:** Допълнителни атрибути (цвят, размер, материал)

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Auto-increment ID |
| product_id | VARCHAR(50) | Връзка към products |
| attribute_key | VARCHAR(100) | Име на атрибута (color, size) |
| attribute_value | TEXT | Стойност |
| display_order | INTEGER | Ред на показване |

## Database Views

### **v_products_complete_bg** - Complete Product View (Bulgarian)
Готов за употреба VIEW с всички данни на български език

```sql
SELECT * FROM v_products_complete_bg WHERE status = 'published' ORDER BY created_at DESC;
```

**Columns:**
- id, sku, slug, status, featured
- name, description, short_description, meta_description (BG)
- price, compare_price, currency
- stock, available_quantity
- primary_image
- all_images (JSON array)
- videos (JSON array)
- categories (JSON array)
- created_at, updated_at

### **v_products_complete_en** - Complete Product View (English)
Същото, но на английски език

## Helper Functions

### `get_product_current_price(product_id)`
Връща текущата цена на продукт
```sql
SELECT get_product_current_price('prod_123');
```

### `get_product_available_stock(product_id)`
Връща наличното количество
```sql
SELECT get_product_available_stock('prod_123');
```

## PHP Integration

### Използване във PHP:

```php
// Get all products (Bulgarian)
$query = "SELECT * FROM v_products_complete_bg WHERE status = 'published' ORDER BY created_at DESC";
$products = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get single product
$stmt = $pdo->prepare("SELECT * FROM v_products_complete_bg WHERE id = ? OR slug = ?");
$stmt->execute([$product_id, $slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Product data structure:
$product = [
    'id' => 'prod_123',
    'name' => 'Клетвата на ездача',
    'description' => 'Пълно описание...',
    'price' => 24.90,
    'currency' => 'EUR',
    'stock' => 10,
    'primary_image' => 'https://...',
    'all_images' => '[{"url": "...", "type": "primary"}, ...]', // JSON string
    'videos' => '[{"platform": "youtube", "url": "..."}]', // JSON string
    'categories' => '[{"id": "cat1", "name": "Кутии"}]' // JSON string
];
```

## Migration Process

### Step 1: Backup Current Data
```bash
ssh root@46.225.71.94
sudo -u postgres pg_dump offmetabg_db > /tmp/backup_before_migration.sql
```

### Step 2: Run Normalization
```bash
cd /var/www/offmetabg/migrations
sudo -u postgres psql -d offmetabg_db -f migrate-to-normalized.sql
```

### Step 3: Verify
```sql
-- Check counts
SELECT * FROM v_products_complete_bg LIMIT 10;

-- Verify all data migrated
SELECT 
    (SELECT COUNT(*) FROM products_old_backup) as old_count,
    (SELECT COUNT(*) FROM products) as new_count;
```

### Step 4: Update PHP Code
Update `includes/functions.php` to use views instead of direct table queries.

### Step 5: Test Thoroughly
- Test product listing
- Test product details
- Test cart functionality
- Test admin product management

### Step 6: Drop Old Backup (ONLY AFTER TESTING!)
```sql
DROP TABLE IF EXISTS products_old_backup;
```

## Benefits

✅ **Scalability**: Easy to add new features (price history, multi-warehouse)
✅ **Multi-language**: Support for Bulgarian, English, etc.
✅ **Price History**: Track price changes over time
✅ **Multiple Images**: Unlimited images per product
✅ **Stock Management**: Support for multiple warehouses
✅ **Flexibility**: Easy to add custom attributes
✅ **Performance**: Indexed properly for fast queries
✅ **Data Integrity**: Foreign keys enforce relationships

## Example Queries

### Add new product
```sql
-- 1. Insert main product
INSERT INTO products (id, sku, slug, status) 
VALUES ('prod_new', 'SKU123', 'product-slug', 'published');

-- 2. Add name and description
INSERT INTO product_descriptions (product_id, language_code, name, description)
VALUES ('prod_new', 'bg', 'Име на продукт', 'Описание...');

-- 3. Set price
INSERT INTO product_prices (product_id, price, currency, is_active)
VALUES ('prod_new', 29.90, 'EUR', true);

-- 4. Add stock
INSERT INTO product_inventory (product_id, warehouse_location, quantity)
VALUES ('prod_new', 'main', 50);

-- 5. Add image
INSERT INTO product_images (product_id, image_url, image_type)
VALUES ('prod_new', 'https://...', 'primary');

-- 6. Link to category
INSERT INTO product_category_links (product_id, category_id, is_primary)
VALUES ('prod_new', 'cat_123', true);
```

### Update price (with history)
```sql
-- Mark old price as inactive
UPDATE product_prices SET is_active = false, valid_to = NOW()
WHERE product_id = 'prod_123' AND is_active = true;

-- Insert new price
INSERT INTO product_prices (product_id, price, currency, is_active, valid_from)
VALUES ('prod_123', 19.90, 'EUR', true, NOW());
```

### Get price history
```sql
SELECT price, currency, valid_from, valid_to, is_active
FROM product_prices
WHERE product_id = 'prod_123'
ORDER BY valid_from DESC;
```
