{* GioSMS Admin Header — Navigation Tabs + API Health Badge *}

<style>
.giosms-wrap { font-family: inherit; }
.giosms-nav { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.giosms-nav .nav-pills .nav-link { border-radius: 6px; font-weight: 500; padding: 8px 18px; color: #495057; }
.giosms-nav .nav-pills .nav-link.active { background: #0068d6; color: #fff; }
.giosms-health { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.giosms-health .badge { font-size: 13px; padding: 6px 12px; border-radius: 20px; }
.giosms-card { border: 1px solid #dee2e6; border-radius: 8px; background: #fff; margin-bottom: 20px; }
.giosms-card .card-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 14px 20px; font-weight: 600; border-radius: 8px 8px 0 0; }
.giosms-card .card-body { padding: 20px; }
.giosms-stat-card { text-align: center; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; }
.giosms-stat-card .stat-value { font-size: 28px; font-weight: 700; color: #212529; }
.giosms-stat-card .stat-label { font-size: 13px; color: #6c757d; margin-top: 4px; }
.giosms-badge-queued { background: #ffc107; color: #212529; }
.giosms-badge-submitted { background: #17a2b8; color: #fff; }
.giosms-badge-delivered { background: #28a745; color: #fff; }
.giosms-badge-failed, .giosms-badge-error { background: #dc3545; color: #fff; }
.giosms-toggle { position: relative; display: inline-block; width: 46px; height: 24px; }
.giosms-toggle input { opacity: 0; width: 0; height: 0; }
.giosms-toggle .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 24px; }
.giosms-toggle .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
.giosms-toggle input:checked + .slider { background-color: #0068d6; }
.giosms-toggle input:checked + .slider:before { transform: translateX(22px); }
.giosms-merge-ref { background: #f1f3f5; border-radius: 6px; padding: 10px 14px; font-size: 12px; margin-top: 8px; }
.giosms-merge-ref code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 12px; margin: 2px; display: inline-block; }
.giosms-char-info { font-size: 12px; color: #6c757d; margin-top: 4px; }
</style>

<div class="giosms-wrap">
    <div class="giosms-nav">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {if $tab == 'dashboard'}active{/if}" href="{$modulelink}&tab=dashboard">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {if $tab == 'hooks'}active{/if}" href="{$modulelink}&tab=hooks">Hook Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {if $tab == 'smslog'}active{/if}" href="{$modulelink}&tab=smslog">SMS Log</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {if $tab == 'campaign'}active{/if}" href="{$modulelink}&tab=campaign">Campaign</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {if $tab == 'settings'}active{/if}" href="{$modulelink}&tab=settings">Settings</a>
            </li>
        </ul>
        <div class="giosms-health">
            <span id="giosms-health-badge" class="badge badge-secondary">Checking...</span>
            <span id="giosms-balance-badge" class="badge badge-light" style="border:1px solid #dee2e6; display:none;"></span>
        </div>
    </div>
