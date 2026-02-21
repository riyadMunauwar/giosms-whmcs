<?php
if (!defined('WHMCS')) die('Access denied');

use Illuminate\Database\Capsule\Manager as Capsule;

class GioSmsLogger
{
    /**
     * Log an SMS send attempt.
     *
     * @param array $data [
     *   'message_id'   => string|null,
     *   'batch_id'     => string|null,
     *   'client_id'    => int,
     *   'hook_name'    => string|null,
     *   'recipient'    => string,
     *   'message'      => string,
     *   'sms_count'    => int,
     *   'cost'         => float|null,
     *   'status'       => string,
     *   'api_response' => string|null,
     * ]
     * @return int Inserted log ID
     */
    public static function log(array $data)
    {
        return Capsule::table('mod_giosms_log')->insertGetId([
            'message_id' => isset($data['message_id']) ? $data['message_id'] : null,
            'batch_id' => isset($data['batch_id']) ? $data['batch_id'] : null,
            'client_id' => isset($data['client_id']) ? (int) $data['client_id'] : 0,
            'hook_name' => isset($data['hook_name']) ? $data['hook_name'] : null,
            'recipient' => isset($data['recipient']) ? $data['recipient'] : '',
            'message' => isset($data['message']) ? $data['message'] : '',
            'sms_count' => isset($data['sms_count']) ? (int) $data['sms_count'] : 1,
            'cost' => isset($data['cost']) ? $data['cost'] : null,
            'status' => isset($data['status']) ? $data['status'] : 'queued',
            'api_response' => isset($data['api_response']) ? $data['api_response'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update delivery status for a logged SMS.
     *
     * @param string $messageId GioSMS message_id
     * @param string $status New status
     * @param array $extra Extra fields to update
     */
    public static function updateStatus($messageId, $status, array $extra = [])
    {
        $update = array_merge($extra, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Capsule::table('mod_giosms_log')
            ->where('message_id', $messageId)
            ->update($update);
    }

    /**
     * Get filtered, paginated SMS logs.
     *
     * @param array $filters [
     *   'status'    => string|null,
     *   'hook_name' => string|null,
     *   'client_id' => int|null,
     *   'date_from' => string|null (Y-m-d),
     *   'date_to'   => string|null (Y-m-d),
     *   'search'    => string|null (recipient or message search),
     * ]
     * @param int $page
     * @param int $perPage
     * @return array ['data' => array, 'total' => int, 'pages' => int, 'page' => int]
     */
    public static function getFiltered(array $filters = [], $page = 1, $perPage = 25)
    {
        $query = Capsule::table('mod_giosms_log');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['hook_name'])) {
            $query->where('hook_name', $filters['hook_name']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'LIKE', '%' . $search . '%')
                  ->orWhere('message', 'LIKE', '%' . $search . '%');
            });
        }

        $total = $query->count();
        $pages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $pages ?: 1));

        $data = (clone $query)
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get aggregate stats for the dashboard.
     *
     * @return array
     */
    public static function getStats()
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $totalAll = Capsule::table('mod_giosms_log')->count();
        $totalToday = Capsule::table('mod_giosms_log')
            ->where('created_at', '>=', $today . ' 00:00:00')
            ->count();
        $totalMonth = Capsule::table('mod_giosms_log')
            ->where('created_at', '>=', $monthStart . ' 00:00:00')
            ->count();

        $delivered = Capsule::table('mod_giosms_log')->where('status', 'delivered')->count();
        $failed = Capsule::table('mod_giosms_log')
            ->whereIn('status', ['failed', 'error'])
            ->count();

        $totalCost = Capsule::table('mod_giosms_log')
            ->whereNotNull('cost')
            ->sum('cost');
        $costToday = Capsule::table('mod_giosms_log')
            ->where('created_at', '>=', $today . ' 00:00:00')
            ->whereNotNull('cost')
            ->sum('cost');

        return [
            'total_all' => $totalAll,
            'total_today' => $totalToday,
            'total_month' => $totalMonth,
            'delivered' => $delivered,
            'failed' => $failed,
            'queued' => Capsule::table('mod_giosms_log')->whereIn('status', ['queued', 'submitted'])->count(),
            'total_cost' => round((float) $totalCost, 2),
            'cost_today' => round((float) $costToday, 2),
        ];
    }

    /**
     * Get distinct hook names from the log for filter dropdowns.
     *
     * @return array
     */
    public static function getDistinctHooks()
    {
        return Capsule::table('mod_giosms_log')
            ->whereNotNull('hook_name')
            ->distinct()
            ->pluck('hook_name')
            ->toArray();
    }

    /**
     * Delete a log entry.
     *
     * @param int $id
     */
    public static function delete($id)
    {
        Capsule::table('mod_giosms_log')->where('id', (int) $id)->delete();
    }

    /**
     * Log to WHMCS activity log.
     *
     * @param string $message
     */
    public static function activity($message)
    {
        logActivity('[GioSMS] ' . $message);
    }
}
