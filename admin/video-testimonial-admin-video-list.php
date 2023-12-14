<?php

// Fetch the video testimonial data from the custom table
global $wpdb;
$table_name = $wpdb->prefix . 'video_testimonials';
$testimonials = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_date DESC");

function enqueue_video_testimonial_scripts() {
    wp_enqueue_script('video-testimonial-admin', plugin_dir_url(__FILE__) . '../assets/js/video-testimonial-admin.js', array('jquery'), '1.0', true);
    wp_localize_script('video-testimonial-admin', 'my_ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_video_testimonial_scripts');

// Add a new submenu page called "Video List"
function video_testimonial_video_list_page() {
    global $testimonials; // Import the global variable into the function's scope
    ?>
    <div class="wrap">
        <h1>Video List</h1>
        <!-- Add your video list content here -->
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Video URL</th>
                    <th>Created Date</th>
                    <th>Download</th> <!-- New column for the download button -->
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($testimonials as $testimonial) {
                        echo '<tr>';
                        echo '<td>' . $testimonial->name . '</td>';
                        echo '<td>' . $testimonial->email . '</td>';
                        echo '<td><a href="' . $testimonial->video_url . '" target="_blank">' . $testimonial->video_url . '</a></td>';
                        echo '<td>' . $testimonial->created_date . '</td>';
                        echo '<td><a href="' . wp_get_attachment_url(  $testimonial->attachment_id ) . '" download>Download</a></td>'; // Download button
                        echo '<td><button class="delete-video-button" data-video-id="' . $testimonial->id . '">Delete</button></td>'; // Delete button
                        echo '</tr>';
                    }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}


// Register the delete_video_testimonial AJAX action
function delete_video_testimonial() {
   if (isset($_POST['video_id'])) {
        $videoId = $_POST['video_id'];

        // Delete the video testimonial from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'video_testimonials';
        $testimonial = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $videoId));
        
        if (!$testimonial) {
            wp_send_json_error('Video testimonial not found.');
        }
        
        // Delete the video attachment from the media library
        $attachmentId = $testimonial->attachment_id;
        wp_delete_attachment($attachmentId, true);
        
        // Delete the video testimonial from the custom table
        $result = $wpdb->delete($table_name, ['id' => $videoId]);

        if ($result) {
            wp_send_json_success(array('redirect_url' => get_admin_url(null, 'admin.php?page=video_testimonial_video_list')));
        } else {
            wp_send_json_error('Failed to delete the video.');
        }
    }
}
add_action('wp_ajax_delete_video_testimonial', 'delete_video_testimonial');
add_action('wp_ajax_nopriv_delete_video_testimonial', 'delete_video_testimonial');