jQuery(document).ready(function($) {
    // Handle delete button click event
    $('.delete-video-button').on('click', function() {
        var videoId = $(this).data('video-id');
        var confirmation = confirm('Are you sure you want to delete this video?');

        if (confirmation) {
            // Send an AJAX request to delete the video
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_video_testimonial',
                    video_id: videoId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Successfully deleted the video file.');
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    alert('An error occurred while deleting the video.');
                }
            });
        }
    });
});