# SQL Migrations

The current schema is defined by the ordered migrations:

1. `001_create_users.sql` creates the `users` table and `schema_migrations` ledger.
2. `002_add_user_profile_fields.sql` adds the User profile fields.
3. `003_create_catalog_and_pickup_orders.sql` creates the catalog, inventory,
   pickup-order and order-history tables used by the current application.
4. `004_add_pickup_payment_method.sql` stores the selected checkout method;
   existing orders default to cash at the shop.
5. `005_create_storefront_announcements.sql` adds Admin-managed announcements
   for the signed-in User dashboard. These do not alter order pricing.
6. `006_create_product_offers.sql` adds Admin-managed product discounts and
   order-item snapshots for discount/free-item totals.
7. `007_create_user_cart_items.sql` saves each User's cart across logouts and
   browser sessions.

In-store sales and reports are not yet represented by these migrations; see
`docs/05-database-design.md` for status.

For a fresh database, apply migrations 001 through 007 in order, once each,
using the XAMPP MySQL command-line client or phpMyAdmin. Each migration records
its ID in `schema_migrations`. Check the ledger before applying a file; never
re-run an applied migration. MariaDB DDL can commit statement by statement, so
if a migration stops partway, inspect the database and resolve it manually
before retrying. Test migrations on a fresh local database. Back up production
before applying a reviewed migration, and apply hosted migrations separately.

Do not put passwords, production data, or secrets in migration files.
