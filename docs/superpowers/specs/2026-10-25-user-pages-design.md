# Beauty Shop User Pages Design

## Intent

Deliver the approved User experience in small working phases: account/profile, product browsing, and pickup orders. The system uses plain PHP/PDO, MySQL/MariaDB, semantic HTML, CSS and vanilla JavaScript on XAMPP, with later hosting limited by verified InfinityFree capabilities. The owner selected phased delivery and PHP (Philippine peso) for prices and totals.

## Approaches considered

1. **Build all User features and schema at once.** Fastest apparent route, but it couples account, catalog and money/stock workflows before the unresolved reservation and pricing details are reviewed. Rejected for this beginner-friendly project.
2. **Create UI-only pages.** Allows early visual review, but would not satisfy the request for working functionality and risks presenting nonfunctional order actions. Rejected as the delivery approach.
3. **Phased vertical slices (selected).** Deliver and verify account/profile first, then catalog browsing with its reviewed data model, then pickup ordering after order, reservation, and expiry decisions are approved. This matches the projectâ€™s phase rules and lets each change be reviewed independently.

## Approved requirements and constraints

- Exactly two roles: Admin and User. A User may register, browse products, manage their own profile and pickup orders, and cancel only their own pending unpaid order. Admin has operational duties and manually confirms payment made at the shop.
- Online payment is out of scope. New User accounts receive the User role server-side. Requests and database access use CSRF protection, server validation, PDO prepared statements, contextual escaping, ownership checks, transactions and concurrency protections as applicable.
- Pickup reservations expire after 48 hours and reserved stock must be released exactly once. Do not assume a scheduler is available on InfinityFree; verify actual local/host capabilities before selecting the expiry mechanism.
- Use PHP (Philippine peso) for product prices/order totals. Do not add tax, fees or discounts; none are approved in the current scope.
- Display prices with the explicit `PHP` currency code (for example, `PHP 1,234.00`) so the UI does not rely on a font-specific currency symbol.
- No new libraries, frameworks, Composer/Node packages, or remote image/font dependencies. Product image paths may refer to assets the owner will add later.

## Phase 1: User account and profile

### Goal

Let a signed-in User view and update their own existing profile fields. The current `users` table already contains first name, optional middle name, last name, optional phone number, email, password hash, role and ID. The first phase therefore needs no database migration.

### Proposed profile behavior for owner review

- Route: `user/account/profile.php`.
- Only an authenticated `user` role may load or submit the page; Admin and visitors are denied or redirected according to the existing authentication pattern. Every profile query is constrained by the authenticated session user ID.
- First, middle, last and phone fields may be updated with the existing registration validation limits. Email is displayed read-only because it is the login identifier.
- A separate password-change form requires the current password, a new password and confirmation. Verify the current password with `password_verify()`, enforce the existing 8â€“72 byte password policy, compare the new password and confirmation, store only `password_hash()`, never redisplay passwords, and regenerate the current session ID after success. Other active sessions are not revoked in this phase because the app has no session registry; document this limitation.
- Use POST + CSRF and Post/Redirect/Get. Show success through the existing shared five-second popup and keep validation/database errors visible beside the form.
- Do not expose another Userâ€™s profile by accepting a user ID from the URL or form.
- UI reuses the current site styles, rounded controls, visible labels/focus and responsive layout. No supplied product/profile image is required.

The owner approved editing names and phone, keeping email read-only, allowing password changes, requiring the current password plus matching new-password confirmation, and rotating only the current session after a password change. Other active sessions remain valid until they end normally.

### Candidate files for Phase 1

- Create `user/account/profile.php` for the page and request handling.
- Modify `app/auth/auth.php` only if a small reusable User-only authorization helper is needed; retain existing CSRF/session behavior.
- Modify shared navigation/URL handling only as required to make the profile route and assets resolve from the nested User path.
- Modify `assets/css/styles.css` for responsive profile layout using the existing visual language.
- Add a no-dependency check under `tests/` before production changes, following test-first development; add a manual browser checklist for real session/CSRF/database behavior.
- Update relevant project documentation after behavior is implemented and verified.

## Later User phases

### Owner authorization update (2026-10-25)

The owner later explicitly requested the catalog, product details, pickup-order history/details and cancellation functionality together with an additive SQL migration. This authorization supersedes the earlier plan to wait for separate phase approvals. Implementation uses only approved User behavior; it does not add order editing, fees, tax, discounts, online payment, or seeded products.

The first implementation uses request-triggered expiry processing as a local fallback: catalog/order requests expire overdue orders transactionally and release reserved quantities once. This does not guarantee an exact-time release without a scheduled job. Hosting scheduler support remains unverified, and the catalog remains empty until products are entered through a future Admin workflow.

### Product browsing

Public catalog and product-detail views are implemented at `public/catalog/index.php` and `public/catalog/product.php`, backed by migration 003. They display sellable availability, never reserved-stock counts. Product records and owner-provided images are not assumed to exist; the empty state explains that the catalog is awaiting products.

### Pickup orders

User order placement, own order history/details and eligible pending-unpaid cancellation are implemented. Store a PHP-currency price snapshot per order line; compute totals from database prices, never submitted prices. Transactions and row locks protect reservations; conditional transitions make cancellation and 48-hour expiry release a reservation once. Payment remains at the shop and is confirmed by Admin; online payment and User cancellation of paid orders are not implemented.

User order quantity/detail edits are not part of the approved scope and are not implemented. Admin payment confirmation, paid-order cancellation/refund outcomes, and exact-time expiry remain later work.

## Open decisions before catalog/order phases

- Verify local and eventual host support for scheduled expiry before relying on exact-time stock release; current request-triggered processing can be delayed until the next catalog/order request.
- At the time this design was written, product entry was not implemented. The later Admin catalog slice adds product/category editing and stock receipts; this does not change the User-page scope.

## Non-goals

- No Admin pages, inventory-management interface, sales reports, payment processing, online payment, paid-order User cancellation, or hosting deployment in this User-pages slice.
- Phase 1 profile work needed no migration. Migration 003 now adds the catalog/order data for the authorized User pages; it is applied locally only.
- Do not install dependencies or introduce a build system.

## Verification

For Phase 1, verify PHP syntax and the no-dependency checks; manually test as Visitor, User and Admin, including CSRF rejection, own-profile update, invalid field input, and direct nested-route asset/navigation loading. Test at desktop and narrow mobile widths. Do not mark any planned check passed until run. For later phases use the existing CAT, ORDER, authorization and reservation tests in `docs/09-testing-and-evidence-plan.md`, extending that matrix only when implementation adds a testable behavior.

## Decisions left to implementation after approval

Choose exact wording, small layout details, and existing-style CSS selectors. Keep those choices beginner-readable and consistent with the current pages. No unresolved decision above may be silently decided in code.

