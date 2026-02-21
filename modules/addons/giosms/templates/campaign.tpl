{include file="partials/header.tpl"}

{if $error}
    <div class="alert alert-danger">{$error|escape:'html'}</div>
{/if}

{if $result}
    <div class="alert alert-success">
        Campaign sent successfully!
        {if $result.is_bulk}
            Batch sent to {$result.recipient_count} recipients.
            {if $result.data.batch_id}
                Batch ID: <code>{$result.data.batch_id}</code>
            {/if}
        {else}
            Message sent.
            {if $result.data.message_id}
                Message ID: <code>{$result.data.message_id}</code>
            {/if}
        {/if}
    </div>
{/if}

<div class="row">
    <div class="col-md-8">
        <div class="giosms-card">
            <div class="card-header">Send Campaign SMS</div>
            <div class="card-body">
                <form method="post" action="{$modulelink}&tab=campaign">
                    <input type="hidden" name="send_campaign" value="1">

                    <div class="form-group">
                        <label><strong>Recipients</strong></label>
                        <textarea name="recipients" class="form-control" rows="4"
                            placeholder="Enter phone numbers, one per line or comma-separated.&#10;Example: 8801712345678, 8801812345678"
                        ></textarea>
                        <small class="form-text text-muted">Enter phone numbers with country code. Separate with commas or new lines.</small>
                    </div>

                    <div class="form-group">
                        <label><strong>Message</strong></label>
                        <textarea name="message" id="campaign-message" class="form-control giosms-template-editor" rows="4"
                            placeholder="Type your message here..."
                        ></textarea>
                        <div class="giosms-char-info" data-target="campaign-message">
                            <span class="char-count">0</span> chars |
                            <span class="sms-parts">0</span> SMS part(s) |
                            <span class="encoding">GSM</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Sender ID <small class="text-muted">(optional override)</small></label>
                                <input type="text" name="sender_id" class="form-control" value="{$sender_id|escape:'html'}" maxlength="16">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SMS Type</label>
                                <select name="sms_type" class="form-control">
                                    <option value="transactional">Transactional</option>
                                    <option value="promotional">Promotional</option>
                                    <option value="otp">OTP</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to send this campaign?');">
                        Send Campaign
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="giosms-card">
            <div class="card-header">SMS Info</div>
            <div class="card-body">
                <h6>Character Limits</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>GSM 7-bit (single)</td>
                        <td class="text-right"><strong>160</strong> chars</td>
                    </tr>
                    <tr>
                        <td>GSM 7-bit (multipart)</td>
                        <td class="text-right"><strong>153</strong> chars/part</td>
                    </tr>
                    <tr>
                        <td>Unicode (single)</td>
                        <td class="text-right"><strong>70</strong> chars</td>
                    </tr>
                    <tr>
                        <td>Unicode (multipart)</td>
                        <td class="text-right"><strong>67</strong> chars/part</td>
                    </tr>
                </table>
                <hr>
                <h6>SMS Types</h6>
                <p style="font-size: 13px; margin-bottom: 4px;"><strong>OTP</strong> — Highest priority queue</p>
                <p style="font-size: 13px; margin-bottom: 4px;"><strong>Transactional</strong> — Medium priority (default)</p>
                <p style="font-size: 13px; margin-bottom: 0;"><strong>Promotional</strong> — Low priority queue</p>
            </div>
        </div>
    </div>
</div>

{include file="partials/footer.tpl"}
