![Conduit](public/images/conduit-logo-final-v.svg)

# Conduit

Open-source CRM built for pipeline generation. It's also the foundation for a marketing automation tool I'm building next. Conduit is step one, not the end goal.

## Core design principle

Pipeline is the organizing object. Every account belongs to a pipeline and sits at exactly one stage in it. A contact is a person attached to an account. Contacts don't move through the pipeline, the account does.

Conduit doesn't run automation itself. That's a separate tool. But the data model is built around one hard constraint: automation can only ever attach to a stage transition - an account moving from one stage to another. No freeform workflow builder, no nested if/then logic, no branching conditions living in this database. A pipeline's stages are the entire, ordered list of places automation can hook in. That's it.

This is deliberate. Most CRMs let workflows sprawl into tangled logic that nobody on the team fully understands six months later. By keeping the extension point to "stage A → stage B," Conduit guarantees that whatever automation tool consumes its data stays legible. You can look at a pipeline and know exactly what can happen and when.

## What's in v1

- **Pipelines**: name, type, an ordered list of stages.
- **Accounts**: belong to a pipeline, sit at one `current_stage`. This is what actually moves through the pipeline.
- **Contacts**: people belonging to an account, name + email. No pipeline or stage of their own.
- Every account and contact gets a UUID, generated automatically, shown read-only. It's a real column on the model, structurally separate from Field data. No Field can ever collide with it.
- **Fields**: a managed resource for defining custom fields on accounts or contacts. Field definitions are shared across all records of that type; values are per-record. Shows up as a "Fields" section on the edit page, saved with the record's normal Save action. Pick which fields show as table columns from the standard column-visibility toggle.
- **Activity log** on every account and contact. Every field change (stage changes included) logged with old value, new value, who made it, most recent first. This is groundwork for the automation tool, which will need to react to field changes generally, not just stage changes.
- **CSV import** for accounts and contacts. Pipeline/stage (accounts) and the account (contacts) get picked once for the whole batch. Only name or email needs to be mapped per row.
- A model observer on `Account` that logs every stage change, as an early placeholder for exposing that transition externally (API or event, later).
- A Filament admin panel for pipelines, accounts, contacts, and fields.

Pipelines are locked to exactly two system-defined ones: self serve and enterprise. Their stages are editable; neither can be renamed, deleted, or joined by a third.

## What's coming later

- **Automation**: a separate tool, outside this codebase and this database, that reads pipeline, account, and contact data from Conduit (via an API or events) and acts on stage transitions. Conduit's job is only to model the pipeline and record the transition; it never stores automation definitions or executes actions itself.

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

Visit `http://localhost:8000/admin` and sign in with the test user above. You'll land straight on the Accounts list.
