-- Snapshot the User's selected checkout method on each pickup order.
-- Existing orders predate method selection and default to cash at the shop.
ALTER TABLE pickup_orders
    ADD COLUMN payment_method ENUM('demo_online', 'cash_at_shop')
        NOT NULL DEFAULT 'cash_at_shop' AFTER status;

INSERT INTO schema_migrations (migration_id)
VALUES ('004_add_pickup_payment_method');
