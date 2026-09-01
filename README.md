![Conduit](public/images/conduit-logo-full.svg)

# Conduit

Conduit is an open-source CRM built as the foundation for a future marketing automation tool.

## Core design principle

The **pipeline** is the organizing object in Conduit. Every **account** belongs to a pipeline and sits at exactly one stage in that pipeline at a time. A **contact** is a person belonging to an account — contacts don't move through the pipeline themselves; the account they belong to does.

Conduit itself does not run automation — that lives entirely in a separate tool. But Conduit's data model is built around a hard constraint that keeps that future automation legible: the only thing an automation can ever attach to is a **stage transition** — an account moving from one stage to another. There is no freeform workflow builder, no nested if/then logic, no branching conditions living in this database. A pipeline's stages are the complete, ordered list of points where automation can hook in — nothing more.

This is a deliberate constraint, not a missing feature. Most CRMs let workflows grow into tangled, unreadable logic that nobody on the team fully understands anymore. By keeping the extension point to "stage A → stage B," Conduit guarantees that whatever automation tool consumes its data stays simple enough that anyone can look at a pipeline and know exactly what can happen and when.

## What's in v1

- **Pipelines** — each with a name, a type, and an ordered list of stages.
- **Accounts** — belong to a pipeline and sit at one `current_stage`. This is the entity that moves through pipeline stages.
- **Contacts** — people belonging to an account, each with a name and email. Contacts have no pipeline or stage of their own.
- Every account and contact has a UUID, generated automatically on creation and shown read-only thereafter, for external systems to reference them reliably. It's a real column on the model, structurally separate from Field data — no Field can ever be named into colliding with or overwriting it.
- **Fields** — a managed resource for defining custom fields on accounts or contacts (a name plus which entity it applies to). Field definitions are shared across all records of that type; values are per-record. An account or contact's edit page shows a "Fields" section with an input for every field defined for its type, saved as part of that record's normal Save action — no separate save step. The Accounts and Contacts list pages let you choose which fields to show as table columns via the standard column-visibility toggle.
- **Activity log** on every account and contact, most recent first, tracking every field change (including stage changes and Field value changes) with old/new values and who made them — via `spatie/laravel-activitylog`. This is groundwork for the future automation tool, which will need to react to field changes generally, not just stage transitions.
- CSV import for accounts and contacts, from their list pages in the admin panel. Pipeline/stage (for accounts) and the account (for contacts) are picked once for the whole batch, not mapped per row — only the name (accounts) or email (contacts) column needs mapping.
- A model observer on `Account` that logs every stage change to the application log, as an early placeholder for exposing that transition to the external automation tool (via an API or event) later.
- A Filament admin panel for managing pipelines, accounts, contacts, and fields — an account's edit page includes a relation manager for adding and viewing its contacts directly.

Only **self serve** pipelines are supported in v1.

## What's coming later

- **enterprise pipelines** — a second pipeline type for sales-led, multi-stage deal cycles, alongside self serve.
- **Automation** — a separate tool, outside this codebase and this database, that reads pipeline, account, and contact data from Conduit (via an API or events) and acts on stage transitions. Conduit's job is only to model the pipeline and record the transition; it never stores automation definitions or executes actions itself.

## Local setup

Requirements: PHP 8.3+, Composer, Node.js.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

The seeder creates a "Self serve" pipeline (`signed_up` → `activated` → `paying` → `churned`) with 5 example accounts, each with one or two contacts, plus a test admin user:

- Email: `test@example.com`
- Password: `password`

## Running the app

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` and sign in with the test user above — you'll land straight on the Accounts list.
