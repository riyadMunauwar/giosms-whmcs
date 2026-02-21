<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsHelpers
{
    /**
     * Normalize a phone number — strip spaces, dashes, parentheses.
     * Keep leading + and digits only.
     */
    public static function formatPhone($number)
    {
        $number = trim($number);
        $number = preg_replace('/[\s\-\(\)\.]+/', '', $number);
        // Keep + at start if present, then digits only
        if (substr($number, 0, 1) === '+') {
            $number = '+' . preg_replace('/[^0-9]/', '', substr($number, 1));
        } else {
            $number = preg_replace('/[^0-9]/', '', $number);
        }
        return $number;
    }

    /**
     * Detect if text requires Unicode (UCS-2) encoding or fits in GSM 7-bit.
     */
    public static function detectEncoding($text)
    {
        // GSM 7-bit basic character set + extension table
        $gsm7 = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            . 'ÄÖÑÜabcdefghijklmnopqrstuvwxyzäöñüà^{}\\[~]|€';

        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            if (mb_strpos($gsm7, $char, 0, 'UTF-8') === false) {
                return 'unicode';
            }
        }
        return 'gsm';
    }

    /**
     * Count SMS parts based on message text.
     */
    public static function countSmsParts($text)
    {
        $encoding = self::detectEncoding($text);
        $len = mb_strlen($text, 'UTF-8');

        if ($encoding === 'gsm') {
            if ($len <= 160) return 1;
            return (int) ceil($len / 153);
        } else {
            if ($len <= 70) return 1;
            return (int) ceil($len / 67);
        }
    }

    /**
     * Get character count info for a message.
     */
    public static function getCharInfo($text)
    {
        $encoding = self::detectEncoding($text);
        $len = mb_strlen($text, 'UTF-8');
        $parts = self::countSmsParts($text);

        if ($encoding === 'gsm') {
            $singleLimit = 160;
            $multiLimit = 153;
        } else {
            $singleLimit = 70;
            $multiLimit = 67;
        }

        return [
            'length' => $len,
            'encoding' => $encoding,
            'parts' => $parts,
            'single_limit' => $singleLimit,
            'multi_limit' => $multiLimit,
        ];
    }

    /**
     * Sanitize string for safe HTML output.
     */
    public static function sanitize($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get a setting value from mod_giosms_settings.
     */
    public static function getSetting($key, $default = '')
    {
        $row = \Illuminate\Database\Capsule\Manager::table('mod_giosms_settings')
            ->where('setting_key', $key)
            ->first();

        return $row ? $row->setting_value : $default;
    }

    /**
     * Set a setting value in mod_giosms_settings.
     */
    public static function setSetting($key, $value)
    {
        \Illuminate\Database\Capsule\Manager::table('mod_giosms_settings')
            ->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => (string) $value]
            );
    }

    /**
     * Get all settings as key => value array.
     */
    public static function getAllSettings()
    {
        $rows = \Illuminate\Database\Capsule\Manager::table('mod_giosms_settings')->get();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    /**
     * Get client details via localAPI.
     */
    public static function getClientDetails($clientId)
    {
        $result = localAPI('GetClientsDetails', ['clientid' => (int) $clientId]);
        if ($result['result'] !== 'success') {
            return null;
        }
        return $result;
    }

    /**
     * Build common merge field values from client data.
     */
    public static function buildClientMergeFields($clientData)
    {
        if (!$clientData) return [];

        return [
            '{firstname}' => isset($clientData['firstname']) ? $clientData['firstname'] : '',
            '{lastname}' => isset($clientData['lastname']) ? $clientData['lastname'] : '',
            '{fullname}' => trim((isset($clientData['firstname']) ? $clientData['firstname'] : '') . ' ' . (isset($clientData['lastname']) ? $clientData['lastname'] : '')),
            '{email}' => isset($clientData['email']) ? $clientData['email'] : '',
            '{phonenumber}' => isset($clientData['phonenumber']) ? $clientData['phonenumber'] : '',
            '{companyname}' => isset($clientData['companyname']) ? $clientData['companyname'] : '',
        ];
    }
}
