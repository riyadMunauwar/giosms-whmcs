<?php
/**
 * GioSMS — WHMCS Addon Module
 * SMS notifications via GioSMS API for WHMCS events.
 *
 * @version 1.0.0
 */
if (!defined('WHMCS')) die('Access denied');

use Illuminate\Database\Capsule\Manager as Capsule;

function giosms_config()
{
    return [
        'name' => 'GioSMS',
        'description' => 'Send SMS notifications via GioSMS API for WHMCS events — invoices, tickets, orders, domains, and more.',
        'version' => '1.0.0',
        'author' => 'GioSMS',
        'language' => 'english',
        'fields' => [],
    ];
}

function giosms_activate()
{
    try {
        // Create settings table (key-value)
        if (!Capsule::schema()->hasTable('mod_giosms_settings')) {
            Capsule::schema()->create('mod_giosms_settings', function ($table) {
                $table->string('setting_key', 64)->primary();
                $table->text('setting_value')->default('');
            });
        }

        // Create hooks table
        if (!Capsule::schema()->hasTable('mod_giosms_hooks')) {
            Capsule::schema()->create('mod_giosms_hooks', function ($table) {
                $table->increments('id');
                $table->string('hook_name', 64);
                $table->string('event_type', 32)->default('client');
                $table->tinyInteger('is_active')->default(0);
                $table->text('template');
                $table->string('description', 255)->default('');
                $table->text('merge_fields');
                $table->dateTime('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
                $table->unique(['hook_name', 'event_type']);
            });
        }

        // Create SMS log table
        if (!Capsule::schema()->hasTable('mod_giosms_log')) {
            Capsule::schema()->create('mod_giosms_log', function ($table) {
                $table->increments('id');
                $table->string('message_id', 64)->nullable();
                $table->string('batch_id', 64)->nullable();
                $table->unsignedInteger('client_id')->default(0);
                $table->string('hook_name', 64)->nullable();
                $table->string('recipient', 20);
                $table->text('message');
                $table->unsignedTinyInteger('sms_count')->default(1);
                $table->decimal('cost', 10, 4)->nullable();
                $table->enum('status', ['queued', 'submitted', 'delivered', 'failed', 'error'])->default('queued');
                $table->text('api_response')->nullable();
                $table->dateTime('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
                $table->index('client_id');
                $table->index('hook_name');
                $table->index('status');
                $table->index('created_at');
                $table->index('message_id');
            });
        }

        // Seed default settings
        $defaults = [
            'api_token' => '',
            'api_url' => 'https://api.giosms.com/api/v1',
            'sender_id' => '',
            'sms_type' => 'transactional',
            'admin_phones' => '',
            'module_version' => '1.0.0',
        ];
        foreach ($defaults as $key => $value) {
            Capsule::table('mod_giosms_settings')->insertOrIgnore([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }

        // Seed hook definitions
        giosms_seedHooks();

        logActivity('[GioSMS] Module activated successfully');

        return ['status' => 'success', 'description' => 'GioSMS module activated. Go to Addons → GioSMS to configure your API settings.'];
    } catch (Exception $e) {
        logActivity('[GioSMS] Module activation failed: ' . $e->getMessage());
        return ['status' => 'error', 'description' => 'Activation failed: ' . $e->getMessage()];
    }
}

function giosms_deactivate()
{
    try {
        Capsule::schema()->dropIfExists('mod_giosms_log');
        Capsule::schema()->dropIfExists('mod_giosms_hooks');
        Capsule::schema()->dropIfExists('mod_giosms_settings');

        logActivity('[GioSMS] Module deactivated — all tables dropped');

        return ['status' => 'success', 'description' => 'GioSMS module deactivated. All data has been removed.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Deactivation failed: ' . $e->getMessage()];
    }
}

function giosms_upgrade($vars)
{
    $version = $vars['version'];

    // Future upgrades go here
    // if ($version < '1.1.0') { ... }
}

function giosms_output($vars)
{
    // Load library files
    $libPath = __DIR__ . '/lib/';
    require_once $libPath . 'Helpers.php';
    require_once $libPath . 'ApiClient.php';
    require_once $libPath . 'TemplateEngine.php';
    require_once $libPath . 'SmsLogger.php';
    require_once $libPath . 'HookHandler.php';
    require_once $libPath . 'CampaignManager.php';
    require_once $libPath . 'AdminController.php';

    $controller = new GioSmsAdminController($vars);
    $controller->route();
}

/**
 * Seed all hook definitions into mod_giosms_hooks.
 */
function giosms_seedHooks()
{
    $commonClientFields = ['{firstname}', '{lastname}', '{fullname}', '{email}', '{phonenumber}', '{companyname}'];
    $invoiceFields = array_merge($commonClientFields, ['{invoice_id}', '{invoice_total}', '{due_date}']);
    $ticketFields = array_merge($commonClientFields, ['{ticket_id}', '{ticket_subject}']);
    $serviceFields = array_merge($commonClientFields, ['{product_name}', '{domain}']);
    $domainFields = array_merge($commonClientFields, ['{domain}']);

    $hooks = [
        // Invoice hooks — client
        [
            'hook_name' => 'InvoiceCreated',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, a new invoice #{invoice_id} for {invoice_total} has been generated. Due date: {due_date}.',
            'description' => 'SMS to client when a new invoice is created',
            'merge_fields' => $invoiceFields,
        ],
        [
            'hook_name' => 'InvoicePaid',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your invoice #{invoice_id} for {invoice_total} has been paid. Thank you!',
            'description' => 'SMS to client when an invoice is marked paid',
            'merge_fields' => $invoiceFields,
        ],
        [
            'hook_name' => 'InvoicePaymentReminder_first',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your invoice #{invoice_id} for {invoice_total} is overdue. Please make payment at your earliest convenience.',
            'description' => 'SMS to client on first overdue payment reminder',
            'merge_fields' => $invoiceFields,
        ],
        [
            'hook_name' => 'InvoicePaymentReminder_second',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, this is a second reminder. Invoice #{invoice_id} for {invoice_total} remains unpaid.',
            'description' => 'SMS to client on second overdue payment reminder',
            'merge_fields' => $invoiceFields,
        ],
        [
            'hook_name' => 'InvoicePaymentReminder_third',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, final reminder. Invoice #{invoice_id} for {invoice_total} is still unpaid. Please pay immediately to avoid service interruption.',
            'description' => 'SMS to client on third overdue payment reminder',
            'merge_fields' => $invoiceFields,
        ],
        [
            'hook_name' => 'InvoicePaymentReminder_overdue',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, reminder: Invoice #{invoice_id} for {invoice_total} is overdue. Due: {due_date}.',
            'description' => 'SMS to client on standard overdue reminder',
            'merge_fields' => $invoiceFields,
        ],

        // Ticket hooks — client
        [
            'hook_name' => 'TicketAdminReply',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, we have replied to your support ticket #{ticket_id}: {ticket_subject}.',
            'description' => 'SMS to client when admin replies to their ticket',
            'merge_fields' => $ticketFields,
        ],
        [
            'hook_name' => 'TicketClose',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your support ticket #{ticket_id} ({ticket_subject}) has been closed.',
            'description' => 'SMS to client when their ticket is closed',
            'merge_fields' => $ticketFields,
        ],

        // Ticket hooks — admin
        [
            'hook_name' => 'TicketOpen',
            'event_type' => 'admin',
            'template' => 'New ticket #{ticket_id} opened by {fullname}: {ticket_subject}',
            'description' => 'SMS to admin when a new ticket is opened',
            'merge_fields' => $ticketFields,
        ],
        [
            'hook_name' => 'TicketUserReply',
            'event_type' => 'admin',
            'template' => 'Client {fullname} replied to ticket #{ticket_id}: {ticket_subject}',
            'description' => 'SMS to admin when a client replies to a ticket',
            'merge_fields' => $ticketFields,
        ],

        // Order & Service hooks — client
        [
            'hook_name' => 'AcceptOrder',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your order has been accepted and is being processed. Thank you!',
            'description' => 'SMS to client when their order is accepted',
            'merge_fields' => $commonClientFields,
        ],
        [
            'hook_name' => 'AfterModuleCreate',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your hosting account for {product_name} ({domain}) has been set up and is ready to use!',
            'description' => 'SMS to client when hosting service is provisioned',
            'merge_fields' => $serviceFields,
        ],
        [
            'hook_name' => 'AfterModuleSuspend',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your service {product_name} ({domain}) has been suspended. Please contact support.',
            'description' => 'SMS to client when their service is suspended',
            'merge_fields' => $serviceFields,
        ],
        [
            'hook_name' => 'AfterModuleUnsuspend',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your service {product_name} ({domain}) has been unsuspended and is now active.',
            'description' => 'SMS to client when their service is unsuspended',
            'merge_fields' => $serviceFields,
        ],

        // Domain hooks — client
        [
            'hook_name' => 'AfterRegistrarRegistration',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your domain {domain} has been registered successfully!',
            'description' => 'SMS to client when their domain is registered',
            'merge_fields' => $domainFields,
        ],
        [
            'hook_name' => 'AfterRegistrarRenewal',
            'event_type' => 'client',
            'template' => 'Hi {firstname}, your domain {domain} has been renewed successfully.',
            'description' => 'SMS to client when their domain is renewed',
            'merge_fields' => $domainFields,
        ],

        // Client hooks — client
        [
            'hook_name' => 'ClientAdd',
            'event_type' => 'client',
            'template' => 'Welcome {firstname}! Your account has been created. Thank you for choosing us!',
            'description' => 'Welcome SMS to new clients on registration',
            'merge_fields' => $commonClientFields,
        ],

        // Client hooks — admin
        [
            'hook_name' => 'ClientAdd',
            'event_type' => 'admin',
            'template' => 'New client registered: {fullname} ({email})',
            'description' => 'SMS to admin when a new client registers',
            'merge_fields' => $commonClientFields,
        ],
    ];

    foreach ($hooks as $hook) {
        Capsule::table('mod_giosms_hooks')->insertOrIgnore([
            'hook_name' => $hook['hook_name'],
            'event_type' => $hook['event_type'],
            'is_active' => 0,
            'template' => $hook['template'],
            'description' => $hook['description'],
            'merge_fields' => json_encode($hook['merge_fields']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
