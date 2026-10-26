# Testing and Deployment Evidence Plan

## Purpose and sources

Functional/security tests verify the approved scope in `docs/03-pages-and-features.md`; authorization expectations are in `docs/04-roles-and-permissions.md`, reservation invariants in `docs/05-database-design.md`, and deployment evidence only in `docs/01-rubric-traceability.md`. Tests are plans, not results. Keep `Not run` until actually executed. The exact report, role and refund permissions follow the decisions still open in their source documents.

## Functional and security test matrix

| ID | Test | Expected result | Status |
|---|---|---|---|
| ENV-01 | Start XAMPP Apache and MySQL/MariaDB | Services start; local PHP page loads | Partial: confirmed the already-running local Apache returned HTTP 200 and PDO connected to MariaDB 10.4.32 on 2026-10-25; startup/restart procedure was not exercised. |
| ENV-02 | Open project from `htdocs` | Page and static assets resolve | Not run |
| AUTH-01 | User registers with required first/last names, optional middle name/phone, email, matching password confirmation | Profile values persist; empty optional fields are NULL; password is hashed, confirmation is not stored, and role is User | Not run |
| AUTH-02 | Submit missing/overlong names, invalid phone/email, mismatched/invalid password confirmation, duplicate email, and invalid login | Rejected safely; no account enumeration or sensitive diagnostic; passwords are not redisplayed | Not run |
| AUTH-03 | Visitor/User directly requests Admin page and POST action | Denied server-side | Not run |
| AUTH-04 | User changes another User's order ID or submits an Admin role | Access/privilege denied | Not run |
| AUTH-05 | Submit login, registration or logout with a missing/invalid CSRF token | State change is rejected | Not run |
| AUTH-06 | Log out, then request a protected page | Session is invalidated and access is denied | Not run |
| AUTH-07 | Run `php tests/login-redirect.php` for User and Admin role destinations | User route maps to `/user/index.php`; Admin route maps to `/admin/index.php` | Pass: `Role-based login destinations passed.` on 2026-10-25 |
| AUTH-08 | Submit valid User and Admin logins through XAMPP and follow the resulting redirects | User lands on the User dashboard; Admin lands on the Admin workspace | Pass: temporary isolated-test accounts landed on their respective dashboards through local Apache on 2026-10-25; no passwords were stored in plaintext. |
| PROFILE-01 | As a User, update valid names/phone; submit missing, overlong, non-string and invalid phone values | Own profile updates; invalid values are rejected with safe field feedback; email and role remain unchanged | Not run |
| PROFILE-02 | As Visitor or Admin, request/post to the User profile page; as User, submit missing/invalid CSRF | Visitor is redirected to login, Admin is denied, invalid CSRF makes no change; User cannot target another account ID | Not run |
| PROFILE-03 | Change password with wrong current password, invalid length, mismatched confirmation, then valid values | Invalid attempts leave hash unchanged; valid change stores a verifiable hash and rotates the current session ID; other active sessions remain valid by owner decision | Not run |
| PROFILE-04 | Open User profile route through XAMPP at desktop and narrow mobile widths; exercise navigation and logout | Nested CSS/JS, shop/profile/logout links resolve; forms remain usable and labels/errors visible | Not run |
| DASHBOARD-01 | Visitor requests `/user/index.php` directly | Redirected to the public login page | Pass: local Apache returned HTTP 302 to `../public/login.php` on 2026-10-25 |
| DASHBOARD-02 | Admin requests `/user/index.php`; User requests it with a normal profile and with a missing/invalid DB row | Admin is denied; User sees their escaped saved first name, generic fallback when unavailable, and a working profile link | Not run |
| DASHBOARD-03 | Open the User home and profile at desktop and narrow mobile widths | Shared links and assets resolve; header/content do not overflow; keyboard focus remains visible | Not run |
| DASHBOARD-04 | Run `php tests/user-dashboard-responsive.php` | Narrow viewport stylesheet allows the sticky header and User actions to wrap | Pass: regression check passed on 2026-10-25 |
| DATA-01 | Apply approved migrations to empty local database | Approved constraints, indexes and relations exist | Partial: applied migrations 001–003 to a new isolated local database and verified the ledger and eight tables on 2026-10-25; indexes and foreign-key constraints were not separately inspected. |
| DATA-02 | Repeat migration procedure / simulate partial failure | Agreed ledger procedure safely refuses or handles repeat/failure | Not run; process decision open |
| DATA-03 | Cause database connection failure | Generic visitor response; protected diagnostic path only | Not run |
| CAT-01 | Browse/search/filter catalog and unavailable product | Results and empty/unavailable states are accurate | Not run |
| CAT-02 | Open product details directly as Visitor, User and Admin | Active product data is escaped; Visitors can browse; only Users see the reservation action | Not run |
| CAT-03 | Place an order with invalid IDs/quantities, stale stock or changed price | Server reloads current active products/prices/stock; invalid or insufficient requests create no partial order | Not run |
| CAT-04 | Browse an empty catalog and search with no matches at mobile/desktop widths | Helpful empty states appear; filters, pagination, image placeholders and navigation work | Not run |
| ADMIN-01 | Visitor and User directly request each Admin page and POST action | Visitor is redirected to login; User receives an authorization denial; no state changes occur | Partial: Visitor GETs and User GETs were checked on all five Admin pages; User POSTs to category, product and inventory routes were denied. Missing/invalid category and product CSRF left rows unchanged, and invalid inventory CSRF left stock unchanged. Visitor POST and every possible mutation route were not individually exercised; 2026-10-25. |
| ADMIN-02 | Admin adds/edits product fields and attempts invalid/duplicate values | Valid values persist; malformed or duplicate values are rejected without exposing database errors | Pass: create/edit, PHP price, duplicate SKU, invalid price/category and overlong name checks behaved as expected on the isolated database; 2026-10-25. |
| ADMIN-03 | Admin submits invalid, spoofed, oversized and valid product images | Only decoded JPEG/PNG/WebP within the local 2 MiB limit are saved under generated names; invalid uploads leave product data unchanged | Partial: valid PNG accepted with generated filename; spoofed PNG, unsupported GIF, truncated PNG and >2 MiB upload rejected without changing the image reference. JPEG/WebP acceptance and InfinityFree limits remain unverified; 2026-10-25. |
| ADMIN-04 | Admin adds/renames categories and records a stock receipt | Category names/slugs remain unique; positive stock and the matching `received` movement commit together with actor/reason | Partial: category add/rename, duplicate slug rejection, and one matching stock movement verified in the isolated database; rollback after a forced database failure was not exercised; 2026-10-25. |
| ADMIN-05 | Review product list, forms, category list and movement table at desktop/mobile | Labels, errors, focus, responsive layout and table scrolling are usable | Not run: no browser-based desktop/mobile visual review was available. |
| UPLOAD-01 | Upload invalid type, spoofed MIME, oversized or undecodable image | Rejected safely; no executable/public unsafe file | Partial: spoofed, unsupported, truncated and oversized uploads were rejected; valid PNG used a generated image filename. Script-execution behavior was not tested; 2026-10-25. |
| INV-01 | Receive stock and inspect movement history | Authorized positive receipt updates the balance and produces the matching audit movement; negative adjustments are unavailable until policy is approved | Pass: positive receipt changed on-hand quantity and produced one matching `received` movement with Admin actor/reason; a negative receipt was rejected without changing stock or history; 2026-10-25. |
| SALE-01 | Record in-store sale at valid and insufficient stock quantities | Server uses trusted prices; sale and stock deduction are atomic; insufficient stock is rejected | Not run |
| ORDER-01 | Add products to cart and submit “Buy all” with each supported payment choice | Cart items form one order; server reloads prices/stock; selected method is saved; stock reserves only at submission; online choice is clearly demo-only; order awaits Admin confirmation | Not run |
| ORDER-02 | Place concurrent orders/sales for the last units | No overselling; one operation is safely rejected or retried | Not run |
| ORDER-03 | Admin reviews an order and confirms reported online-demo payment or cash received | Admin-only transition recorded once with actor/time/method; User cannot self-confirm; reservation is consumed consistently | Not run |
| ORDER-04 | User cancels own pending unpaid order | Allowed only in eligible state; reserved stock released exactly once | Not run |
| ORDER-05 | User attempts to cancel paid/expired/another User's order | Denied without stock or order corruption | Not run |
| ORDER-06 | Expire reservation after 48 hours; repeat expiry concurrently | Expired order and stock release are consistent; repeated/concurrent execution releases exactly once | Not run; scheduler mechanism open |
| ORDER-07 | Admin cancels paid order and records refund outcome | Admin-only transition and applicable refund outcome audited; stock policy follows approved decision | Not run; details open |
| ORDER-08 | View own order and request another User's order ID | Owner sees only own snapshots/status history; another User's order returns not found | Not run |
| ORDER-09 | Cancel own pending unpaid order with valid, missing and invalid CSRF; repeat cancellation | Only a valid eligible request changes status; reserved quantity returns once and audit rows are consistent | Not run |
| ORDER-10 | Open catalog/order pages after an unpaid deadline | Order becomes expired and stock is released exactly once; release may wait until the next request | Not run; scheduler support open |
| ORDER-11 | Change cart quantities to zero/remove items, submit an empty cart, or submit invalid/unsupported payment method | Empty or malformed checkout is rejected without an order or reservation; removed items stay removed | Not run |
| ORDER-12 | Admin confirms payment with missing/invalid CSRF, as a User, repeatedly, or after expiry | Unauthorized/invalid/repeated action makes no duplicate transition or stock movement; expired order cannot become paid | Not run |
| REPORT-01 | Compare sales and inventory reports to source records | Values reconcile; only Admin can access reports | Not run |
| DEP-01 | Configure production DB without exposing credentials | Hosted connection succeeds using host-provided settings, not local defaults | Not run |
| DEP-02 | Visit actual assigned HTTPS URL if available | URL, certificate, routes and assets verified | Not run |
| DEP-03 | Exercise a critical hosted workflow | PHP, database and frontend integration works | Not run |
| DEP-04 | Inspect host diagnostics during controlled failure | Available logs/tools identified; secrets absent | Not run |
| DEP-05 | Repeat deployment from runbook | Another clean deployment is reproducible | Not run |

Adapt tests only when the approved scope changes; document the owner decision. Do not mark a planned test passed without evidence.

## Rubric evidence checklist

Use the eight criteria, point values and evidence descriptions in `docs/01-rubric-traceability.md`; this checklist is a reminder, not a second definition of rubric requirements.

- [ ] Hosting/runtime/services demonstrated.
- [ ] Actual source preparation and appropriate checks explained; no invented build.
- [ ] Local/production configuration explained with secrets hidden.
- [ ] Hosted database/schema process and verification demonstrated.
- [ ] Deployed frontend/backend/database request flow demonstrated.
- [ ] Actual URL, HTTPS, routes and host limits checked.
- [ ] Deployment issue diagnosed, corrected and retested.
- [ ] Reproducible runbook followed.

## Troubleshooting practice and evidence

Practice a database connection failure, missing asset, or PHP runtime error using only available diagnostics. Record steps, actual result and correction without secrets. Keep separate local and production evidence; never use production customer data for destructive tests.

| Date | Test/criterion | Steps and evidence (redacted) | Result | Issue/fix | Evidence location |
|---|---|---|---|---|---|
| YYYY-MM-DD | Replace with actual test | Actual steps | Pass / Fail / Blocked | Actual details | Screenshot/log reference |

