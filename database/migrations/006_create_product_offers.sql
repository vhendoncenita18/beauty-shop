-- One active, product-specific offer may apply to an item at checkout.
CREATE TABLE product_offers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    offer_type ENUM('percent', 'fixed', 'buy_x_get_y') NOT NULL,
    discount_value DECIMAL(10,2) NULL,
    buy_quantity INT UNSIGNED NULL,
    free_quantity INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_product_offers_product_active (product_id, is_active),
    CONSTRAINT fk_product_offers_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT fk_product_offers_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pickup_order_items
    ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER line_total,
    ADD COLUMN free_quantity INT UNSIGNED NOT NULL DEFAULT 0 AFTER discount_amount,
    ADD COLUMN promotion_title VARCHAR(120) NULL AFTER free_quantity;

INSERT INTO schema_migrations (migration_id)
VALUES ('006_create_product_offers');
