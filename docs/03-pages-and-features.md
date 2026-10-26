# Approved Pages and Features

## Status and ownership

The owner approved the functional scope below. This is the single source of truth for shop behavior, separate from the deployment rubric in `docs/01-rubric-traceability.md`. Access boundaries are in `docs/04-roles-and-permissions.md`; data planning is in `docs/05-database-design.md`; tests are in `docs/09-testing-and-evidence-plan.md`.

## Approved capabilities

- Beauty-product catalog: browse/search products and view product details.
- Administer products, categories, inventory balances and stock movement history.
- Upload product images using `docs/08-security-guidelines.md`.
- Exactly two roles: Admin and User. Users may register; Admin account creation and role assignment must be controlled server-side. The Admin performs all operational and administrative duties. Detailed permissions are in `docs/04-roles-and-permissions.md`.
- User registration collects required first and last names, a proposed optional middle name, an optional phone number, a unique email address, a password, and a confirmation of that password. The confirmation is checked during registration and is never stored; email/password remain the login credentials. Role is assigned as User by the server.
- Record in-store sales.
- Accept User pickup orders from a shopping cart. Users add products from the catalog, review the cart, choose a demo online payment option or cash at the shop, then submit all cart items as one order. Stock is reserved only when the order is submitted.
- The online option is a demonstration only: it does not connect to GCash or another provider, move money, collect payment credentials, or independently prove payment. The order records the selected method and remains awaiting Admin review/payment confirmation. Cash orders also remain unpaid until Admin confirms receipt in store.
- Admin can view and review all customer orders, see the selected payment method, and record whether payment was confirmed. Admin confirmation is required for either method.
- Reserve available stock when a pickup order is placed. An unpaid reservation expires after 48 hours; release reserved stock exactly once. Users may cancel their own pending unpaid orders. The Admin handles paid-order cancellations and records the applicable refund outcome.
- Provide sales and inventory reports.
- Admin can publish product-level percentage discounts, fixed-amount discounts, and buy-X-get-Y-free offers. **Proposed initial rules:** discounts apply to paid units of the linked product in pickup checkout; BOGO adds free units after each complete buy quantity; free units are reserved from inventory; one active offer per product at a time. Each applied offer and total is snapshotted on the order item.
- Admin can publish and hide product-linked sale announcements, events, raffles and general shop updates for the signed-in User dashboard. These announcement cards do not alter prices unless a product discount is separately configured.
- Use plain PHP/PDO, HTML, CSS, vanilla JavaScript and MySQL/MariaDB; develop locally with XAMPP and prepare for InfinityFree only after verifying actual host limitations.

## Proposed page/route map

These filenames are implementation proposals, not requirements to use exact routes.

| Area | Pages/capabilities |
|---|---|
| Public | Home, catalog/search/category/sort, product details, User registration, login, logout, optional shop information/contact page. |
| User | Browse catalog, register/login, manage own profile and pickup order, view own order history, cancel own pending unpaid order. |
| Admin | All operational and administrative duties: record in-store sales, manage pickup queue, confirm payment at shop, handle paid-order cancellation/refund outcomes, manage products/categories/images/inventory, accounts and reports. |

Operational permissions are assigned to Admin; User access is limited to public catalog functions and the User's own account and pickup orders. See `docs/04-roles-and-permissions.md` for enforcement rules.

## Current User implementation status

The catalog and product-detail routes are `public/catalog/index.php` and `public/catalog/product.php`; the User's header shopping bag opens the cart and checkout dialog, while `public/catalog/cart-actions.php` processes signed-in User cart updates and checkout submissions. Migration `007_create_user_cart_items.sql` persists each User's cart across logout and later login. The active session mirrors the saved cart; add, quantity update, remove and clear actions update the saved rows, and checkout clears them transactionally with order creation. The shopping bag dialog supports item quantities, removal, offer-aware totals, payment choices and checkout without a separate cart page. Add-to-cart motion is progressively enhanced and respects reduced-motion preferences. Checkout offers demo online payment or cash at the shop; the online choice does not connect to a provider. Migration `004_add_pickup_payment_method.sql` stores the checkout method. Migration `005_create_storefront_announcements.sql` adds Admin-authored sale/event/raffle/announcement copy for the User dashboard. Migration `006_create_product_offers.sql` adds active product discounts and discount/free-unit snapshots on order items. Product offers apply to pickup checkout prices; BOGO free units are included in reservation and payment stock consumption. One active offer per product is enforced by the Admin workflow. The User dashboard shows current offers, newest active products, best sellers by paid-for units, and active announcements. The User order list and details/cancellation routes are `user/account/orders/index.php` and `user/account/orders/view.php`; order reads are scoped to the authenticated User. Admin order review is at `admin/orders/`; Admin confirmation records the order as paid and consumes reserved stock transactionally. Confirmation requires real review outside the demo for demo-online claims. Each order snapshots product names, SKUs, unit prices, offer title, discount amount, free-unit count and final line total.

Unpaid expiry is evaluated on catalog and User order requests. This releases overdue reservations exactly once under a database transaction, but release can be delayed until the next request; exact-time processing awaits verification of a local/hosting scheduler. The initial Admin catalog slice is implemented at `admin/index.php`, `admin/products/`, `admin/categories/` and `admin/inventory/`: Admins can add/edit products, set initial stock when creating a product, add/rename categories, upload optional product images and record later positive stock receipts with movement history. Initial stock and its `received` movement are saved together. The catalog is empty until products are added and stocked.

Admin in-store POS sales, paid-order cancellation/refund handling, account management and reports remain unimplemented. Pickup queue review and payment confirmation are implemented at `admin/orders/`; product offers and shop announcements are managed at `admin/promotions/`. Offers can be percentage off, fixed PHP off each purchased unit, or buy-X-get-Y-free, and affect pickup order pricing; announcement copy does not change checkout totals. The Admin product Delete action removes a product from the active catalog by setting `is_active` to false; order snapshots, stock movements, images and saved bag references are preserved. Category deletion/deactivation and negative stock corrections remain open decisions.

## Open business and operational decisions

Resolve before the dependent schema or workflow is implemented:

- Whether Admin may create additional Admin accounts, or whether the initial/only Admin is provisioned through a separate setup process.
- What refund outcomes must record (such as status, reference or notes) and who may record them; never collect/store card details.
- Whether Admin may override reservation expiry or restore stock manually, and required audit reason.
- How 48-hour expiry is triggered locally and on the chosen host. Decide after checking support for scheduled jobs; no scheduler is assumed.
- Editing pickup details/quantities after placement is not part of the current approved User scope. If it is later requested, define revalidation and reservation adjustment before adding it.
- Whether order prices include tax or fees; no tax/fee rule is approved. The initial proposed product-discount rules are listed above.
- Whether accounts may be deactivated and effects on historical records.
- Whether category deletion/deactivation is allowed, and how stock losses/corrections are reconciled. Product removal is implemented as deactivation to preserve dependent records.
- Whether the current local JPEG/PNG/WebP, 2 MiB image limit and `uploads/products/` storage arrangement fit the selected host; InfinityFree support remains unverified.
- Any additional functional assessment rubric.
- Whether the middle name is optional; the current signup implementation treats it as optional pending confirmation.

## Acceptance principle

Each implemented capability maps to a role, endpoint, validation rule, data impact, authorization rule and test. Do not treat the open decisions above as approved behavior. UI acceptance criteria are in `docs/07-ui-guidelines.md`.

