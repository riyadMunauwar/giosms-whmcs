<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsHookHandler
{
    /**
     * Dispatch an SMS for a hook event.
     *
     * @param string $hookName Internal hook name (e.g. 'InvoicePaid')
     * @param string $eventType 'client' or 'admin'
     * @param array $mergeFields ['{field}' => 'value', ...]
     * @param string $recipient Phone number to send to
     * @param int $clientId WHMCS client ID (0 for admin events)
     */
    public static function dispatch($hookName, $eventType, array $mergeFields, $recipient, $clientId = 0)
    {
        try {
            // Check if hook is active
            if (!GioSmsTemplateEngine::isHookActive($hookName, $eventType)) {
                return;
            }

            // Get template
            $template = GioSmsTemplateEngine::getTemplate($hookName, $eventType);
            if (empty($template)) {
                return;
            }

            // Render message
            $message = GioSmsTemplateEngine::render($template, $mergeFields);
            if (empty(trim($message))) {
                return;
            }

            // Format phone number
            $recipient = GioSmsHelpers::formatPhone($recipient);
            if (empty($recipient)) {
                GioSmsLogger::activity('SMS skipped for hook ' . $hookName . ' (' . $eventType . '): empty phone number');
                return;
            }

            // Send via API
            $api = new GioSmsApiClient();
            if (!$api->isConfigured()) {
                GioSmsLogger::activity('SMS skipped for hook ' . $hookName . ': API not configured');
                return;
            }

            $response = $api->sendSms($recipient, $message);

            // Parse response
            $data = isset($response['data']) ? $response['data'] : [];
            $messageId = isset($data['message_id']) ? $data['message_id'] : null;
            $smsCount = isset($data['sms_count']) ? (int) $data['sms_count'] : GioSmsHelpers::countSmsParts($message);
            $cost = isset($data['cost']) ? $data['cost'] : null;
            $status = isset($data['status']) ? $data['status'] : 'queued';

            // Log to internal table
            GioSmsLogger::log([
                'message_id' => $messageId,
                'client_id' => $clientId,
                'hook_name' => $hookName,
                'recipient' => $recipient,
                'message' => $message,
                'sms_count' => $smsCount,
                'cost' => $cost,
                'status' => $status,
                'api_response' => json_encode($response),
            ]);

            // Log to WHMCS activity log
            GioSmsLogger::activity(
                'SMS sent to ' . $recipient . ' via hook ' . $hookName . ' (' . $eventType . ')'
                . ' | Status: ' . $status
                . ($messageId ? ' | MsgID: ' . $messageId : '')
            );

        } catch (Exception $e) {
            // Log failure
            GioSmsLogger::log([
                'client_id' => $clientId,
                'hook_name' => $hookName,
                'recipient' => $recipient,
                'message' => isset($message) ? $message : '',
                'status' => 'error',
                'api_response' => json_encode(['error' => $e->getMessage()]),
            ]);

            GioSmsLogger::activity(
                'SMS FAILED for hook ' . $hookName . ' (' . $eventType . ')'
                . ' to ' . (isset($recipient) ? $recipient : 'unknown')
                . ' | Error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Dispatch SMS to admin phone numbers.
     *
     * @param string $hookName
     * @param array $mergeFields
     */
    public static function dispatchToAdmin($hookName, array $mergeFields)
    {
        $adminPhones = GioSmsHelpers::getSetting('admin_phones', '');
        if (empty($adminPhones)) return;

        $phones = array_filter(array_map('trim', explode(',', $adminPhones)));
        foreach ($phones as $phone) {
            self::dispatch($hookName, 'admin', $mergeFields, $phone, 0);
        }
    }

    /**
     * Helper: Get client merge fields and phone from a client ID.
     *
     * @param int $clientId
     * @return array ['fields' => array, 'phone' => string] or empty on failure
     */
    public static function resolveClient($clientId)
    {
        $client = GioSmsHelpers::getClientDetails($clientId);
        if (!$client) {
            return ['fields' => [], 'phone' => ''];
        }

        return [
            'fields' => GioSmsHelpers::buildClientMergeFields($client),
            'phone' => isset($client['phonenumber']) ? $client['phonenumber'] : '',
        ];
    }

    /**
     * Helper: Get invoice merge fields.
     *
     * @param int $invoiceId
     * @return array Merge fields for invoice
     */
    public static function resolveInvoice($invoiceId)
    {
        $result = localAPI('GetInvoice', ['invoiceid' => (int) $invoiceId]);
        if ($result['result'] !== 'success') return [];

        return [
            '{invoice_id}' => isset($result['invoiceid']) ? $result['invoiceid'] : $invoiceId,
            '{invoice_total}' => isset($result['total']) ? $result['total'] : '0.00',
            '{due_date}' => isset($result['duedate']) ? $result['duedate'] : '',
        ];
    }

    /**
     * Helper: Get ticket merge fields.
     *
     * @param int $ticketId
     * @return array Merge fields for ticket
     */
    public static function resolveTicket($ticketId)
    {
        $result = localAPI('GetTicket', ['ticketid' => (int) $ticketId]);
        if ($result['result'] !== 'success') return [];

        return [
            '{ticket_id}' => isset($result['tid']) ? $result['tid'] : $ticketId,
            '{ticket_subject}' => isset($result['subject']) ? $result['subject'] : '',
        ];
    }
}
