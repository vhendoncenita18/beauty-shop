# User Profile Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let a signed-in User update their own name/phone details and change their password safely.

**Architecture:** Add small shared profile/password validators and use them from registration and the new User profile page. Keep the page server-rendered, authorize from the session role, constrain database reads/writes by the session user ID, and use the existing shared layout and success popup with nested-route-aware links. The password form verifies the current hash and updates the existing hash column; no SQL migration is needed.

**Tech Stack:** PHP 8-compatible syntax, PDO, MySQL/MariaDB, HTML, CSS, vanilla JavaScript. No added dependencies.

**Spec:** `docs/superpowers/specs/2026-10-25-user-pages-design.md` (Phase 1)

## Global Constraints

- Exactly two roles: Admin and User.
- Use PDO prepared statements for variable input in SQL.
- Validate all input on the server and escape rendered output contextually.
- Protect state-changing form requests with CSRF tokens.
- Enforce authorization on the server for every protected page and action.
- Keep CSS organized and responsive, with accessible labels, keyboard focus, readable contrast, and useful validation messages.
- Do not add a framework, build tool, Composer package, Node package, or external service.
- Do not generate SQL for Phase 1; the existing profile columns are already in migration `002_add_user_profile_fields.sql`.

## Review Focus

1. Tampered or missing profile values must be rejected without PHP warnings or unsafe redisplay. Test missing names, overlong names, non-string input, invalid phone, and valid optional blanks.
2. A User must never read or update another account. Test direct GET/POST access as Visitor and Admin, and confirm all SQL uses only the session ID.
3. A missing/invalid CSRF token must prevent profile changes. Exercise an invalid token in the browser.
   Successful password changes rotate only the current session ID; other existing sessions remain active by owner choice.
4. Password changes must verify the current password, enforce 8–72 bytes, require matching confirmation, hash only the new password, and never redisplay password values. Confirm incorrect current passwords leave the hash unchanged.
5. Nested URL paths must load CSS, JavaScript, home navigation and logout correctly. Test the profile route through XAMPP at desktop and mobile widths; confirm profile updates leave email and password unchanged.

## File Map

- Create `app/validation/user_profile.php`: normalize and validate first name, optional middle name, last name, optional phone, and new-password confirmation using existing limits.
- Create `tests/user-profile-validation.php`: dependency-free PHP checks for valid/invalid profile inputs and password-pair validation.
- Modify `public/register.php`: call the shared validators rather than duplicate profile/password validation; preserve current registration flow.
- Create `user/account/profile.php`: User-only profile read/update and password-change page, CSRF-protected POST, PRG redirect, session-backed success popup, and field-level errors.
- Modify `app/includes/site-header.php` and `app/includes/site-footer.php`: accept nested-route asset/public/logout/profile URLs while preserving existing public-page defaults; show a profile link for signed-in Users.
- Modify `assets/css/styles.css`: responsive profile form styling using the current shared rounded control and focus styles.
- Update `README.md`, `docs/06-architecture-and-project-structure.md`, `docs/07-ui-guidelines.md`, `docs/08-security-guidelines.md`, and `docs/09-testing-and-evidence-plan.md`: describe the implemented route/password feature, document the session revocation limitation, and add planned profile/authorization checks without claiming they passed.

## Task 1: Shared Profile Validation

**Interfaces:** `validate_user_profile(array $input): array` returns `['values' => ['first_name' => string, 'middle_name' => string, 'last_name' => string, 'phone_number' => string], 'errors' => array<string, string>]`. `validate_password_pair(mixed $password, mixed $confirmation): array` returns normalized values and field errors for an 8–72 byte password and matching confirmation. Missing/non-string required profile values are invalid; optional blanks normalize to empty strings. Names follow existing limits (required first/last, up to 100 characters; optional middle up to 100). Phone is optional, up to 30 characters, and must include a digit when supplied.

- [ ] Add `tests/user-profile-validation.php` with checks for a valid profile, valid blank optional fields, missing first/last names, overlong names, non-string values, invalid phone input, invalid password length, and mismatched confirmation.
- [ ] Run `php tests/user-profile-validation.php`; verify it fails because `validate_user_profile()` is not available yet.
- [ ] Implement `app/validation/user_profile.php` with the exact return shape above and no database/session dependency.
- [ ] Rerun `php tests/user-profile-validation.php`; verify all assertions pass.
- [ ] Add `validate_password_pair()` and refactor `public/register.php` to use the shared profile/password validators while preserving email validation and safe redisplay.
- [ ] Run `php -l app/validation/user_profile.php` and `php -l public/register.php`.

## Task 2: User-Owned Profile Page and Navigation

**Consumes:** `current_user(): ?array`, `csrf_token(): string`, `csrf_token_is_valid(mixed $submittedToken): bool`, `database_connection(): PDO`, and `validate_user_profile(array $input): array`.

**Produces:** `user/account/profile.php`; the page accepts no account ID from request input. It loads and updates only `current_user()['id']`. A Visitor is redirected to `../../public/login.php`; a signed-in non-User receives HTTP 403. Email is read-only. Profile updates change only names and phone. A separate password POST requires the current password, validates matching new-password fields, stores only a hash, and regenerates the current session ID. Other active sessions are not revoked. Valid POSTs store a one-time session success message and redirect to `profile.php`; errors are escaped and remain visible.

- [ ] Add a manual check list for a Visitor, User, and Admin, plus valid update, invalid fields, and invalid CSRF submissions.
- [ ] Implement the page with prepared `SELECT`/`UPDATE` statements constrained by the session user ID; do not accept an ID from GET/POST. Keep profile and password changes in separate forms so one action cannot accidentally apply the other.
- [ ] Add nested-route URL variables to the shared header/footer with current public-page defaults; render the signed-in User’s profile link and correct nested asset, shop, and logout URLs.
- [ ] Add responsive profile form CSS; keep visible labels, the existing rounded control radius, keyboard focus, and mobile stacking.
- [ ] Run `php -l user/account/profile.php`, `php -l app/includes/site-header.php`, and `php -l app/includes/site-footer.php`.
- [ ] Manually test the profile route in XAMPP as Visitor, User and Admin; verify update, redirect, popup, CSRF rejection, ownership boundary, assets, logout, and narrow-screen layout.

## Task 3: Documentation and Final Review

- [ ] Update the architecture map so `user/account/profile.php` is marked present and describe role enforcement.
- [ ] Update UI guidance for User profile form and feedback behavior.
- [ ] Update security guidance to state that changing a password rotates the current session but does not revoke other active sessions in this phase.
- [ ] Add profile update and direct-access cases to `docs/09-testing-and-evidence-plan.md`, leaving statuses `Not run` until actually tested.
- [ ] Re-run the dependency-free validation check and all PHP syntax checks from Tasks 1–2.
- [ ] Review the final diff manually for session-ID-only queries, CSRF enforcement, escaping, unchanged email/role during profile edits, correct password verification/hash/session rotation, and nested paths.

## Scope After Phase 1

Stop after the User profile slice and report its files, checks, results and remaining limitations. Do not implement catalog/order pages or create/apply SQL migrations in this phase. Those phases require their own approved data design; order work additionally depends on the open stock, state-transition and expiry decisions in the design document and `docs/05-database-design.md`.
