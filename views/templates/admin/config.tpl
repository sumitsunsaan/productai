<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {$module->displayName|escape:'html':'UTF-8'}
    </div>
    <div class="panel-body">
        {$content nofilter}
        <div class="alert alert-info">
            <i class="icon-info-circle"></i> 
            {l s='First upload product images before generating descriptions' mod='productai'}
        </div>
    </div>
</div>