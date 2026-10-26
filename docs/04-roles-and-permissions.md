# Roles and Permissions

## Source of truth

The approved capabilities are in `docs/03-pages-and-features.md`. This document owns authorization boundaries. There are exactly two application roles: **Admin** and **User**. A visitor is an unauthenticated state, not a third role. The Admin performs all operational and administrative duties. Users browse products and manage their own accounts and pickup orders.

## Permission matrix

| Capability | User | Admin |
|---|---|---|
| Browse/search catalog and view product details | Yes | Yes |
| Register | Yes; assigned User role only | No public self-registration |
| View/update own profile | Yes | Yes |
| Manage own cart, choose demo online payment or cash at shop, place pickup order and cancel own pending unpaid order | Yes | No; Admin manages orders operationally |
| View/manage all User pickup orders; view selected payment method; confirm reported online payment or cash received; cancel paid orders and record refund outcomes | No | Yes |
| Record in-store sales | No | Yes |
| Create/hide product discounts and publish/hide shop updates | No | Yes |
| Manage catalog, categories, product images, stock and movement history | No | Yes |
| View sales and inventory reports | No | Yes |
| Create/manage accounts and assign roles | No | Admin-only; creation/recovery process for Admin accounts remains an open decision |
| Access application configuration/secrets through ordinary UI | No | No |

The implemented Admin routes are `admin/index.php`, `admin/products/`, `admin/categories/`, `admin/inventory/`, `admin/orders/` and `admin/promotions/`. Each route requires an authenticated Admin. Admins can add/edit products, add/rename categories, record positive stock receipts, review pickup orders and confirm payment, publish/hide storefront announcement copy, and create/hide one active product discount per product. The Admin product Delete action deactivates products and preserves history; category deletion/deactivation, negative stock corrections, in-store POS sales, report pages and account-management pages remain unimplemented.

The order-review page at `admin/orders/` is Admin-only. Users may submit only their own cart and view their own resulting orders. A submitted payment method is validated against the supported choices; the User cannot set an order to paid. Only an authorized Admin action can confirm payment. The demo online option does not collect credentials or represent a provider confirmation.

Admin operational actions must be distinguishable from a User's own account/order actions. Admin accounts do not place User orders under this role model; do not add a third role to represent staff.

## Enforcement and safeguards

- Check authentication and required role server-side before every protected read and mutation; hiding links is not authorization.
- Enforce User ownership of profiles and pickup orders in queries and business logic.
- Registration must assign the User role on the server and reject submitted role values.
- Protect all state-changing requests with CSRF tokens.
- Define the initial Admin provisioning/recovery process and prevent accidental removal of the last active Admin before account management is implemented.
- Define how account deactivation affects historical orders before implementing deactivation.
- Audit sensitive Admin actions without logging passwords, session tokens or secrets.

## Required authorization checks

- Visitor direct requests to Admin routes/actions are denied.
- User direct requests to Admin actions are denied, including POST requests.
- User cannot view or modify another User's order by changing an identifier.
- User cannot cancel paid, expired, or otherwise ineligible orders.
- Submitted role values cannot grant Admin access; invalid/expired sessions cannot perform protected actions.
- Repeated or concurrent Admin/order-expiry actions cannot release or consume stock more than once.

