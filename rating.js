jQuery(document).ready(function($) {
    $('.entity-rating').on('click', '.rate', function() {
        var postID = $(this).data('post-id');
        var listID = $(this).data('list-id');
        var rating = $(this).data('rating');
        $.post(ceRating.ajaxurl, {
            action: 'ce_handle_vote',
            post_id: postID,
            list_id: listID,
            rating: rating
        }, function(response) {
            if (response.success) {
                alert('Your vote has been counted! Overall rating: ' + response.data.rating + ', List rating: ' + response.data.list_rating);
                // Update rating on the page
                location.reload();
            } else {
                alert('An error occurred while processing your vote.');
            }
        });
    });

    $('#upload_logo_button').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Select Logo',
            multiple: false
        }).open()
        .on('select', function() {
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $('#entity_logo').val(image_url);
        });
    });
});
