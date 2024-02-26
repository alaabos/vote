
jQuery(document).ready(function($) {
    $('.vote-button').on('click', function() {
        var post_id = $(this).data('postid');
        var vote = $(this).data('vote');

        $.ajax({
            type: 'post',
            dataType: 'json',
            url: wah_ajax_obj.ajaxurl,
            data: {
                action: 'submit_vote',
                post_id: post_id,
                vote: vote,
                nonce: wah_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the percentages
                    $('.wah-bar-yes').width(response.data.yes_percentage + '%').text(response.data.yes_percentage + '%');
                    $('.wah-bar-no').width(response.data.no_percentage + '%').text(response.data.no_percentage + '%');

                    // Show the feedback message
                    $('#wah-feedback').show();
                }
            }
        });
    });
});
