# Architecture and Project Structure

## Proposed architecture

Use server-rendered plain PHP. Browser requests reach Apache/PHP; PHP validates input and authorization, invokes focused application/data helpers, and uses PDO with MySQL/MariaDB; PHP returns HTML or a safe redirect/message. CSS and vanilla JavaScript enhance the interface but are not security boundaries. Approved features live in `docs/03-pages-and-features.md`; role rules in `docs/04-roles-and-permissions.md`; data rules in `docs/05-database-design.md`.

## Current UI shell and page-level structure

The root `admin/` and `user/` folders contain their role-specific page areas. `public/` contains the landing, authentication and public catalog screens. Paths marked **present** exist; **planned** paths are not implemented yet. User registration, login, logout, profile, catalog, product details, pickup ordering and own-order management connect to the local database. Successful User login redirects to `user/index.php`; Admin login redirects to `admin/index.php`. The profile page permits name/phone updates and password changes; it reads the email as read-only. There are no PHP entry-point files in the project root; open the landing page from `/beauty-shop/public/` locally.

```text
beauty-shop/
├── .env                               [local DB settings; ignored by Git]
├── .env.example                       [tracked starter values]
├── .htaccess                          [denies direct .env requests]
├── public/
│   ├── index.php                      [present: landing page]
│   ├── login.php                      [present: User/Admin authentication]
│   ├── register.php                   [present: creates User accounts]
│   ├── logout.php                     [present: POST + CSRF session logout]
│   └── catalog/
│       ├── index.php                  [present: search/browse and User reservation]
│       └── product.php                [present: product details and User reservation]
├── admin/
│   ├── index.php                      [present: Admin workspace]
│   ├── promotions/index.php           [present: product offers and storefront announcements]
│   ├── products/                      [present: product list and add/edit form]
│   ├── categories/index.php           [present: add/rename categories]
│   ├── inventory/index.php            [present: receive stock and movement history]
│   ├── sales/                         [directory present; pages planned]
│   ├── orders/                        [present: pickup order review/payment confirmation]
│   ├── users/                         [directory present; pages planned]
│   └── reports/                       [directory present; pages planned]
├── user/
│   ├── index.php                      [present: User account home]
│   └── account/                       [directory present]
│       ├── profile.php                [present: User's own profile and password]
│       └── orders/
│           ├── index.php              [present: User's own order history]
│           └── view.php               [present: own order details/cancellation]
├── app/
│   ├── auth/auth.php                  [present: session, CSRF, login/logout helpers]
│   ├── config/database.php            [present: PDO connection from local .env]
│   ├── includes/                     [header and footer present]
│   ├── services/pickup_orders.php    [present: reservation, expiry, cancellation and offer totals]
│   ├── services/admin_catalog.php   [present: product/category saves, image storage, stock receipts]
│   └── validation/                   [input validation helpers planned]
├── assets/
│   ├── css/styles.css                [present]
│   ├── js/                           [present: password, toast and confirmation behaviors]
│   └── images/                       [owner-supplied image placeholders]
├── uploads/products/                 [directory present; safe storage to verify]
├── database/
│   ├── .htaccess                      [denies direct database-file requests]
│   └── migrations/001-006_*.sql      [ordered schema; new migrations 005-006]
├── database/seeds/                   [no seed data yet]
├── docs/
└── tests/
```

The User home route `user/index.php` enforces the User role and shows active product offers, newest products, best sellers based on paid-for units, and Admin announcements. Public catalog and product-detail routes are implemented; browsing is public and order placement is User-only. User order queries include the session User ID, and cancellation is allowed only for that User's pending unpaid order. Reservation placement, cancellation and expiry update order status, reserved balances and audit rows transactionally. Product offer discounts are calculated server-side at checkout and snapshotted on order items; buy/get units are reserved and consumed from stock. Expiry is processed on catalog/order requests and may be delayed until a request arrives; hosting scheduler support is unverified. Admin product/category management, positive stock receipts, product-offer management, storefront announcements and pickup order review/payment confirmation are implemented. Admin in-store POS sales, paid-order cancellation/refunds, account administration, stock corrections and reports remain planned. Product uploads accept validated JPEG/PNG/WebP images up to 2 MiB in `uploads/products/`; a local Apache rule blocks common script extensions there, but storage and `.htaccess` behavior must be checked against actual hosting before deployment. Keep `.env` and SQL migration files inaccessible over HTTP; the local Apache deny rules are not yet verified for InfinityFree.

Current image references to supply later: `assets/images/hero-beauty.jpg`, `category-skincare.jpg`, `category-makeup.jpg`, `category-bodycare.jpg`, `account-care.jpg` and `account-ritual.jpg`. These are design placeholders, not uploaded assets or confirmed product content.

This is illustrative; exact filenames and directories are not approved requirements. Keep configuration and uploaded files outside executable/public locations where possible. InfinityFree's actual web-root and protection options must be checked before selecting deployment paths; see `docs/10-infinityfree-deployment.md`.

## Component boundaries

- Configuration/database connection: one shared PDO setup with environment-specific secrets kept out of source control.
- Shared request helpers: sessions, CSRF, contextual output escaping, safe errors.
- Authorization: reusable server-side Admin checks and User ownership checks; exactly two roles.
- Current User account page: `user/account/profile.php` handles own profile updates and password changes; password changes rotate the current session only.
- User account home: `user/index.php` displays a greeting and links to catalog, own orders and profile management.
- User ordering: `app/services/pickup_orders.php` owns quantity validation, locked stock reservation, UTC 48-hour expiry, idempotent reservation release and own pending-order cancellation; page templates call this service.
- Product/category management, image uploads, positive stock receipts and movement history use `app/services/admin_catalog.php`; Admin routes enforce the Admin role.
- In-store sales, Admin payment confirmation and paid-order cancellation/refunds remain planned Admin operations.
- Upload handling validates image format/content/size, generates safe names and stores files under the current local path; verify host protections before deployment.
- Tests and docs: keep functional/security checks aligned with `docs/09-testing-and-evidence-plan.md`.

## Dependencies and build preparation

Use native PHP, HTML, CSS and JavaScript unless an approved requirement justifies a dependency. Plain PHP may have no compiled production build; do not invent a build command. If a dependency is later proposed, explain its need and verify InfinityFree compatibility before approval. Deployment evidence requirements are only those in `docs/01-rubric-traceability.md`.

## Error handling

Development diagnostics may be detailed locally. Production visitors receive generic errors; diagnostic logs must be protected and must not contain credentials, tokens or unnecessary personal data. Verify the host's logging and writable-path capabilities before relying on them.



