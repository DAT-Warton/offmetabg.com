-- Add missing categories
INSERT INTO categories (id, slug, name, description, status, active, product_count, created_at, updated_at)
VALUES
    ('cat_' || substr(md5('cards'), 1, 13), 'cards', 'Картички', 'Уникални картички с любими персонажи', 'active', true, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('cat_' || substr(md5('promo'), 1, 13), 'promo', 'Промо', 'Промоционални продукти и оферти', 'active', true, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('cat_' || substr(md5('uncategorized'), 1, 13), 'uncategorized', 'Без категория', 'Некатегоризирани продукти', 'active', true, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('cat_' || substr(md5('ornaments'), 1, 13), 'ornaments', 'Орнаменти', 'Празнични орнаменти и декорации', 'active', true, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (slug) DO NOTHING;
