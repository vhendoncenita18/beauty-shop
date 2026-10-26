# Agent Instructions --- Beauty Shop Web Application

## Project goal

Build a beginner-friendly Beauty Shop Inventory and Sales Management
System using PHP, HTML, CSS, vanilla JavaScript, and MySQL/MariaDB.
Develop locally with XAMPP and prepare for InfinityFree after verifying
actual host limitations. The owner has approved the functional scope in
`docs/03-pages-and-features.md`; unresolved operational details remain
open in that document and its linked permission/data plans. The supplied
assessment rubric is a **deployment competency rubric** and does not
define shop features.

## Source of truth and workflow

1.  Read `README.md` and the relevant files in `docs/` before planning
    or editing.
2.  Use the supplied deployment assessment in
    `docs/01-rubric-traceability.md` as the source of truth for
    deployment evidence and assessment preparation.
3.  Do not silently invent requirements. Mark assumptions as `Proposed`
    and ask the owner when a decision affects scope, money, order
    handling, or deployment compatibility. Use `docs/03-pages-and-features.md`
    as the functional scope source of truth; role grants, data/migration
    policy and rubric mapping belong to `docs/04-roles-and-permissions.md`,
    `docs/05-database-design.md` and `docs/01-rubric-traceability.md`.
4.  Work in small, explainable phases. Before editing, inspect the
    existing project and preserve working code.
5.  Explain what each step does, why it is needed, how to run it, how to
    verify it, and how it connects to the whole application.
6.  Do not generate the entire application or database in one large
    unreviewed change.
7.  After each phase, summarize files changed, tests performed,
    outcomes, known issues, and the next step.

## Technology constraints

-   Backend: plain PHP, using PDO for database access unless the owner
    approves another approach.
-   Frontend: semantic HTML, CSS, and vanilla JavaScript unless a
    requirement justifies a dependency.
-   Local environment: XAMPP (Apache + PHP + MySQL/MariaDB, depending on
    the installed bundle).
-   Target hosting: InfinityFree, subject to verification of its current
    PHP, database, HTTPS, upload, and configuration limitations.
-   Database: MySQL-compatible relational schema; SQL migrations are
    versioned and kept in `database/migrations/`.
-   Do not add a framework, build tool, Composer package, Node package,
    or external service without explaining the benefit and verifying
    hosting compatibility.

## Security requirements

-   Use PDO prepared statements for variable input in SQL.
-   Hash passwords with PHP `password_hash()` and verify with
    `password_verify()`. Never store plaintext passwords.
-   Validate all input on the server and escape rendered output
    contextually.
-   Protect state-changing form requests with CSRF tokens.
-   Regenerate the session ID after successful login; use secure cookie
    settings appropriate to HTTPS support.
-   Enforce authorization on the server for every protected page and
    action. Hiding a link is not authorization.
-   Never trust a submitted role, product price, stock quantity,
    discount, or order total.
-   Restrict image uploads by extension, MIME/content checks, file size,
    and safe storage. Do not permit uploaded scripts to execute.
-   Do not expose stack traces, database credentials, or detailed
    production errors to visitors.
-   Keep secrets out of Git and public files. Use host-supported private
    configuration where available; if hosting restrictions require a
    different method, document the trade-offs and protect the file.
-   Use transactions and concurrency protection for in-store sales,
    pickup reservations, payment confirmation, cancellation/expiry and
    associated stock updates. Unpaid pickup reservations expire after
    48 hours; release reserved stock exactly once, including on repeated
    or concurrent processing. See `docs/05-database-design.md`.
-   Do not perform destructive production database operations without a
    verified backup and explicit approval.

## Database and migration policy

-   Design the ERD and table definitions before implementing dependent
    features.
-   Create ordered, repeatable-by-documentation SQL migration files with
    a unique sequence and purpose, for example `001_create_users.sql`.
-   Maintain a migration ledger table (planned name:
    `schema_migrations`) so applied migrations can be tracked. Decide
    the exact implementation before creating the first migration.
-   Keep schema migrations separate from sample data and production
    data.
-   Test migrations against a fresh local database and verify
    constraints, indexes, and relationships.
-   Never assume the local XAMPP database automatically exists on
    InfinityFree. Create the hosted database and import/apply the schema
    separately.
-   Do not include real user data, real passwords, or production
    credentials in seed files or commits.
-   Before each production migration, back up the database and record
    the migration being applied.

## Deployment and assessment evidence

The rubric assesses eight areas: hosting/environment setup (15),
production build preparation (15), environment variables/configuration
(15), production database deployment (15), frontend/backend deployment
(15), domain/HTTPS/network configuration (10),
verification/troubleshooting (10), and deployment
documentation/reproducibility (5). - Do not claim a build step exists
unless the chosen stack actually has one. For plain PHP, document the
absence of a compiled production build and demonstrate syntax checks,
configuration validation, and smoke tests as appropriate. - Record the
exact hosting setup, PHP version, database configuration method, URLs,
deployment steps, and troubleshooting evidence. Never record secret
values. - Demonstrate personal understanding: the owner should be able
to explain and repeat each step, not merely open the live site. - Test
local and production environments separately. Do not use `localhost` as
a production database/API hostname. - Verify HTTPS and routing only
after checking current host capabilities and actual deployment
settings. - Collect evidence for every rubric criterion; see
`docs/01-rubric-traceability.md` and
`docs/09-testing-and-evidence-plan.md`.

## Coding conventions

-   Use clear, descriptive file and function names.
-   Separate shared layout, configuration, database access,
    authentication/authorization, and feature logic.
-   Avoid duplicate database connection code and duplicated validation
    rules.
-   Use prepared statements and explicit error handling.
-   Keep CSS organized and responsive, with accessible labels, keyboard
    focus, readable contrast, and useful validation messages.
-   Do not commit generated logs, secrets, backups, or uploaded customer
    files.
-   Update documentation when a decision, setup command, migration,
    route, or deployment procedure changes.

## Definition of done

A task is complete only when: 1. The change matches an approved
requirement. 2. Relevant code has been inspected and explained. 3.
Tests/checks and their results are recorded. 4. Authorization and input
validation have been considered. 5. Documentation is updated when
needed. 6. No secrets or unverified claims are added.

Codex skill workflow

- This project uses the OpenAI Codex extension in VS Code.
- Before starting a task, identify relevant skills available under ".agents/skills/" and read their "SKILL.md" instructions when applicable.
- Use relevant skills automatically when their descriptions match the task. Do not invoke every installed skill for every task.
- Prefer the smallest set of skills that adequately covers the task. For example, use planning skills for requirements work, UI/UX skills for interface design, and debugging or testing skills when investigating defects or verifying changes.
- Explicitly requested skills should be used when compatible with the task and available tools.
- Treat skill instructions as workflow guidance, subordinate to project requirements, security rules, and explicit owner decisions.
- If a skill requires an unavailable tool, external service, or unsupported command, explain the limitation and use a suitable alternative. Never claim that a skill or its scripts ran unless verified.
- Do not install dependencies, modify global Codex settings, or change skill files without approval.
- Before editing project files, inspect the relevant existing code and documentation. Work in small phases, explain the changes, and report the checks actually performed.
- Do not generate application code or database migrations during planning and audit tasks unless explicitly authorized.
- For every completed phase, summarize files changed, checks performed and their results, known limitations, and the next recommended step.
