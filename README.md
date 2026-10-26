# Beauty Shop Inventory and Sales Management System

## Project overview and scope

The owner-approved functional scope is recorded in `docs/03-pages-and-features.md`: beauty product catalog, inventory and movement history, product image uploads, exactly two roles (Admin and User), in-store sales, cart-based online pickup orders with a demo online-payment choice or cash at the shop, Admin order review/payment confirmation, sales and inventory reports, and the specified cancellation/refund handling. The online-payment choice is simulated only; there is no GCash integration or real money transfer. Admin performs all staff and administrative duties. Users browse products and manage only their own pickup orders. Orders reserve stock on submission; unpaid reservations expire after 48 hours and reserved stock must be released exactly once. Detailed permissions and unresolved operational decisions are in `docs/04-roles-and-permissions.md` and `docs/05-database-design.md`.

The supplied assessment is a **deployment competency rubric**, not the functional scope. `docs/01-rubric-traceability.md` is the source of truth for its eight criteria and evidence. Do not describe shop features as rubric requirements.

## Technology and hosting direction

- Plain PHP with PDO, semantic HTML, CSS and vanilla JavaScript.
- MySQL/MariaDB; local development with XAMPP.
- Prepare for InfinityFree, but verify the actual account/plan capabilities before treating deployment instructions as confirmed.
- Do not add frameworks, build tools, Composer/Node packages or external services without an explained need and compatibility review.

## Planning and source of truth

- `AGENTS.md` â€” project-wide coding, security and workflow rules.
- `docs/01-rubric-traceability.md` â€” deployment assessment only.
- `docs/02-project-phases.md` â€” phased delivery and exit criteria.
- `docs/03-pages-and-features.md` â€” approved functional scope and open business decisions.
- `docs/04-roles-and-permissions.md` â€” authorization boundaries and unresolved grants.
- `docs/05-database-design.md` â€” data model and migration planning; not a schema.
- `docs/06-architecture-and-project-structure.md` â€” proposed app boundaries/layout.
- `docs/07-ui-guidelines.md` â€” UI and accessibility acceptance.
- `docs/08-security-guidelines.md` â€” application security requirements.
- `docs/09-testing-and-evidence-plan.md` â€” functional/security tests and rubric evidence workflow.
- `docs/10-infinityfree-deployment.md` â€” unverified host-investigation and deployment checklist.

Feature details belong in `docs/03-pages-and-features.md`; downstream documents reference rather than redefine them. Keep detailed permission grants in `docs/04-roles-and-permissions.md`, data/migration decisions in `docs/05-database-design.md`, and rubric traceability in `docs/01-rubric-traceability.md`.

## Current status

The public UI and account flow are in `public/`: visitors can register as Users with their name, optional middle name and phone number, email, and password confirmation; log in and log out. Successful User login opens `user/index.php`; Admin login opens `admin/index.php`. The User dashboard links to the catalog, own pickup orders, and profile. Users can edit their own names and phone number and change their password at `user/account/profile.php`; email is read-only. Password changes require the current password and matching new-password confirmation, store a hash, and rotate only the current session ID. Other active sessions remain valid.

The public catalog at `public/catalog/` supports search, product details, availability display and a per-User saved cart that persists across logout/login (migration 007). Checkout submits the saved cart as one pickup order, offers demo online payment or cash at the shop, and reserves stock transactionally. Migrations 004–007 for payment selection, shop announcements, product offers and persistent carts were applied locally on 2026-10-26. Users can review only their own orders under `user/account/orders/` and cancel an eligible pending unpaid order. Admins can review pickup orders at `admin/orders/` and confirm payment; confirmation consumes reserved stock and records an Admin-attributed order-history event. The demo online option does not connect to a payment provider. Reservations last 48 hours; expiry is processed when catalog or order pages are requested. Exact-time expiry still depends on a scheduled-job capability that has not been verified. Admins can add/edit products, add/rename categories, upload validated product images, record positive stock receipts, create product-level percentage/fixed/BOGO offers and publish/hide event, raffle and shop announcements. Discounts are calculated at pickup checkout; free BOGO units are included in inventory reservation. In-store POS sales, paid-order cancellation/refund management, negative stock corrections, account administration and reports remain unimplemented. Image upload rules and host storage need verification before deployment. Initial Admin provisioning and login throttling remain limitations before public deployment.

The application uses PDO with local settings in the ignored `.env` file. The public landing page is at `/beauty-shop/public/` under XAMPP. Registration/login/logout, catalog/order and Admin workflows have not been browser-tested. PHP syntax checks were run for the changed/new page and service files; migration 007 was recorded in the local migration ledger on 2026-10-26. Hosting configuration and `.htaccess` behavior must be verified separately before deployment. Placeholder image paths are listed in `docs/06-architecture-and-project-structure.md` for assets the owner plans to supply.




