<?php
/**
 * GioSMS — WHMCS Hook Registrations
 * Auto-loaded by WHMCS from modules/addons/giosms/hooks.php
 */
if (!defined('WHMCS')) die('Access denied');

// Load library files
$giosmsLibPath = __DIR__ . '/lib/';
require_once $giosmsLibPath . 'Helpers.php';
require_once $giosmsLibPath . 'ApiClient.php';
require_once $giosmsLibPath . 'TemplateEngine.php';
require_once $giosmsLibPath . 'SmsLogger.php';
require_once $giosmsLibPath . 'HookHandler.php';

// ─────────────────────────────────────────────
// Invoice Hooks
// ─────────────────────────────────────────────

add_hook('InvoiceCreated', 1, function ($vars) {
    $invoiceId = $vars['invoiceid'];
    $invoiceFields = GioSmsHookHandler::resolveInvoice($invoiceId);

    // Get client ID from invoice
    $invoice = localAPI('GetInvoice', ['invoiceid' => (int) $invoiceId]);
    if ($invoice['result'] !== 'success') return;
    $clientId = $invoice['userid'];

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $mergeFields = array_merge($client['fields'], $invoiceFields);
    GioSmsHookHandler::dispatch('InvoiceCreated', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('InvoicePaid', 1, function ($vars) {
    $invoiceId = $vars['invoiceid'];
    $invoiceFields = GioSmsHookHandler::resolveInvoice($invoiceId);

    $invoice = localAPI('GetInvoice', ['invoiceid' => (int) $invoiceId]);
    if ($invoice['result'] !== 'success') return;
    $clientId = $invoice['userid'];

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $mergeFields = array_merge($client['fields'], $invoiceFields);
    GioSmsHookHandler::dispatch('InvoicePaid', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('InvoicePaymentReminder', 1, function ($vars) {
    $invoiceId = $vars['invoiceid'];
    $type = isset($vars['type']) ? $vars['type'] : 'Overdue';

    // Map WHMCS reminder types to our hook names
    $typeMap = [
        'First Overdue' => 'InvoicePaymentReminder_first',
        'Second Overdue' => 'InvoicePaymentReminder_second',
        'Third Overdue' => 'InvoicePaymentReminder_third',
        'Overdue' => 'InvoicePaymentReminder_overdue',
    ];
    $hookName = isset($typeMap[$type]) ? $typeMap[$type] : 'InvoicePaymentReminder_overdue';

    $invoiceFields = GioSmsHookHandler::resolveInvoice($invoiceId);

    $invoice = localAPI('GetInvoice', ['invoiceid' => (int) $invoiceId]);
    if ($invoice['result'] !== 'success') return;
    $clientId = $invoice['userid'];

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $mergeFields = array_merge($client['fields'], $invoiceFields);
    GioSmsHookHandler::dispatch($hookName, 'client', $mergeFields, $client['phone'], $clientId);
});

// ─────────────────────────────────────────────
// Ticket Hooks
// ─────────────────────────────────────────────

add_hook('TicketAdminReply', 1, function ($vars) {
    $ticketId = $vars['ticketid'];
    $ticketFields = GioSmsHookHandler::resolveTicket($ticketId);

    // Get client ID from ticket
    $ticket = localAPI('GetTicket', ['ticketid' => (int) $ticketId]);
    if ($ticket['result'] !== 'success') return;
    $clientId = isset($ticket['userid']) ? $ticket['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $mergeFields = array_merge($client['fields'], $ticketFields);
    GioSmsHookHandler::dispatch('TicketAdminReply', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('TicketClose', 1, function ($vars) {
    $ticketId = $vars['ticketid'];
    $ticketFields = GioSmsHookHandler::resolveTicket($ticketId);

    $ticket = localAPI('GetTicket', ['ticketid' => (int) $ticketId]);
    if ($ticket['result'] !== 'success') return;
    $clientId = isset($ticket['userid']) ? $ticket['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $mergeFields = array_merge($client['fields'], $ticketFields);
    GioSmsHookHandler::dispatch('TicketClose', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('TicketOpen', 1, function ($vars) {
    $ticketId = $vars['ticketid'];
    $ticketFields = GioSmsHookHandler::resolveTicket($ticketId);

    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    $client = GioSmsHookHandler::resolveClient($clientId);

    $mergeFields = array_merge($client['fields'], $ticketFields);
    GioSmsHookHandler::dispatchToAdmin('TicketOpen', $mergeFields);
});

add_hook('TicketUserReply', 1, function ($vars) {
    $ticketId = $vars['ticketid'];
    $ticketFields = GioSmsHookHandler::resolveTicket($ticketId);

    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    $client = GioSmsHookHandler::resolveClient($clientId);

    $mergeFields = array_merge($client['fields'], $ticketFields);
    GioSmsHookHandler::dispatchToAdmin('TicketUserReply', $mergeFields);
});

// ─────────────────────────────────────────────
// Order & Service Hooks
// ─────────────────────────────────────────────

add_hook('AcceptOrder', 1, function ($vars) {
    $orderId = $vars['orderid'];

    $order = localAPI('GetOrders', ['id' => (int) $orderId]);
    if ($order['result'] !== 'success') return;
    if (empty($order['orders']['order'])) return;

    $orderData = $order['orders']['order'][0];
    $clientId = $orderData['userid'];

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    GioSmsHookHandler::dispatch('AcceptOrder', 'client', $client['fields'], $client['phone'], $clientId);
});

add_hook('AfterModuleCreate', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $serviceFields = [
        '{product_name}' => isset($vars['params']['configoption1']) ? $vars['params']['configoption1'] : (isset($vars['params']['productname']) ? $vars['params']['productname'] : ''),
        '{domain}' => isset($vars['params']['domain']) ? $vars['params']['domain'] : '',
    ];

    $mergeFields = array_merge($client['fields'], $serviceFields);
    GioSmsHookHandler::dispatch('AfterModuleCreate', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('AfterModuleSuspend', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $serviceFields = [
        '{product_name}' => isset($vars['params']['productname']) ? $vars['params']['productname'] : '',
        '{domain}' => isset($vars['params']['domain']) ? $vars['params']['domain'] : '',
    ];

    $mergeFields = array_merge($client['fields'], $serviceFields);
    GioSmsHookHandler::dispatch('AfterModuleSuspend', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('AfterModuleUnsuspend', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $serviceFields = [
        '{product_name}' => isset($vars['params']['productname']) ? $vars['params']['productname'] : '',
        '{domain}' => isset($vars['params']['domain']) ? $vars['params']['domain'] : '',
    ];

    $mergeFields = array_merge($client['fields'], $serviceFields);
    GioSmsHookHandler::dispatch('AfterModuleUnsuspend', 'client', $mergeFields, $client['phone'], $clientId);
});

// ─────────────────────────────────────────────
// Domain Hooks
// ─────────────────────────────────────────────

add_hook('AfterRegistrarRegistration', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $domainFields = [
        '{domain}' => isset($vars['domain']) ? $vars['domain'] : '',
    ];

    $mergeFields = array_merge($client['fields'], $domainFields);
    GioSmsHookHandler::dispatch('AfterRegistrarRegistration', 'client', $mergeFields, $client['phone'], $clientId);
});

add_hook('AfterRegistrarRenewal', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    $domainFields = [
        '{domain}' => isset($vars['domain']) ? $vars['domain'] : '',
    ];

    $mergeFields = array_merge($client['fields'], $domainFields);
    GioSmsHookHandler::dispatch('AfterRegistrarRenewal', 'client', $mergeFields, $client['phone'], $clientId);
});

// ─────────────────────────────────────────────
// Client Hooks
// ─────────────────────────────────────────────

add_hook('ClientAdd', 1, function ($vars) {
    $clientId = isset($vars['userid']) ? $vars['userid'] : 0;
    if (!$clientId) return;

    $client = GioSmsHookHandler::resolveClient($clientId);
    if (empty($client['phone'])) return;

    // Send welcome SMS to client
    GioSmsHookHandler::dispatch('ClientAdd', 'client', $client['fields'], $client['phone'], $clientId);

    // Send notification to admin
    GioSmsHookHandler::dispatchToAdmin('ClientAdd', $client['fields']);
});
