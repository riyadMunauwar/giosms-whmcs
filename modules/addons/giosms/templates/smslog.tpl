{include file="partials/header.tpl"}

<div class="giosms-card">
    <div class="card-header">
        SMS Log
        <span style="font-weight: normal; font-size: 13px; color: #6c757d; margin-left: 10px;">
            {$logs.total} total entries
        </span>
    </div>
    <div class="card-body">
        {* Filters *}
        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="module" value="giosms">
            <input type="hidden" name="tab" value="smslog">
            <div class="row">
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        {foreach from=['queued','submitted','delivered','failed','error'] item=s}
                            <option value="{$s}" {if $filters.status == $s}selected{/if}>{$s|ucfirst}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="hook_name" class="form-control form-control-sm">
                        <option value="">All Hooks</option>
                        {foreach from=$hookNames item=h}
                            <option value="{$h}" {if $filters.hook_name == $h}selected{/if}>{$h}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{$filters.date_from}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{$filters.date_to}" placeholder="To">
                </div>
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control form-control-sm" value="{$filters.search}" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{$modulelink}&tab=smslog" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>

        {* Log Table *}
        {if $logs.total > 0}
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Hook</th>
                            <th>Message</th>
                            <th>Parts</th>
                            <th>Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$logs.data item=log}
                            <tr>
                                <td style="white-space: nowrap; font-size: 13px;">{$log->created_at}</td>
                                <td><code>{$log->recipient}</code></td>
                                <td>
                                    {if $log->hook_name}
                                        <span class="badge badge-light" style="border:1px solid #dee2e6;">{$log->hook_name}</span>
                                    {else}
                                        <span class="badge badge-secondary">Campaign</span>
                                    {/if}
                                </td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px;">
                                    {$log->message|escape:'html'|truncate:80}
                                </td>
                                <td>{$log->sms_count}</td>
                                <td>{if $log->cost}{$log->cost}{else}—{/if}</td>
                                <td>
                                    <span class="badge giosms-badge-{$log->status}">{$log->status|ucfirst}</span>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {* Pagination *}
            {if $logs.pages > 1}
                <nav>
                    <ul class="pagination pagination-sm justify-content-center">
                        {for $p=1 to $logs.pages}
                            <li class="page-item {if $p == $logs.page}active{/if}">
                                <a class="page-link" href="{$modulelink}&tab=smslog&page={$p}&status={$filters.status}&hook_name={$filters.hook_name}&date_from={$filters.date_from}&date_to={$filters.date_to}&search={$filters.search}">{$p}</a>
                            </li>
                        {/for}
                    </ul>
                </nav>
            {/if}
        {else}
            <div class="text-center text-muted" style="padding: 40px 0;">
                No SMS log entries found.
            </div>
        {/if}
    </div>
</div>

{include file="partials/footer.tpl"}
