# Database Design and Migration Plan

## Status and source of truth

This document owns the data model and migration plan. Migrations `001_create_users.sql` and `002_add_user_profile_fields.sql` define the local account foundation. Migration `003_create_catalog_and_pickup_orders.sql` adds categories, products, stock/reservation balances, pickup orders and item snapshots, stock movements, and order-status history for the User catalog/order slice. Migration `004_add_pickup_payment_method.sql` adds the order-level payment method snapshot and has been applied locally. Migration `005_create_storefront_announcements.sql` adds Admin-authored customer-facing announcement copy with optional product association. Migration `006_create_product_offers.sql` adds product discounts and order-item discount/free-quantity snapshots. The initial proposed discount policy is one active offer per product, applied to the same product in pickup checkout; fixed discounts are per purchased unit, percentages apply to paid units, and buy-X-get-Y adds the free quantity when a complete buy quantity is selected. Free units are included in stock reservation and consumption. Admin in-store POS sales, refunds and report data still need design before implementation.

## Implemented User-slice data model

The implemented `users` table stores first name, optional middle name, last name, optional phone number, unique email, password hash, one of the two approved roles (`admin` or `user`), and account creation time. Public registration always assigns `user`. Password confirmation is form-only and is never stored. Migration `002_add_user_profile_fields.sql` adds these profile columns without rewriting applied migration `001`; first and last names allow up to 100 characters, middle name up to 100 characters, and phone number up to 30 characters and must include a digit when supplied. Existing rows receive blank first/last names and NULL optional fields. Users may update their own profile as implemented at `user/account/profile.php`.

The catalog tables in migration 003 also support the initial Admin product/category screens. Product add/edit uses existing `products` columns; creating a product accepts an initial stock quantity and records any positive quantity as a `received` stock movement in the same transaction. Later stock receipts use the same ledger pattern. The inventory screen reads the latest 100 movements. Migration 006 adds `product_offers`, `discount_amount`, `free_quantity` and `promotion_title` snapshots. Migration 007 adds `user_cart_items`, keyed by User and product, to retain bag contents across logouts and browser sessions; cart changes are saved as they happen and checkout clears the saved rows in the same transaction that creates the order. The Admin page validates offer inputs and permits one active offer per product at a time. Offer rules are initial proposals: fixed amount off each paid unit, percentage off paid units, or free units after each complete buy-X bundle; Free units are reserved and consumed from stock and their normal price is stored as the order discount. The Admin product Delete action soft-deactivates a product by setting `is_active` to false so order snapshots, stock movements, image files and saved bag references remain intact. Physical product deletion is not used. Category deletion/deactivation, stock reduction outside pickup payment, in-store POS sales and refund handling remain unresolved or future design work.

User orders use a per-User saved cart, mirrored in the active PHP session for the current request flow, and snapshot a payment method (`demo_online` or `cash_at_shop`) at submission. The online method is simulated only and is not evidence that money moved; both methods require Admin confirmation before the order becomes paid. Never trust a submitted paid status. Product offer totals are calculated server-side, and line items store the original unit price, total units, free-unit quantity, discount amount, offer-title snapshot and final line total. Admin confirmation consumes all reserved units, including free offer units, and records a sale movement and status-history event in one transaction. Reservation overrides, customer edits and account deactivation remain future decisions/work. Current product-image storage details and host compatibility are documented in `docs/08-security-guidelines.md`. Profile behavior is described in `docs/03-pages-and-features.md`; permission boundaries are in `docs/04-roles-and-permissions.md`.

## Reservation and stock integrity requirements

- A placed pickup order reserves only currently available stock; available quantity must account for existing reservations.
- Unpaid reservations expire 48 hours after placement.
- Expiry and customer cancellation of a pending unpaid order release reserved stock exactly once. A repeated job/request or concurrent request must not release it again.
- Payment confirmation, cancellation, expiry, in-store sale and stock adjustment must coordinate safely under concurrent activity. Use an appropriate transaction and concurrency-control design so the same units cannot be sold/reserved twice.
- Status change and stock movement must remain consistent if any step fails. Preserve an auditable reason/actor/time for stock changes.
- Reservation placement, User cancellation and expiry use transactions and lock order/product rows. Status updates and reserved-quantity release commit together; the status guard and row lock prevent repeated/concurrent release.
- The current implementation checks expiry on catalog and User order requests; it does not assume a cron job. This is idempotent but can delay releasing stock until a request arrives. Exact-time expiry requires a verified scheduler or an approved alternative.

## Data design principles

- Use MySQL/MariaDB-compatible keys, foreign keys, indexes and unique constraints; verify exact engine/host support.
- Use `DECIMAL` for money, not floating-point types. The owner selected PHP (Philippine peso); screens display the explicit `PHP` code. Store order timestamps in UTC and label displayed UTC times; deployment/database timezone behavior still needs verification.
- Normalize identifiers such as email consistently; document the uniqueness rule.
- Use explicit deletion/update behavior; preserve historical sale/order records.
- Keep migration files separate from optional, non-sensitive development seeds and real data.

## Migration policy

- Keep ordered, uniquely numbered SQL migrations under `database/migrations/` after the affected data design is reviewed.
- Apply migrations manually once with phpMyAdmin or the XAMPP MySQL command-line client. Check the `schema_migrations` ledger before applying; do not re-run an applied file.
- MariaDB DDL may partially commit if a migration fails. Inspect and repair a partial migration manually before retrying; do not assume rollback is automatic.
- Apply to a fresh local database first; verify relationships, indexes, constraints and representative queries.
- Before production changes, make and verify a backup; record the migration identifier. Never overwrite production data with a development dump.
- Local XAMPP and hosted databases are separate; apply reviewed schema independently on the host.

The initial `001_create_users.sql` migration creates `schema_migrations` and `users`, then records its ID. It was applied locally to MariaDB 10.4.32. Migrations `002_add_user_profile_fields.sql` and `003_create_catalog_and_pickup_orders.sql` are recorded locally. Migrations `004_add_pickup_payment_method.sql` through `007_create_user_cart_items.sql` were applied and recorded in the local database on 2026-10-26. Apply these migrations to any hosted database separately before deployment. A fresh-database migration rehearsal and repeat/partial-failure procedure remain unverified.

## Open design questions

- Account constraints and safe provisioning/recovery for Admin accounts, aligned to the fixed Admin/User role model.
- Admin stock adjustment/reconciliation rules and movement types for all operational workflows.
- Paid-order cancellation/refund outcomes and whether they require additional fields beyond the current status history.
- Refund outcome fields, audit history and retention needs.
- UTC display preference and production DB timezone verification; no tax or fee calculation is approved. Product discounts follow the proposed rules listed above and are included in order totals.
- Category deletion/deactivation policy and account deactivation behavior. Product removal is implemented as history-preserving deactivation.
- Image file storage/reference strategy and host-compatible public/private paths.
- Migration runner versus manual application is resolved for now: use documented manual application and the `schema_migrations` ledger; reconsider only if project scale requires a runner.

