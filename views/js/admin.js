// Handle AI content generation
$(document).ready(function() {
    // Initialize loading state
    let isProcessing = false;

    // Generate content button click handler
    $(document).on('click', '#generate-ai-content', function(e) {
        e.preventDefault();
        
        if (isProcessing) return;
        
        const $btn = $(this);
        const $container = $('#ai-content-container');
        const productId = $btn.data('product-id');
        const secureKey = $btn.data('secure-key');
        const langId = $('#ai-language-select').val();

        // Reset container
        $container.html('');
        isProcessing = true;
        $btn.prop('disabled', true).addClass('processing');

        // AJAX request
        $.ajax({
            url: prestashop.urls.base_url + 'module/productai/GenerateContent',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'GenerateContent',
                productId: productId,
                langId: langId,
                secure_key: secureKey
            },
            success: function(response) {
                if (response.error) {
                    showError(response.error);
                } else {
                    showSuccess(response.content);
                }
            },
            error: function(xhr, status, error) {
                showError(prestashop.errors.generic_error + ' (' + xhr.status + ')');
            },
            complete: function() {
                isProcessing = false;
                $btn.prop('disabled', false).removeClass('processing');
            }
        });
    });

    // Show error message
    function showError(message) {
        const $container = $('#ai-content-container');
        $container.html(`
            <div class="alert alert-danger">
                <i class="icon-warning-sign"></i> ${message}
            </div>
        `);
    }

    // Show success content
    function showSuccess(content) {
        const $container = $('#ai-content-container');
        $container.html(`
            <div class="generated-content-wrapper">
                <h4 class="generated-title">${prestashop.productai.generated_title}</h4>
                <div class="generated-content well">${content}</div>
                <button class="btn btn-primary apply-content">
                    <i class="icon-ok"></i> ${prestashop.productai.apply_button}
                </button>
            </div>
        `);
    }

    // Handle content application
    $(document).on('click', '.apply-content', function() {
        const content = $('.generated-content').html();
        tinyMCE.get('description_short').setContent(content);
        $('#description_short').val(content).trigger('change');
        $('#ai-content-container').html('');
    });
});