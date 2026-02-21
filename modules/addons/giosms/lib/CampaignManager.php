<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsCampaignManager
{
    /**
     * Send a campaign/bulk SMS.
     *
     * @param string $recipients Comma-separated phone numbers or newline-separated
     * @param string $message Message text
     * @param string|null $senderId Override sender ID
     * @param string|null $type SMS type
     * @return array Result with batch_id or message_id
     * @throws Exception on failure
     */
    public static function send($recipients, $message, $senderId = null, $type = null)
    {
        // Normalize: replace newlines with commas, clean up
        $recipients = str_replace(["\r\n", "\r", "\n"], ',', $recipients);
        $numbers = array_filter(array_map(function ($n) {
            return GioSmsHelpers::formatPhone(trim($n));
        }, explode(',', $recipients)));

        if (empty($numbers)) {
            throw new Exception('No valid phone numbers provided');
        }

        if (empty(trim($message))) {
            throw new Exception('Message cannot be empty');
        }

        $api = new GioSmsApiClient();
        if (!$api->isConfigured()) {
            throw new Exception('GioSMS API is not configured');
        }

        $to = implode(',', $numbers);
        $isBulk = count($numbers) > 1;

        GioSmsLogger::activity(
            'Campaign SMS initiated to ' . count($numbers) . ' recipient(s)'
        );

        if ($isBulk) {
            $response = $api->sendBulk($to, $message, $senderId, $type);
        } else {
            $response = $api->sendSms($to, $message, $senderId, $type);
        }

        $data = isset($response['data']) ? $response['data'] : [];

        // Log each number for single sends, or log as batch for bulk
        if ($isBulk) {
            $batchId = isset($data['batch_id']) ? $data['batch_id'] : null;
            $costReserved = isset($data['total_cost_reserved']) ? $data['total_cost_reserved'] : null;
            $smsCountPer = isset($data['sms_count_per_message']) ? $data['sms_count_per_message'] : 1;

            GioSmsLogger::log([
                'batch_id' => $batchId,
                'client_id' => 0,
                'hook_name' => null,
                'recipient' => count($numbers) . ' recipients',
                'message' => $message,
                'sms_count' => $smsCountPer * count($numbers),
                'cost' => $costReserved,
                'status' => isset($data['status']) ? $data['status'] : 'queued',
                'api_response' => json_encode($response),
            ]);

            GioSmsLogger::activity(
                'Campaign SMS sent | Batch: ' . ($batchId ?: 'N/A')
                . ' | Recipients: ' . count($numbers)
                . ' | Cost reserved: ' . ($costReserved ?: 'N/A')
            );
        } else {
            $messageId = isset($data['message_id']) ? $data['message_id'] : null;

            GioSmsLogger::log([
                'message_id' => $messageId,
                'client_id' => 0,
                'hook_name' => null,
                'recipient' => $numbers[0],
                'message' => $message,
                'sms_count' => isset($data['sms_count']) ? $data['sms_count'] : 1,
                'cost' => isset($data['cost']) ? $data['cost'] : null,
                'status' => isset($data['status']) ? $data['status'] : 'queued',
                'api_response' => json_encode($response),
            ]);

            GioSmsLogger::activity(
                'Campaign SMS sent to ' . $numbers[0]
                . ' | MsgID: ' . ($messageId ?: 'N/A')
            );
        }

        return [
            'success' => true,
            'is_bulk' => $isBulk,
            'recipient_count' => count($numbers),
            'data' => $data,
        ];
    }

    /**
     * Get batch status for a campaign.
     *
     * @param string $batchId
     * @return array
     * @throws Exception
     */
    public static function getBatchStatus($batchId)
    {
        $api = new GioSmsApiClient();
        return $api->getBatch($batchId);
    }
}
