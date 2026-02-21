{include file="partials/header.tpl"}

<form method="post" action="{$modulelink}&tab=hooks">
    <input type="hidden" name="save_hooks" value="1">

    {foreach from=$hookGroups key=group item=hooks}
        {if count($hooks) > 0}
            <div class="giosms-card">
                <div class="card-header" style="text-transform: capitalize;">
                    {$group} Notifications
                </div>
                <div class="card-body">
                    {foreach from=$hooks item=hook}
                        <input type="hidden" name="hook_ids[]" value="{$hook->id}">
                        <div style="border: 1px solid #e9ecef; border-radius: 6px; padding: 16px; margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                <div>
                                    <strong>{$hook->hook_name}</strong>
                                    <span class="badge {if $hook->event_type == 'admin'}badge-warning{else}badge-info{/if}" style="margin-left: 6px;">
                                        {$hook->event_type}
                                    </span>
                                    <div style="font-size: 13px; color: #6c757d; margin-top: 2px;">
                                        {$hook->description}
                                    </div>
                                </div>
                                <label class="giosms-toggle">
                                    <input type="checkbox" name="active_{$hook->id}" value="1" {if $hook->is_active}checked{/if}>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <textarea
                                name="template_{$hook->id}"
                                class="form-control giosms-template-editor"
                                rows="3"
                                placeholder="Enter SMS template..."
                                style="font-size: 14px;"
                            >{$hook->template}</textarea>

                            <div class="giosms-char-info" data-target="template_{$hook->id}">
                                <span class="char-count">0</span> chars |
                                <span class="sms-parts">0</span> SMS part(s) |
                                <span class="encoding">GSM</span>
                            </div>

                            {assign var="fields" value=","|explode:$hook->merge_fields}
                            {if $hook->merge_fields}
                                <div class="giosms-merge-ref">
                                    Available fields:
                                    {assign var="fieldList" value=$hook->merge_fields|json_decode:true}
                                    {if $fieldList}
                                        {foreach from=$fieldList item=field}
                                            <code>{$field}</code>
                                        {/foreach}
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
    {/foreach}

    <div style="text-align: right; margin-top: 10px;">
        <button type="submit" class="btn btn-primary btn-lg">Save All Hook Settings</button>
    </div>
</form>

{include file="partials/footer.tpl"}
