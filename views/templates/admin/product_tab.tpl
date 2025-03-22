<div class="panel productai-panel">
    <div class="panel-heading">
        <i class="icon-magic"></i> {l s='AI Content Generator' mod='productai'}
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label>{l s='Select Language' mod='productai'}</label>
            <select id="ai-language-select" class="form-control">
                {foreach from=$languages item=lang}
                    <option value="{$lang.id_lang}">{$lang.name}</option>
                {/foreach}
            </select>
        </div>
        
        <button id="generate-ai-content" 
                class="btn btn-primary" 
                data-product-id="{$product_id}"
                data-secure-key="{$secure_key}">
            <i class="icon icon-refresh"></i> {l s='Generate Description' mod='productai'}
        </button>
        
        <div id="ai-content-container" class="mt-3"></div>
    </div>
</div>

{literal}
<script>
$(document).ready(function() {
    var ajaxUrl = '{/literal}{$link->getAdminLink('AdminProductAi', true, [], ['ajax' => 1])|escape:'javascript'}{literal}';
    
    $('#generate-ai-content').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $container = $('#ai-content-container');
        
        $btn.prop('disabled', true);
        $container.html('<div class="loading"><i class="icon-spinner icon-spin"></i> {/literal}{l s='Generating...' mod='productai'}{literal}</div>');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'GenerateContent',
                productId: $btn.data('product-id'),
                langId: $('#ai-language-select').val(),
                secure_key: $btn.data('secure-key')
            },
            success: function(response) {
                if (response.content) {
                    $container.html('<div class="alert alert-success">' + response.content + '</div>');
                } else {
                    $container.html('<div class="alert alert-danger">' + (response.error || '{/literal}{l s='Generation failed' mod='productai'}{literal}') + '</div>');
                }
            },
            error: function(xhr) {
                $container.html('<div class="alert alert-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</div>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
{/literal}