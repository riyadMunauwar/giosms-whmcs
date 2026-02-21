# GioSMS — WHMCS SMS Notification Module

> Send automated SMS notifications for every critical WHMCS event via the [GioSMS](https://giosms.com) API. Built for reliability, flexibility, and production use.

---

## Overview

**GioSMS** is a professional WHMCS addon module that connects your WHMCS installation to the GioSMS API, enabling automated SMS notifications for invoices, support tickets, orders, domain registrations, and more. Every hook is independently configurable with its own template and merge fields, giving you full control over every message your clients and team receive.

---

## Features

- **19 Event Hooks** — Covers invoices, tickets, orders, domains, and client lifecycle
- **Per-Hook Templates** — Each hook has its own SMS template with merge field support
- **Per-Hook Enable/Disable** — Toggle any hook independently without affecting others
- **Admin Dashboard** — Live balance widget, SMS statistics, and API health badge
- **Full SMS Delivery Log** — Filterable by date, status, hook type, client, and recipient
- **Bulk / Campaign SMS** — Send to a custom recipient list directly from the admin panel
- **Real-Time Character Counter** — Encoding detection (GSM 7-bit vs Unicode) and SMS part counter in all editors
- **API Health Badge** — Green/red connection status visible on every admin page
- **Retry Logic** — Configurable retry on API failures with timeout protection
- **WHMCS Activity Log** — Every significant action is recorded in the WHMCS activity log
- **No Composer Required** — Works on any shared hosting without Composer or PSR-4

---

## Supported WHMCS Hooks

| Category | Hook | Recipient |
|---|---|---|
| **Invoice** | InvoiceCreated | Client |
| | InvoicePaid | Client |
| | InvoicePaymentReminder (1st) | Client |
| | InvoicePaymentReminder (2nd) | Client |
| | InvoicePaymentReminder (3rd) | Client |
| | InvoicePaymentReminder (Overdue) | Client |
| **Ticket** | TicketAdminReply | Client |
| | TicketClose | Client |
| | TicketOpen | Admin |
| | TicketUserReply | Admin |
| **Order** | AcceptOrder | Client |
| | AfterModuleCreate (Service Provisioned) | Client |
| | AfterModuleSuspend | Client |
| | AfterModuleUnsuspend | Client |
| **Domain** | AfterRegistrarRegistration | Client |
| | AfterRegistrarRenewal | Client |
| **Client** | ClientAdd (Welcome) | Client |
| | ClientAdd (New Client Alert) | Admin |

---

## Merge Fields

Use these placeholders inside any hook template:

| Field | Description |
|---|---|
| `{firstname}` | Client first name |
| `{lastname}` | Client last name |
| `{fullname}` | Client full name |
| `{email}` | Client email address |
| `{phonenumber}` | Client phone number |
| `{companyname}` | Client company name |
| `{invoice_id}` | Invoice ID |
| `{invoice_total}` | Invoice total amount |
| `{due_date}` | Invoice due date |
| `{ticket_id}` | Support ticket ID |
| `{ticket_subject}` | Support ticket subject |
| `{domain}` | Domain name |
| `{product_name}` | Product or service name |

---

## Requirements

- WHMCS **7.x or 8.x**
- PHP **7.4** or higher
- A valid [GioSMS](https://giosms.com) account with API access
- MySQL / MariaDB
- cURL enabled on your server

---

## Installation

### 1. Upload the module

Copy the `giosms` folder to your WHMCS modules directory:

```
/your-whmcs/modules/addons/giosms/
```

Your final structure should look like:

```
modules/
└── addons/
    └── giosms/
        ├── giosms.php
        ├── hooks.php
        ├── lang/
        ├── lib/
        ├── assets/
        └── templates/
```

### 2. Activate the module

1. Log in to WHMCS Admin
2. Go to **Setup → Addon Modules**
3. Find **GioSMS** and click **Activate**
4. Click **Configure** and set the access control roles

### 3. Configure the API

1. Go to **Addons → GioSMS → Settings**
2. Enter your **GioSMS API Token** (Bearer token from your GioSMS account)
3. Enter your **Default Sender ID**
4. Optionally set your **Admin Notification Phone Numbers** (comma-separated) for admin-targeted hooks
5. Click **Save Settings**
6. Click **Test Connection** to verify the API is reachable

### 4. Configure hooks and templates

1. Go to **Addons → GioSMS → Hook Settings**
2. Toggle the hooks you want to enable
3. Customize the SMS template for each hook using the available merge fields
4. Save — hooks go live immediately

---

## Module File Structure

```
modules/addons/giosms/
├── giosms.php              # Module entry point: config, activate, deactivate, output
├── hooks.php               # WHMCS hook registrations (auto-loaded)
├── lang/
│   └── english.php         # Language strings
├── lib/
│   ├── ApiClient.php       # GioSMS API client (single class for all API calls)
│   ├── AdminController.php # Admin panel routing and tab rendering
│   ├── HookHandler.php     # Event dispatching: resolves client/invoice/ticket data
│   ├── TemplateEngine.php  # Template rendering with merge field substitution
│   ├── SmsLogger.php       # SMS log CRUD and statistics
│   ├── CampaignManager.php # Bulk/campaign SMS sending
│   └── Helpers.php         # Phone formatting, encoding detection, settings
├── assets/
│   └── giosms.js           # Character counter, AJAX balance/health, status polling
└── templates/
    ├── dashboard.tpl        # Admin dashboard
    ├── hooks.tpl            # Hook settings editor
    ├── smslog.tpl           # SMS delivery log viewer
    ├── campaign.tpl         # Bulk SMS panel
    ├── settings.tpl         # API and module settings
    └── partials/
        ├── header.tpl       # Shared admin header with API health badge
        └── footer.tpl       # Shared admin footer
```

---

## Database Tables

The module creates three tables on activation:

| Table | Purpose |
|---|---|
| `mod_giosms_settings` | Key-value configuration store |
| `mod_giosms_hooks` | Per-hook configuration, templates, and active state |
| `mod_giosms_log` | Full SMS delivery log with status tracking |

All tables are removed cleanly when the module is deactivated.

---

## Admin Panel

### Dashboard
- SMS statistics: today, this month, all-time
- Delivery breakdown: delivered, queued, failed
- Cost summary
- Live API health badge and balance widget

### Hook Settings
- Grouped by category (Invoice, Ticket, Order, Domain, Client)
- Toggle switches for enable/disable per hook
- Individual template editors with live character and SMS part counter
- Merge field reference panel per template

### SMS Log
- Full delivery log filterable by status, hook, date range, client, and recipient
- Paginated table view with status color badges

### Campaign
- Multi-recipient SMS sender (comma or newline separated phone numbers)
- Message editor with character counter and encoding indicator
- Sender ID and SMS type override per campaign

### Settings
- API Token, Base URL, Sender ID, SMS Type, Admin Phones
- Live connection test with response detail

---

## Security

- Every PHP file checks for the WHMCS constant: `if (!defined('WHMCS')) die('Access denied');`
- All user inputs are sanitized before processing or database storage
- API tokens are never exposed in logs or template output
- Database queries use Capsule (Eloquent) prepared statements throughout
- SSL verification is enabled on all API requests

---

## Changelog

### v1.0.0
- Initial release
- 19 WHMCS hooks supported
- Per-hook template and enable/disable system
- Full SMS delivery log with filters and pagination
- Bulk/campaign SMS panel
- Real-time API health and balance widgets
- WHMCS activity log integration
- Character counter with GSM 7-bit and Unicode encoding detection

---

## Support

For support, feature requests, or bug reports:

- **GitHub Issues:** [github.com/riyadMunauwar/giosms-whmcs/issues](https://github.com/riyadMunauwar/giosms-whmcs/issues)
- **GioSMS Support:** [giosms.com](https://giosms.com)

---

## Author

**Riyad Munauwar**
[GioSMS](https://giosms.com)

---

## License

This module is proprietary software developed and maintained by **GioSMS**.
Unauthorized copying, distribution, or modification is prohibited.

&copy; 2024–2026 GioSMS. All rights reserved.
