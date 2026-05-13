jQuery(document).ready(function($) {
    // Add some interactivity if needed
    $('.wp-adserver-settings .cap-check').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            $(this).find('input[type="checkbox"]').trigger('click');
        }
    });

    // Toggle Ad Status
    $(document).on('click', '.wp-ad-status-toggle', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        var nonce = $btn.data('nonce');

        $btn.css('opacity', '0.5');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_adserver_toggle_status',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.html(response.data.html);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Something went wrong. Please try again.');
            },
            complete: function() {
                $btn.css('opacity', '1');
            }
        });
    });
});
