jQuery(document).ready(function($) {
    // Add some interactivity if needed
    $('.wp-adserver-settings .cap-check').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            $(this).find('input[type="checkbox"]').trigger('click');
        }
    });
});
