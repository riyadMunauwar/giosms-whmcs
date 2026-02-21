<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsTemplateEngine
{
    /**
     * Render a template by replacing merge fields with values.
     *
     * @param string $template Template text with {placeholder} tokens
     * @param array $mergeFields Associative array ['{field}' => 'value', ...]
     * @return string Rendered message
     */
    public static function render($template, array $mergeFields)
    {
        if (empty($template)) return '';

        return str_replace(
            array_keys($mergeFields),
            array_values($mergeFields),
            $template
        );
    }

    /**
     * Get hook configuration from database.
     *
     * @param string $hookName
     * @param string $eventType 'client' or 'admin'
     * @return object|null
     */
    public static function getHookConfig($hookName, $eventType = 'client')
    {
        return \Illuminate\Database\Capsule\Manager::table('mod_giosms_hooks')
            ->where('hook_name', $hookName)
            ->where('event_type', $eventType)
            ->first();
    }

    /**
     * Check if a hook is active.
     *
     * @param string $hookName
     * @param string $eventType
     * @return bool
     */
    public static function isHookActive($hookName, $eventType = 'client')
    {
        $hook = self::getHookConfig($hookName, $eventType);
        return $hook && (int) $hook->is_active === 1;
    }

    /**
     * Get the template text for a hook.
     *
     * @param string $hookName
     * @param string $eventType
     * @return string
     */
    public static function getTemplate($hookName, $eventType = 'client')
    {
        $hook = self::getHookConfig($hookName, $eventType);
        return $hook ? $hook->template : '';
    }

    /**
     * Get available merge fields for a hook.
     *
     * @param string $hookName
     * @param string $eventType
     * @return array
     */
    public static function getMergeFields($hookName, $eventType = 'client')
    {
        $hook = self::getHookConfig($hookName, $eventType);
        if (!$hook || empty($hook->merge_fields)) return [];

        $fields = json_decode($hook->merge_fields, true);
        return is_array($fields) ? $fields : [];
    }

    /**
     * Get all hooks grouped by category.
     *
     * @return array
     */
    public static function getAllHooksGrouped()
    {
        $hooks = \Illuminate\Database\Capsule\Manager::table('mod_giosms_hooks')
            ->orderBy('id')
            ->get();

        $grouped = [
            'invoice' => [],
            'ticket' => [],
            'order' => [],
            'domain' => [],
            'client' => [],
        ];

        foreach ($hooks as $hook) {
            $name = strtolower($hook->hook_name);
            if (strpos($name, 'invoice') !== false) {
                $grouped['invoice'][] = $hook;
            } elseif (strpos($name, 'ticket') !== false) {
                $grouped['ticket'][] = $hook;
            } elseif (strpos($name, 'order') !== false || strpos($name, 'module') !== false) {
                $grouped['order'][] = $hook;
            } elseif (strpos($name, 'registrar') !== false || strpos($name, 'domain') !== false) {
                $grouped['domain'][] = $hook;
            } else {
                $grouped['client'][] = $hook;
            }
        }

        return $grouped;
    }

    /**
     * Update a hook's template and active state.
     *
     * @param int $hookId
     * @param string $template
     * @param bool $isActive
     */
    public static function updateHook($hookId, $template, $isActive)
    {
        \Illuminate\Database\Capsule\Manager::table('mod_giosms_hooks')
            ->where('id', (int) $hookId)
            ->update([
                'template' => $template,
                'is_active' => $isActive ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
