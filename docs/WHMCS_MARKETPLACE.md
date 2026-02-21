# GioSMS — WHMCS Marketplace Listing

> This file contains the copy for the WHMCS Marketplace product listing.
> Use each section in the corresponding marketplace field.

---

## Product Name

```
GioSMS — SMS Notifications for WHMCS
```

---

## Short Description (160 characters max)

```
Send automated SMS notifications to clients and admins for invoices, tickets, orders, domains, and more — powered by GioSMS API.
```

---

## Full Description

### Automate Your Client Communication with SMS

GioSMS is a professional WHMCS addon module that connects your billing system to the GioSMS API and sends automated SMS notifications for every critical event — invoices, support tickets, orders, domain registrations, and more.

Stop relying on email alone. SMS notifications reach your clients instantly and improve payment rates, client satisfaction, and support response times.

---

### Key Features

**19 Configurable WHMCS Hooks**
Cover the full client lifecycle from welcome messages to invoice reminders, service provisioning alerts, ticket replies, domain registrations, and renewals.

**Per-Hook Templates with Merge Fields**
Every hook has its own SMS template. Personalise every message with client name, invoice ID, due date, ticket subject, domain name, and more — all with simple `{placeholder}` syntax.

**Per-Hook Enable / Disable**
Toggle any hook on or off independently. Run exactly the notifications your business needs without affecting anything else.

**Admin Dashboard with Live Balance**
A modern card-based dashboard shows your SMS statistics (today, this month, all-time), delivery breakdown, cost summary, and live API balance — all in one view.

**Full SMS Delivery Log**
Every SMS sent is recorded with recipient, message, hook name, delivery status, cost, and timestamp. Filter by status, hook type, date range, client, or free text search.

**Bulk / Campaign SMS**
Send a custom SMS to any list of recipients directly from the admin panel. Useful for promotional messages, maintenance alerts, or any broadcast communication.

**Real-Time Character Counter**
All template editors include a live character counter with automatic GSM 7-bit / Unicode encoding detection and SMS part counting — so you always know exactly how many messages each template costs.

**API Health Badge**
A green / red connection status badge is visible on every admin page so you always know whether the GioSMS API is reachable.

**WHMCS Activity Log Integration**
Every SMS sent (success or failure), every connection error, every campaign, and every settings change is recorded in the WHMCS activity log for full auditability.

**No Composer, No Dependencies**
Works out of the box on any shared hosting. No Composer, no PSR-4, no external dependencies. Just upload and activate.

---

### Supported Hooks

| Category | Event |
|---|---|
| Invoice | Created, Paid, 1st / 2nd / 3rd Reminder, Overdue |
| Ticket | Admin Reply, Close (→ Client), Open, User Reply (→ Admin) |
| Order & Service | Order Accepted, Provisioned, Suspended, Unsuspended |
| Domain | Registered, Renewed |
| Client | Welcome (→ Client), New Client Alert (→ Admin) |

---

### Merge Fields Available

`{firstname}` `{lastname}` `{fullname}` `{email}` `{phonenumber}` `{companyname}` `{invoice_id}` `{invoice_total}` `{due_date}` `{ticket_id}` `{ticket_subject}` `{domain}` `{product_name}`

---

### Example SMS Templates

**Invoice Paid:**
```
Hi {firstname}, your invoice #{invoice_id} of {invoice_total} has been received. Thank you! — YourCompany
```

**Invoice Reminder:**
```
Hi {firstname}, invoice #{invoice_id} of {invoice_total} is due on {due_date}. Please pay to avoid interruption.
```

**Service Provisioned:**
```
Hi {firstname}, your service {product_name} is now active. Welcome aboard!
```

**Ticket Reply:**
```
Hi {firstname}, we replied to your ticket #{ticket_id}: "{ticket_subject}". Check your client area.
```

---

### Requirements

- WHMCS 7.x or 8.x
- PHP 7.4 or higher
- GioSMS account with API access
- cURL enabled on server

---

### Installation

1. Upload the `giosms` folder to `/modules/addons/`
2. Activate via **Setup → Addon Modules**
3. Enter your GioSMS API Token and Sender ID in Settings
4. Enable and customise your hook templates
5. Done — SMS notifications go live immediately

---

### Support

Developed and maintained by **GioSMS**.

- Website: [giosms.com](https://giosms.com)
- GitHub: [github.com/riyadMunauwar/giosms-whmcs](https://github.com/riyadMunauwar/giosms-whmcs)

---

## Tags (WHMCS Marketplace)

```
sms, notifications, giosms, invoice, ticket, order, domain, bulk sms, campaign, client notifications, automation
```

---

## Category (WHMCS Marketplace)

```
Notifications & Communication
```

---

## Compatibility

```
WHMCS 7.x, WHMCS 8.x
PHP 7.4+
```

---

## Screenshots Needed for Listing

1. Admin Dashboard — stats cards, balance widget, API health badge
2. Hook Settings — toggle switches, template editor, character counter, merge field reference
3. SMS Log — filterable table with status badges
4. Campaign Panel — bulk SMS sender with character counter
5. Settings Page — API configuration and connection test

---

## Changelog Entry (v1.0.0)

```
v1.0.0 — Initial Release

- 19 WHMCS hooks supported across invoices, tickets, orders, domains, and clients
- Per-hook SMS templates with merge field substitution
- Per-hook enable/disable toggle
- Full SMS delivery log with filters and pagination
- Bulk/campaign SMS panel with batch tracking
- Admin dashboard with live balance widget and API health badge
- Real-time character counter with GSM 7-bit/Unicode encoding detection
- WHMCS activity log integration for full auditability
- No Composer required — works on any shared hosting
```
