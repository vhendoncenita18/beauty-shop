# Cart and Demo Payment Implementation Plan

> **For agentic workers:** Use the native execution approach in this session and work task by task from this plan.

**Goal:** Add a session cart, demo online or cash-at-shop method selection, and Admin order/payment review while preserving current stock and order safeguards.

**Architecture:** Keep the cart in the PHP session and use the existing pickup-order service for transactional order creation. Store the chosen method on the order; Admin review records payment confirmation through the existing status history unless inspection shows it cannot represent the required actor, time and method audit.

**Tech Stack:** Plain PHP/PDO, MySQL/MariaDB, semantic HTML, CSS and vanilla JavaScript.

**Spec:** `docs/superpowers/specs/2026-10-26-cart-demo-payment-design.md`

## Global Constraints

- Do not add a framework, dependency, payment service or payment credentials.
- Online payment is demo-only and must never be represented as a real transfer.
- Reserve stock only when “Buy all” submits successfully; preserve the 48-hour unpaid reservation and exactly-once release behavior.
- Validate current products, prices, stock and payment method on the server; only Admin can confirm payment.
- Preserve authentication, role checks, CSRF protection, prepared SQL, order ownership, transactions and audit history.

## Review Focus

- Stale, inactive or insufficient-stock cart item: submission creates no partial order and reserves nothing.
- Malformed quantities or unsupported payment method: rejected server-side.
- User-submitted paid status or direct Admin request as User: denied without changes.
- Repeated Admin confirmation or cancellation/expiry race: no duplicate payment transition or stock release.
- Empty cart or invalid session cart: useful recovery feedback without warnings or data loss.

## File Map

- `app/services/pickup_orders.php`: validate cart and payment method, create one order transactionally, and add an Admin confirmation transition.
- `public/catalog/index.php`, `public/catalog/product.php`, `app/includes/site-header.php`, and existing public CSS/JS: add-to-cart, cart badge/panel, quantity/remove controls, checkout choice and motion feedback.
- `user/account/orders/index.php` and `view.php`: show selected method and demo explanation/status.
- `admin/orders/index.php` and `view.php` (or matching existing conventions): Admin-only queue, detail, and confirmation action.
- `database/migrations/004_add_pickup_payment_method.sql`: add the method snapshot and any minimal confirmation audit field only after checking the existing history schema.
- `docs/03-pages-and-features.md`, `docs/04-roles-and-permissions.md`, `docs/05-database-design.md`, `docs/07-ui-guidelines.md`, `docs/09-testing-and-evidence-plan.md`, and `README.md`: keep scope and implementation status aligned.

## Tasks

### Task 1: Add the payment-method schema

**Files:** Create `database/migrations/004_add_pickup_payment_method.sql`; modify `docs/05-database-design.md` and migration README.

- [ ] Inspect migration 003 definitions for `pickup_orders` and `pickup_order_status_history`; settle whether existing history can record payment confirmation actor/time/reason adequately.
- [ ] Write migration 004 with a non-null method snapshot constrained to `demo_online` or `cash_at_shop`, a safe default for existing orders (cash at shop), and only the minimal audit columns/table justified by the inspection.
- [ ] Document application order and existing-row treatment; do not apply to a production database.

### Task 2: Move catalog ordering into a cart

**Files:** Modify `public/catalog/index.php`, `public/catalog/product.php`, `app/includes/site-header.php`, existing CSS/JS and `app/services/pickup_orders.php`.

- [ ] Replace direct order-submit controls with add-to-cart actions; keep quantities in the session and validate positive bounded integers.
- [ ] Add header cart access and a usable cart view with item quantities, remove/update actions, totals from current server product data, payment choices and “Buy all.”
- [ ] Add accessible reduced-motion-aware feedback toward the cart icon; the cart remains usable without JavaScript animation.
- [ ] On submit, reload active products/prices and lock stock rows inside the existing transaction; reject an empty, stale or insufficient cart without partial reservation.
- [ ] Create exactly one pending order, snapshot its payment method, reserve inventory, write status history, and clear the cart only after commit.

### Task 3: Show method and status in User order pages

**Files:** Modify `user/account/orders/index.php` and `user/account/orders/view.php`.

- [ ] Display the selected method and clearly label `demo_online` as simulated with no provider connection or transfer.
- [ ] Show that Admin confirmation is required and preserve existing owner-scoped views, cancellation eligibility, and expiry wording.

### Task 4: Add Admin order review and confirmation

**Files:** Create `admin/orders/index.php` and `admin/orders/view.php`; modify `admin/index.php`, `app/services/pickup_orders.php`, and history/schema only if Task 1 requires it.

- [ ] List orders with customer, date, total, method and payment/order status; link to an escaped, complete order detail.
- [ ] Add a CSRF-protected Admin-only confirmation action with an appropriate confirmation prompt; record Admin actor/time/reason and transition to paid exactly once.
- [ ] Do not auto-confirm online-demo orders. Reject repeat, expired or otherwise ineligible transitions without altering stock/history.

### Task 5: Align documentation and perform requested-scope checks

**Files:** Modify `README.md`, feature/permission/data/UI/test docs and migration README as needed.

- [ ] Update implementation status and routes, distinguishing implemented work from remaining account/report/refund features.
- [ ] Record the actual checks run and outcomes; do not claim browser, payment-provider or host verification that did not occur.

## Execution Notes

Work sequentially because schema and service interfaces determine the User and Admin pages. Do not add or run automated tests unless the owner asks; keep planned test statuses unchanged and state that runtime verification remains outstanding.
