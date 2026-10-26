# Cart and Demo Payment Design

## Intent

Let a signed-in User collect products in a cart, submit them as one pickup order, choose demo online payment or cash at the shop, and let Admin review the order and record payment confirmation.

## Goals

- Add-to-cart gives visible feedback, including motion toward the header cart icon; the icon opens the cart with its items and a “Buy all” action.
- Use the existing session and vanilla JavaScript patterns; do not add a framework or payment dependency.
- Keep cart contents temporary. Validate current active products, prices and stock on the server at submission. Reserve stock only when “Buy all” succeeds.
- Store the selected method (`demo_online` or `cash_at_shop`) with the pickup order.
- Demo online payment is a simulation only: no GCash connection, credentials, real transfer, or automatic paid status.
- Admin can view all orders, review method/status and confirm payment. Only Admin confirmation marks an order paid; User can view only their own orders and cannot set payment status.
- Preserve the existing 48-hour unpaid reservation, cancellation, expiry and exactly-once stock-release rules.

## Non-goals

- Real online payment processing, QR/payment credentials, gateway callbacks, cart persistence across devices, customer order editing, or new user roles.
- Admin refunds and paid-order cancellation policy remain separate unresolved work.

## Approaches considered

1. Session cart plus Admin-confirmed demo payment (recommended): small fit with the current PHP session and order workflow; no external provider and explicit manual review.
2. Persistent database cart: supports cross-device recovery but adds cart tables and lifecycle logic without a current requirement.
3. Auto-mark demo online orders paid: fewer Admin steps but falsely represents a transfer and defeats the requested review.

## Chosen flow

Use a session-backed cart. Users add products, open the header cart, adjust/remove quantities, select one of the two payment methods at checkout, and submit all items with “Buy all.” The server recalculates totals and reserves stock transactionally, creates one `pending_unpaid` pickup order, and snapshots the selected method. Admin reviews the order and, after verifying cash receipt or the demo payment claim outside the application, confirms payment. The demo does not verify an online transfer.

## Data and security

Add a reviewed migration for the order payment-method snapshot and any minimal audit fields required by Admin confirmation. Record the confirming Admin and timestamp through the existing order history or a focused payment history design. Never accept payment status from User input. Require existing authentication, role checks, CSRF protection, prepared SQL and server validation. Preserve current order ownership checks and transaction/locking rules.

## Files expected to change

- Catalog and shared header/cart UI in `public/catalog/` and `app/includes/site-header.php`, plus existing shared styles/scripts.
- Pickup order service in `app/services/pickup_orders.php` and User order views under `user/account/orders/`.
- Admin order-review route and navigation under `admin/`.
- A new ordered migration under `database/migrations/` and the corresponding database design notes.
- Functional/security cases in `docs/09-testing-and-evidence-plan.md`.

## Open implementation detail

Decide during schema review whether Admin payment confirmations can be represented adequately by `pickup_order_status_history` or need a focused payment event table; preserve an audit trail either way. Existing cancellation/refund policy remains out of scope.
