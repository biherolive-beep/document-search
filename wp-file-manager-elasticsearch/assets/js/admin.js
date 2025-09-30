jQuery(document).ready(function($) {
    $('#wpfmes-force-reindex').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $status = $('#wpfmes-reindex-status');

        // Disable button and show spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.text('Indexing started... Please be patient.').css('color', 'black');

        // Perform AJAX request
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'wpfmes_force_reindex', // Our custom AJAX action
                nonce: wpfmes_admin_nonce // Security nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.text(response.data.message).css('color', 'green');
                } else {
                    $status.text('Error: ' + response.data.message).css('color', 'red');
                }
            },
            error: function() {
                $status.text('An unknown AJAX error occurred.').css('color', 'red');
            },
            complete: function() {
                // Re-enable button and hide spinner
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});