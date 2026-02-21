# GioSMS WHMCS Module — AI Development Instructions

## Role
You are building a production-grade WHMCS addon module called **GioSMS**.
You are NOT copying the reference module. It exists only so you can understand
WHMCS integration patterns, conventions, and how things work in practice.

## Source of Truth Files (Read These First)
1. `docs/GIOSMS_DOC.md` — GioSMS API: endpoints, auth, request/response, error codes
2. `reference/mimsms/` — Study every file deeply. Understand every pattern, every
   hook, every convention. Then decide how to do it better.

## Autoloading
Follow native WHMCS module conventions — NO Composer, NO PSR-4.
Use `require_once` for file loading. Must work on any shared hosting without Composer.

## Your First Job (Before Writing Any Code)
1. Read `docs/GIOSMS_DOC.md` fully — understand the GioSMS API completely
2. Read and deeply analyze the entire `reference/mimsms/` module — every file
3. From your analysis, propose:
   - The best folder and file structure for GioSMS
   - The database schema that fits the feature set properly
   - Which WHMCS hooks to register and why
   - Your overall architecture plan
4. Explain every decision and why it is better than the reference approach
5. Wait for my approval before writing a single line of code

You have complete freedom to decide the best architecture.
Let your analysis drive every decision — not assumptions.

## What the Module Must Do
- Send SMS via GioSMS API for WHMCS events (invoice, ticket, client, order actions)
- Each hook independently enable/disable toggleable
- Each hook has its own SMS template with merge field support
- Admin dashboard: live balance, SMS stats, API health status
- Full SMS delivery log with filters
- Bulk/Campaign SMS panel
- All settings via native WHMCS addon settings system

## Merge Fields to Support
```
{firstname}     {lastname}      {fullname}
{email}         {phonenumber}   {companyname}
{invoice_id}    {invoice_total} {due_date}
{ticket_id}     {ticket_subject}
{domain}        {product_name}
```

## Admin UI Requirements
- Bootstrap 4 and jQuery are already in WHMCS — do not import them again
- Modern card-based layout — not raw WHMCS table style
- Live balance widget via AJAX
- Per-hook toggle switches with individual template editors
- Merge field reference panel inside template editor
- SMS log filterable by date, status, hook type, client
- Character counter + SMS part counter in all template editors
- API health badge: green Connected / red Failed on every admin page

## WHMCS Best Practices (Follow All of These)
- Every PHP file must begin with `if (!defined('WHMCS')) die('Access denied');`
- Use `Illuminate\Database\Capsule\Manager` (Capsule) for all database operations
- Use `localAPI()` for all internal WHMCS data fetching inside hooks
- Use WHMCS Smarty engine for all template rendering — never echo HTML directly
- Follow WHMCS module function naming convention: `{modulename}_config()`,
  `{modulename}_activate()`, `{modulename}_deactivate()`, `{modulename}_output()`
- Register all hooks properly in `hooks.php` which WHMCS auto-loads
- Validate and sanitize all inputs before processing or saving
- All curl requests must have timeout set and handle failures gracefully

## Action Logging Requirements
Log every significant action to WHMCS Activity Log using `logActivity()`:
- Every SMS sent (success and failure) with mobile, hook name, status
- Every API connection failure with error detail
- Module activation and deactivation
- Settings saved
- Campaign/bulk SMS initiated and completed
- Any unexpected exception or error caught

Also maintain a detailed internal SMS log table (schema your decision)
that stores per-message records queryable from the admin log viewer.

## What Must Be Better Than Reference
- Cleaner, more logical file and folder organization
- Modern admin UI — card-based, not raw WHMCS tables
- Single dedicated API client class — no scattered inline API calls
- Per-hook template system — not one global SMS template
- Full SMS log with delivery status tracking
- Per-hook enable/disable control
- Proper error handling, timeout, retry logic on all API calls
- Meaningful activity log entries for every action

## Never Do
- Do not hardcode API key, sender ID, or API URL anywhere
- Do not put business logic or database queries inside template files
- Do not call the GioSMS API directly inside hook functions — always via API client
- Do not import Bootstrap, jQuery, or any library already present in WHMCS
- Do not copy mimsms code — understand the pattern, write it properly
- Do not begin coding until your full plan is presented and approved by me