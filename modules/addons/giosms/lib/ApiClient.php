<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsApiClient
{
    private $apiToken;
    private $apiUrl;
    private $senderId;
    private $smsType;
    private $connectTimeout = 15;
    private $timeout = 30;
    private $maxRetries = 1;
    private $retryDelay = 2;

    public function __construct()
    {
        $this->apiToken = GioSmsHelpers::getSetting('api_token', '');
        $this->apiUrl = rtrim(GioSmsHelpers::getSetting('api_url', 'https://api.giosms.com/api/v1'), '/');
        $this->senderId = GioSmsHelpers::getSetting('sender_id', '');
        $this->smsType = GioSmsHelpers::getSetting('sms_type', 'transactional');
    }

    /**
     * Send a single SMS.
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param string|null $senderId Override sender ID
     * @param string|null $type Override SMS type
     * @return array API response data
     * @throws Exception on failure
     */
    public function sendSms($to, $message, $senderId = null, $type = null)
    {
        $payload = [
            'to' => $to,
            'message' => $message,
            'sender_id' => $senderId ?: $this->senderId,
            'type' => $type ?: $this->smsType,
        ];

        return $this->request('POST', '/send', $payload);
    }

    /**
     * Send bulk SMS to multiple numbers.
     *
     * @param string $to Comma-separated phone numbers
     * @param string $message Message text
     * @param string|null $senderId Override sender ID
     * @param string|null $type Override SMS type
     * @return array API response data
     * @throws Exception on failure
     */
    public function sendBulk($to, $message, $senderId = null, $type = null)
    {
        $payload = [
            'to' => $to,
            'message' => $message,
            'sender_id' => $senderId ?: $this->senderId,
            'type' => $type ?: $this->smsType,
        ];

        return $this->request('POST', '/send', $payload);
    }

    /**
     * Get SMS delivery status.
     *
     * @param string $messageId
     * @return array API response data
     * @throws Exception on failure
     */
    public function getStatus($messageId)
    {
        return $this->request('GET', '/status/' . urlencode($messageId));
    }

    /**
     * Get account balance.
     *
     * @param string|null $message Optional message for cost estimate
     * @return array API response data
     * @throws Exception on failure
     */
    public function getBalance($message = null)
    {
        $query = $message ? '?message=' . urlencode($message) : '';
        return $this->request('GET', '/balance' . $query);
    }

    /**
     * Get batch report.
     *
     * @param string $batchId
     * @return array API response data
     * @throws Exception on failure
     */
    public function getBatch($batchId)
    {
        return $this->request('GET', '/batch/' . urlencode($batchId));
    }

    /**
     * Get active batches.
     *
     * @return array API response data
     * @throws Exception on failure
     */
    public function getActiveBatches()
    {
        return $this->request('GET', '/batch/active');
    }

    /**
     * Health check — does NOT require auth.
     * Uses a different base URL (no /api/v1 prefix).
     *
     * @return array ['status' => 'ok'|'failed', 'message' => string]
     */
    public function healthCheck()
    {
        try {
            // Health endpoint is at /api/health, not under /api/v1
            $baseUrl = preg_replace('#/api/v1$#', '', $this->apiUrl);
            $url = $baseUrl . '/api/health';

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['status' => 'failed', 'message' => 'Connection error: ' . $error];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['status']) && $data['status'] === 'ok') {
                    return ['status' => 'ok', 'message' => 'Connected'];
                }
            }

            return ['status' => 'failed', 'message' => 'HTTP ' . $httpCode];
        } catch (Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Test API connection by checking balance.
     *
     * @return array ['status' => 'ok'|'failed', 'message' => string, 'balance' => float|null]
     */
    public function testConnection()
    {
        try {
            $result = $this->getBalance();
            if (isset($result['data']['balance'])) {
                return [
                    'status' => 'ok',
                    'message' => 'Connected successfully',
                    'balance' => $result['data']['balance'],
                    'currency' => isset($result['data']['currency']) ? $result['data']['currency'] : '',
                ];
            }
            return ['status' => 'failed', 'message' => 'Unexpected response format', 'balance' => null];
        } catch (Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage(), 'balance' => null];
        }
    }

    /**
     * Make an HTTP request to the GioSMS API.
     *
     * @param string $method GET or POST
     * @param string $endpoint API endpoint path
     * @param array|null $data POST body data
     * @return array Parsed JSON response
     * @throws Exception on failure
     */
    private function request($method, $endpoint, $data = null)
    {
        if (empty($this->apiToken)) {
            throw new Exception('GioSMS API token is not configured');
        }

        $url = $this->apiUrl . $endpoint;
        $attempt = 0;
        $lastError = '';

        while ($attempt <= $this->maxRetries) {
            $attempt++;

            $ch = curl_init();
            $headers = [
                'Authorization: Bearer ' . $this->apiToken,
                'Accept: application/json',
            ];

            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            if ($method === 'POST') {
                $opts[CURLOPT_POST] = true;
                $opts[CURLOPT_POSTFIELDS] = json_encode($data);
                $headers[] = 'Content-Type: application/json';
                $opts[CURLOPT_HTTPHEADER] = $headers;
            }

            curl_setopt_array($ch, $opts);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // Retry on timeout or 5xx errors
            if ($curlErrno === CURLE_OPERATION_TIMEDOUT || $curlErrno === CURLE_COULDNT_CONNECT || ($httpCode >= 500 && $httpCode < 600)) {
                $lastError = $curlError ?: ('HTTP ' . $httpCode);
                if ($attempt <= $this->maxRetries) {
                    sleep($this->retryDelay);
                    continue;
                }
            }

            if ($curlError) {
                throw new Exception('GioSMS API connection error: ' . $curlError);
            }

            $parsed = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('GioSMS API returned invalid JSON (HTTP ' . $httpCode . ')');
            }

            // Handle error responses
            if ($httpCode >= 400) {
                $errorMsg = isset($parsed['message']) ? $parsed['message'] : ('HTTP error ' . $httpCode);
                throw new Exception('GioSMS API error: ' . $errorMsg . ' (HTTP ' . $httpCode . ')');
            }

            return $parsed;
        }

        throw new Exception('GioSMS API request failed after ' . $attempt . ' attempts: ' . $lastError);
    }

    // Getters for configuration
    public function getSenderId()
    {
        return $this->senderId;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function isConfigured()
    {
        return !empty($this->apiToken) && !empty($this->senderId);
    }
}
