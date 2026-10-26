-- Admin-managed announcements displayed on the signed-in User dashboard.
-- These records publish copy only; they do not change product or order prices.
CREATE TABLE storefront_announcements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type ENUM('sale', 'event', 'raffle', 'announcement') NOT NULL DEFAULT 'announcement',
    title VARCHAR(120) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_storefront_announcements_active_created (is_active, created_at),
    KEY idx_storefront_announcements_product (product_id),
    CONSTRAINT fk_storefront_announcements_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE SET NULL ON UPDATE RESTRICT,
    CONSTRAINT fk_storefront_announcements_creator FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (migration_id)
VALUES ('005_create_storefront_announcements');
