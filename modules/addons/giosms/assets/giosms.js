/**
 * GioSMS Admin JavaScript
 * Handles AJAX calls, character counter, SMS part counter, health badge.
 */
var GioSMS = {
    moduleLink: '',

    init: function(moduleLink) {
        this.moduleLink = moduleLink;
        this.initCharCounters();
        this.loadHealth();
        this.loadBalance();
    },

    /**
     * Initialize character counters on all template editors.
     */
    initCharCounters: function() {
        var editors = document.querySelectorAll('.giosms-template-editor');
        editors.forEach(function(editor) {
            var infoEl = editor.parentElement.querySelector('.giosms-char-info');
            if (!infoEl) return;

            var update = function() {
                var text = editor.value;
                var info = GioSMS.getCharInfo(text);
                infoEl.querySelector('.char-count').textContent = info.length;
                infoEl.querySelector('.sms-parts').textContent = info.parts;
                infoEl.querySelector('.encoding').textContent = info.encoding.toUpperCase();
            };

            editor.addEventListener('input', update);
            editor.addEventListener('keyup', update);
            // Run on load
            update();
        });
    },

    /**
     * Calculate character count, encoding, and SMS parts.
     */
    getCharInfo: function(text) {
        var encoding = 'gsm';
        // Simple Unicode detection: check for chars outside basic ASCII + common GSM
        for (var i = 0; i < text.length; i++) {
            var code = text.charCodeAt(i);
            if (code > 127 && '€'.indexOf(text[i]) === -1) {
                // Allow some known GSM extension chars
                var gsmExtended = ['\f', '[', '\\', ']', '^', '{', '|', '}', '~', '€'];
                if (gsmExtended.indexOf(text[i]) === -1) {
                    encoding = 'unicode';
                    break;
                }
            }
        }

        var len = text.length;
        var parts;
        if (encoding === 'gsm') {
            parts = len <= 160 ? (len > 0 ? 1 : 0) : Math.ceil(len / 153);
        } else {
            parts = len <= 70 ? (len > 0 ? 1 : 0) : Math.ceil(len / 67);
        }

        return { length: len, encoding: encoding, parts: parts };
    },

    /**
     * Load API health status.
     */
    loadHealth: function() {
        var badge = document.getElementById('giosms-health-badge');
        var dashBadge = document.getElementById('giosms-dash-health');
        var settingsBadge = document.getElementById('giosms-settings-health');

        this.ajax('health', function(data) {
            var isOk = data.status === 'ok';
            var text = isOk ? 'Connected' : 'Failed';
            var cls = isOk ? 'badge badge-success' : 'badge badge-danger';

            if (badge) { badge.textContent = text; badge.className = cls; }
            if (dashBadge) { dashBadge.textContent = text; dashBadge.className = cls; }
            if (settingsBadge) { settingsBadge.textContent = text; settingsBadge.className = cls; }
        }, function() {
            if (badge) { badge.textContent = 'Error'; badge.className = 'badge badge-danger'; }
        });
    },

    /**
     * Load balance via AJAX.
     */
    loadBalance: function() {
        this.ajax('balance', function(data) {
            if (data.success && data.data) {
                var bal = parseFloat(data.data.balance).toFixed(2);
                var currency = data.data.currency || '';

                var balBadge = document.getElementById('giosms-balance-badge');
                if (balBadge) {
                    balBadge.textContent = 'Balance: ' + bal + ' ' + currency;
                    balBadge.style.display = 'inline-block';
                }

                var balValue = document.getElementById('giosms-balance-value');
                if (balValue) balValue.textContent = bal + ' ' + currency;

                var dashBal = document.getElementById('giosms-dash-balance');
                if (dashBal) dashBal.textContent = bal;

                var dashCur = document.getElementById('giosms-dash-currency');
                if (dashCur) dashCur.textContent = currency;
            }
        });
    },

    /**
     * Test API connection (Settings page).
     */
    testConnection: function() {
        var btn = document.getElementById('giosms-test-btn');
        var resultDiv = document.getElementById('giosms-test-result');
        var statusEl = document.getElementById('giosms-test-status');
        var detailEl = document.getElementById('giosms-test-detail');

        btn.disabled = true;
        btn.textContent = 'Testing...';
        resultDiv.style.display = 'block';
        statusEl.textContent = 'Checking...';
        statusEl.className = 'badge badge-secondary';
        detailEl.textContent = '';

        this.ajax('test_connection', function(data) {
            btn.disabled = false;
            btn.textContent = 'Test Connection';

            if (data.status === 'ok') {
                statusEl.textContent = 'Connected';
                statusEl.className = 'badge badge-success';
                var detail = 'API is reachable and authenticated.';
                if (data.balance !== null && data.balance !== undefined) {
                    detail += ' Balance: ' + parseFloat(data.balance).toFixed(2);
                    if (data.currency) detail += ' ' + data.currency;
                }
                detailEl.textContent = detail;
            } else {
                statusEl.textContent = 'Failed';
                statusEl.className = 'badge badge-danger';
                detailEl.textContent = data.message || 'Connection test failed.';
            }
        }, function() {
            btn.disabled = false;
            btn.textContent = 'Test Connection';
            statusEl.textContent = 'Error';
            statusEl.className = 'badge badge-danger';
            detailEl.textContent = 'Could not reach the server.';
        });
    },

    /**
     * Generic AJAX helper.
     */
    ajax: function(action, onSuccess, onError) {
        var url = this.moduleLink + '&ajax=' + encodeURIComponent(action);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 30000;

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (onSuccess) onSuccess(data);
                } catch (e) {
                    if (onError) onError(e);
                }
            } else {
                if (onError) onError(new Error('HTTP ' + xhr.status));
            }
        };

        xhr.onerror = function() { if (onError) onError(new Error('Network error')); };
        xhr.ontimeout = function() { if (onError) onError(new Error('Timeout')); };
        xhr.send();
    }
};
