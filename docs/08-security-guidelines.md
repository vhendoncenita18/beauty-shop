# Security Guidelines

## Scope and ownership

These are application security requirements for features approved in `docs/03-pages-and-features.md`. Authorization grants are owned by `docs/04-roles-and-permissions.md`; reservation/data integrity requirements by `docs/05-database-design.md`. Do not treat this document as a source of new shop features.

## Accounts and authorization

- Hash passwords with `password_hash()` and verify with `password_verify()`; regenerate session ID after login and invalidate the session/cookie on logout.
- Set `HttpOnly`, `SameSite` and HTTPS-appropriate `Secure` cookie flags. Use generic authentication errors and proportionate login abuse protections compatible with the selected host.
- The initial local auth slice uses secure session cookies, CSRF tokens, generic login errors and hashed passwords. Login throttling is not implemented; select and verify a host-compatible abuse limit before public deployment.
- Users may self-register and must receive the User role server-side. Registration must not accept a role. Admin account creation and role assignment must use a controlled, server-authorized process; the initial Admin provisioning/recovery method remains open.
- Validate registration profile values on the server, use PDO prepared statements, and escape redisplayed names. Compare the password confirmation with the password, but never store or log the confirmation value; store only a `password_hash()` result. The password's byte-length policy remains 8–72 bytes.
- The User profile page reads and updates only the signed-in User ID from the session; it does not accept an account ID from request data. Email and role are not editable there. Password changes verify the current hash, validate matching new-password fields, store only the new password hash, and regenerate the current session ID. By owner decision for this phase, other active sessions are not revoked; document this limitation until a future session-revocation design is approved.
- Enforce permissions and customer ownership on every protected read/action, including direct requests. Protect every state-changing request with CSRF tokens.
- Public catalog reads expose active products only. Pickup-order placement and order pages require the User role; order reads and cancellation queries are scoped to both order ID and the signed-in User ID. Never accept customer, price, stock or status values as authority from the form.

## Input, output and database

- Validate all inputs server-side by type, length and allowed values; validate record IDs and access rights.
- Use PDO prepared statements for variable SQL values. Escape rendered output for its context, commonly `htmlspecialchars()` for HTML text/attributes.
- Never trust submitted product prices, stock counts, roles, order totals or status transitions. Re-read authoritative prices and stock from the database.
- Use transactions and concurrency control for sales, reservations, payment confirmation, cancellation/expiry and stock movement so partial updates or double allocation/release cannot occur.
- For the 48-hour unpaid pickup reservation, enforce a single terminal release: repeated expiry/cancel attempts and concurrent workers must release reserved units exactly once. Record auditable actor/reason/time where defined. Detailed data design is in `docs/05-database-design.md`.
- The current order service locks selected product rows, reads prices and availability from MySQL, then writes order snapshots and reservation movements in one InnoDB transaction. Cancellation and expiry update status, reserved balances, stock movements and order-status history together. Request-triggered expiry may be delayed; verify scheduler support before deployment.
- Do not expose raw exceptions, credentials, tokens or unnecessary personal data. Log diagnostics only in a protected host-supported location.

## Product image uploads

- Accept only the image formats actually selected; check extension, detected MIME/content, image decoding and size. Do not trust browser MIME or filename.
- Generate server-side filenames, prevent directory listing and PHP/script execution, and store outside executable/public paths where possible.
- Verify allowed formats, limits, permissions and storage paths against the chosen host before implementation/deployment. Do not allow active formats such as SVG without a reviewed safe handling need.
- Current Admin uploads accept JPEG, PNG and WebP up to 2 MiB, validate extension, detected MIME and decoded image content, use generated filenames and save under `uploads/products/`. A local Apache rule disables indexing and denies common script extensions. Verify actual storage and rule support with the chosen host before deployment; these local limits are not confirmed InfinityFree capabilities.
- Current Admin catalog routes use a server-side Admin gate; state-changing forms require CSRF. A stock receipt locks the product row and updates the balance plus its `received` movement record in one transaction. The product Delete action requires CSRF and sets `is_active` to false in a transaction, preserving linked order, stock, cart and image history while disabling linked offers. Category deletion/deactivation and negative stock corrections remain unavailable while their policies are open.

## Order and payment integrity

- Pickup payment is in person at the shop; Admin manually confirms it. Do not implement online payment or store raw card number/CVV.
- Users may cancel only their own pending unpaid order. Admin handles paid-order cancellation and records the applicable refund outcome, with permitted transitions and audit details to be defined before implementation.
- Recalculate totals from trusted product data. Pricing adjustments (tax, delivery, discounts or fees) are not approved and must remain absent until decided.
- Back up before production migrations or destructive operations. Never run unreviewed production SQL; verify HTTPS and host capabilities rather than assuming them.

