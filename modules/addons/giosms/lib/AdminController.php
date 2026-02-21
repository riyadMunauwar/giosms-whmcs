<?php
if (!defined('WHMCS')) die('Access denied');

class GioSmsAdminController
{
    private $moduleLink;
    private $smarty;

    public function __construct($vars)
    {
        $this->moduleLink = $vars['modulelink'];
        $this->smarty = $this->initSmarty();
    }

    /**
     * Route to the correct tab handler.
     */
    public function route()
    {
        // Handle AJAX requests
        if (isset($_GET['ajax'])) {
            return $this->handleAjax($_GET['ajax']);
        }

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

        switch ($tab) {
            case 'hooks':
                return $this->hooks();
            case 'smslog':
                return $this->smslog();
            case 'campaign':
                return $this->campaign();
            case 'settings':
                return $this->settings();
            default:
                return $this->dashboard();
        }
    }

    /**
     * Dashboard tab — stats, balance, API health.
     */
    private function dashboard()
    {
        $stats = GioSmsLogger::getStats();

        $this->smarty->assign('stats', $stats);
        $this->smarty->assign('modulelink', $this->moduleLink);
        $this->smarty->assign('tab', 'dashboard');
        $this->renderPage('dashboard');
    }

    /**
     * Hook settings tab — per-hook toggles and template editors.
     */
    private function hooks()
    {
        // Handle form save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hooks'])) {
            $this->saveHooks();
        }

        $grouped = GioSmsTemplateEngine::getAllHooksGrouped();

        $this->smarty->assign('hookGroups', $grouped);
        $this->smarty->assign('modulelink', $this->moduleLink);
        $this->smarty->assign('tab', 'hooks');
        $this->renderPage('hooks');
    }

    /**
     * SMS log tab — filterable log viewer.
     */
    private function smslog()
    {
        $filters = [
            'status' => isset($_GET['status']) ? $_GET['status'] : '',
            'hook_name' => isset($_GET['hook_name']) ? $_GET['hook_name'] : '',
            'client_id' => isset($_GET['client_id']) ? $_GET['client_id'] : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
            'search' => isset($_GET['search']) ? $_GET['search'] : '',
        ];
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $result = GioSmsLogger::getFiltered($filters, $page);
        $hookNames = GioSmsLogger::getDistinctHooks();

        $this->smarty->assign('logs', $result);
        $this->smarty->assign('filters', $filters);
        $this->smarty->assign('hookNames', $hookNames);
        $this->smarty->assign('modulelink', $this->moduleLink);
        $this->smarty->assign('tab', 'smslog');
        $this->renderPage('smslog');
    }

    /**
     * Campaign tab — bulk SMS panel.
     */
    private function campaign()
    {
        $result = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_campaign'])) {
            try {
                $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : '';
                $message = isset($_POST['message']) ? $_POST['message'] : '';
                $senderId = isset($_POST['sender_id']) ? $_POST['sender_id'] : null;
                $smsType = isset($_POST['sms_type']) ? $_POST['sms_type'] : null;

                $result = GioSmsCampaignManager::send($recipients, $message, $senderId ?: null, $smsType ?: null);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $this->smarty->assign('result', $result);
        $this->smarty->assign('error', $error);
        $this->smarty->assign('sender_id', GioSmsHelpers::getSetting('sender_id', ''));
        $this->smarty->assign('modulelink', $this->moduleLink);
        $this->smarty->assign('tab', 'campaign');
        $this->renderPage('campaign');
    }

    /**
     * Settings tab — API config, connection test.
     */
    private function settings()
    {
        $saved = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
            $keys = ['api_token', 'api_url', 'sender_id', 'sms_type', 'admin_phones'];
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    GioSmsHelpers::setSetting($key, trim($_POST[$key]));
                }
            }
            $saved = true;
            GioSmsLogger::activity('Module settings updated by admin');
        }

        $settings = GioSmsHelpers::getAllSettings();

        $this->smarty->assign('settings', $settings);
        $this->smarty->assign('saved', $saved);
        $this->smarty->assign('modulelink', $this->moduleLink);
        $this->smarty->assign('tab', 'settings');
        $this->renderPage('settings');
    }

    /**
     * Handle AJAX requests.
     */
    private function handleAjax($action)
    {
        header('Content-Type: application/json');

        switch ($action) {
            case 'balance':
                try {
                    $api = new GioSmsApiClient();
                    $result = $api->getBalance();
                    echo json_encode(['success' => true, 'data' => $result['data']]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'health':
                $api = new GioSmsApiClient();
                $result = $api->healthCheck();
                echo json_encode($result);
                break;

            case 'test_connection':
                $api = new GioSmsApiClient();
                $result = $api->testConnection();
                echo json_encode($result);
                break;

            case 'sms_status':
                try {
                    $messageId = isset($_GET['message_id']) ? $_GET['message_id'] : '';
                    if (empty($messageId)) {
                        echo json_encode(['success' => false, 'message' => 'No message ID']);
                        break;
                    }
                    $api = new GioSmsApiClient();
                    $result = $api->getStatus($messageId);
                    if (isset($result['data']['status'])) {
                        GioSmsLogger::updateStatus($messageId, $result['data']['status']);
                    }
                    echo json_encode(['success' => true, 'data' => $result['data']]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            case 'batch_status':
                try {
                    $batchId = isset($_GET['batch_id']) ? $_GET['batch_id'] : '';
                    if (empty($batchId)) {
                        echo json_encode(['success' => false, 'message' => 'No batch ID']);
                        break;
                    }
                    $result = GioSmsCampaignManager::getBatchStatus($batchId);
                    echo json_encode(['success' => true, 'data' => $result['data']]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }

        exit;
    }

    /**
     * Save hook templates and active states from POST.
     */
    private function saveHooks()
    {
        $hookIds = isset($_POST['hook_ids']) ? $_POST['hook_ids'] : [];

        foreach ($hookIds as $id) {
            $id = (int) $id;
            $template = isset($_POST['template_' . $id]) ? $_POST['template_' . $id] : '';
            $isActive = isset($_POST['active_' . $id]) ? 1 : 0;

            GioSmsTemplateEngine::updateHook($id, $template, $isActive);
        }

        GioSmsLogger::activity('Hook templates and settings updated by admin');
    }

    /**
     * Initialize Smarty with the module template directory.
     */
    private function initSmarty()
    {
        global $templates_compiledir;

        $smarty = new Smarty();
        $smarty->setTemplateDir(__DIR__ . '/../templates');
        $smarty->setCompileDir($templates_compiledir);
        return $smarty;
    }

    /**
     * Render a template page with the common header partial.
     */
    private function renderPage($template)
    {
        $this->smarty->display($template . '.tpl');
    }
}
