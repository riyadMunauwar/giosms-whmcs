{include file="partials/header.tpl"}

{if $saved}
    <div class="alert alert-success">Settings saved successfully.</div>
{/if}

<form method="post" action="{$modulelink}&tab=settings">
    <input type="hidden" name="save_settings" value="1">

    <div class="row">
        <div class="col-md-8">
            <div class="giosms-card">
                <div class="card-header">API Configuration</div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>API Token</strong></label>
                        <input type="password" name="api_token" class="form-control" value="{$settings.api_token|escape:'html'}"
                            placeholder="Enter your GioSMS Bearer token">
                        <small class="form-text text-muted">Your GioSMS API authentication token (Laravel Sanctum Bearer token).</small>
                    </div>

                    <div class="form-group">
                        <label><strong>API Base URL</strong></label>
                        <input type="url" name="api_url" class="form-control"
                            value="{if $settings.api_url}{$settings.api_url|escape:'html'}{else}https://api.giosms.com/api/v1{/if}"
                            placeholder="https://api.giosms.com/api/v1">
                        <small class="form-text text-muted">Default: https://api.giosms.com/api/v1 — only change for staging/custom endpoints.</small>
                    </div>

                    <div class="form-group">
                        <label><strong>Default Sender ID</strong></label>
                        <input type="text" name="sender_id" class="form-control" value="{$settings.sender_id|escape:'html'}"
                            placeholder="MyBrand" maxlength="16">
                        <small class="form-text text-muted">Your registered sender ID on GioSMS. Max 16 characters.</small>
                    </div>

                    <div class="form-group">
                        <label><strong>Default SMS Type</strong></label>
                        <select name="sms_type" class="form-control">
                            <option value="transactional" {if $settings.sms_type == 'transactional'}selected{/if}>Transactional (recommended)</option>
                            <option value="otp" {if $settings.sms_type == 'otp'}selected{/if}>OTP</option>
                            <option value="promotional" {if $settings.sms_type == 'promotional'}selected{/if}>Promotional</option>
                        </select>
                        <small class="form-text text-muted">Default SMS type for hook-triggered messages. Affects queue priority.</small>
                    </div>

                    <div class="form-group">
                        <label><strong>Admin Notification Phone Numbers</strong></label>
                        <input type="text" name="admin_phones" class="form-control" value="{$settings.admin_phones|escape:'html'}"
                            placeholder="8801712345678, 8801812345678">
                        <small class="form-text text-muted">Comma-separated phone numbers to receive admin notification SMS (new tickets, new clients, etc.).</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="giosms-card">
                <div class="card-header">Connection Test</div>
                <div class="card-body" style="text-align: center;">
                    <p style="font-size: 14px; color: #6c757d;">Test your API configuration.</p>
                    <button type="button" id="giosms-test-btn" class="btn btn-outline-primary" onclick="GioSMS.testConnection()">
                        Test Connection
                    </button>
                    <div id="giosms-test-result" style="margin-top: 16px; display: none;">
                        <span id="giosms-test-status" class="badge" style="font-size: 14px; padding: 8px 16px;"></span>
                        <div id="giosms-test-detail" style="margin-top: 8px; font-size: 13px; color: #6c757d;"></div>
                    </div>
                </div>
            </div>

            <div class="giosms-card">
                <div class="card-header">Module Info</div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td>Module</td>
                            <td class="text-right"><strong>GioSMS</strong></td>
                        </tr>
                        <tr>
                            <td>Version</td>
                            <td class="text-right"><strong>{$settings.module_version|default:'1.0.0'}</strong></td>
                        </tr>
                        <tr>
                            <td>API Health</td>
                            <td class="text-right"><span id="giosms-settings-health" class="badge badge-secondary">Checking...</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="partials/footer.tpl"}
