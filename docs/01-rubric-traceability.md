# Deployment Rubric Traceability

## Source and scope

The supplied `INDIVIDUAL SME WEB APPLICATION DEPLOYMENT ASSESSMENT — DEPLOYMENT COMPETENCY RUBRIC` is the source of deployment requirements. It assesses deployment competence, not the shop's functional features. Functional scope is defined in `docs/03-pages-and-features.md` and was approved by the owner; do not attribute those features to this rubric.

## Criteria and evidence

| Rubric criterion | Points | Planned evidence (record only after performed) |
|---|---:|---|
| Hosting and environment setup | 15 | XAMPP and hosting dashboard, PHP/runtime version, database service, project location, relevant settings; explain each service. |
| Production build preparation | 15 | Source preparation and applicable PHP syntax checks; explain whether a compiled build exists. Plain PHP does not imply one. |
| Environment variables and configuration | 15 | Show local/production configuration locations and a safe configuration change without revealing secrets; explain host differences. |
| Production database deployment | 15 | Hosted database setup, reviewed schema application, schema verification, and a successful application database request; hide credentials. |
| Frontend and backend deployment | 15 | Deployed files, live page, working backend feature, database interaction, and browser-to-PHP-to-database explanation. |
| Domain, HTTPS, and network configuration | 10 | Actual assigned URL, HTTPS status, routes/assets and supported network settings; explain verified limitations. |
| Deployment verification and troubleshooting | 10 | Smoke checks and one deployment issue diagnosed, corrected, and retested using available logs/tools. |
| Deployment documentation and reproducibility | 5 | Repeat deployment using an updated runbook with real steps, verification, and rollback guidance. |

**Total: 100 points.** The eight rows above are the rubric criteria; shop features are not extra rubric criteria.

## Assessment preparation

The supplied assessment calls for an individual demonstration, explanation of one or two technical decisions, and a troubleshooting challenge. The student should be able to explain XAMPP, the actual build/check process, local versus hosted configuration, schema application, request flow, URL/HTTPS verification, and a database/configuration failure. Use the evidence log below and `docs/09-testing-and-evidence-plan.md`; never mark unperformed evidence complete.

| Date | Criterion | Evidence demonstrated (redact secrets) | Result | Follow-up |
|---|---|---|---|---|
| YYYY-MM-DD | Replace with actual criterion | Describe actual evidence | Pass / Needs work | Action |

