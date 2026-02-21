{include file="partials/header.tpl"}

<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-3">
        <div class="giosms-stat-card">
            <div class="stat-value">{$stats.total_today}</div>
            <div class="stat-label">SMS Today</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="giosms-stat-card">
            <div class="stat-value">{$stats.total_month}</div>
            <div class="stat-label">SMS This Month</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="giosms-stat-card">
            <div class="stat-value">{$stats.total_all}</div>
            <div class="stat-label">Total SMS Sent</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="giosms-stat-card">
            <div class="stat-value" id="giosms-balance-value">—</div>
            <div class="stat-label">Account Balance</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="giosms-card">
            <div class="card-header">Delivery Stats</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><span class="badge giosms-badge-delivered">Delivered</span></td>
                        <td class="text-right font-weight-bold">{$stats.delivered}</td>
                    </tr>
                    <tr>
                        <td><span class="badge giosms-badge-queued">Queued/Submitted</span></td>
                        <td class="text-right font-weight-bold">{$stats.queued}</td>
                    </tr>
                    <tr>
                        <td><span class="badge giosms-badge-failed">Failed/Error</span></td>
                        <td class="text-right font-weight-bold">{$stats.failed}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="giosms-card">
            <div class="card-header">Cost Summary</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td>Today</td>
                        <td class="text-right font-weight-bold">{$stats.cost_today}</td>
                    </tr>
                    <tr>
                        <td>Total All-Time</td>
                        <td class="text-right font-weight-bold">{$stats.total_cost}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="giosms-card">
            <div class="card-header">API Status</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td>Health</td>
                        <td class="text-right"><span id="giosms-dash-health" class="badge badge-secondary">Checking...</span></td>
                    </tr>
                    <tr>
                        <td>Balance</td>
                        <td class="text-right" id="giosms-dash-balance">—</td>
                    </tr>
                    <tr>
                        <td>Currency</td>
                        <td class="text-right" id="giosms-dash-currency">—</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{include file="partials/footer.tpl"}
