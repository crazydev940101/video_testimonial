<?php
/*
Plugin Name: Video Testimonial
Description: Allows users to record video testimonials with a video release statement for marketing purposes. This plugin provides a video testimonial form that users can use to record their feedback and submit it to the website for further processing.
Version: 1.0
Author: Ariel Cruz
License: GPL2
*/

require_once 'database/video-testimonial-database.php';
require_once 'admin/video-testimonial-admin.php';

// Enqueue required scripts and styles
function video_testimonial_enqueue_scripts() {
    wp_enqueue_script('webrtc', 'https://webrtc.github.io/adapter/adapter-latest.js', array(), '1.0', true);
    wp_enqueue_script('recordrtc', 'https://cdn.webrtc-experiment.com/RecordRTC.js', array(), '1.0', true);
    wp_enqueue_script('video-testimonial', plugin_dir_url(__FILE__) . 'assets/js/video-testimonial.js', array('jquery'), '1.0', true);
    wp_enqueue_style('video-testimonial', plugin_dir_url(__FILE__) . 'assets/css/video-testimonial.css');
    wp_localize_script('video-testimonial', 'my_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'video_testimonial_enqueue_scripts');

// Add shortcode for displaying the video testimonial form
function video_testimonial_shortcode() {
    $video_release_label = get_option('video_release_label', 'I grant permission for [od_practicename] to use this testimonial video across their social media and web channels. I understand that I can cancel this authorization at any time by sending a written letter to the practice canceling my authorization to release.s.');
    ob_start();
    ?>
    <div id="video-testimonial-form">
        <h2 class="h4">Record Video Feedback</h2>
        <div class="video-container">
            <video id="video-preview" autoplay playsinline>Your browser does not support the video tag.</video>
            <div id="timer-bar">
                    <div id="timer">05:00</div> 
                </div>
            <div id="controls-bar">
                <div class="record-release-text">
                    <p id="record-release-note">* Press the Allow Camera/Microphone button below and grant access to your device’s camera and microphone.</p>   
                </div>
                <button id="allow-button" class="_odbutton pPbutton txt_white">Allow Camera/Microphone</button> <!-- Add Font Awesome icon for start button -->
                <button id="record-button" class="_odbutton pPbutton txt_white">Record</button> <!-- Add Font Awesome icon for start button -->
              <!--<button id="stop-button" disabled><i class="x-icon x-graphic-child x-graphic-icon x-graphic-primary" aria-hidden="true" data-x-icon-s="&#xf04d;"></i></button> <!-- Add Font Awesome icon for stop button -->
            </div>
        </div>
        <hr style="margin: 50px 0 50px 0; background-color: #c4c4c4;">
        <div class="submit-container">
            <div class="form-group">
                <label for="name-field">Name (required)</label>
                <input type="text" id="name-field" name="name" required>
            </div>
            <div class="form-group">
                <label for="email-field">Email (optional)</label>
                <input type="email" id="email-field" name="email">
            </div>
            <div class="form-group">
                <label for="video-release-checkbox" class="checkbox-inline">
                    <input type="checkbox" id="video-release-checkbox" name="video_release">
                    <?php echo esc_html($video_release_label); ?>
                </label>
            </div>
            <button id="submit-button" class="_odbutton pPbutton txt_white"  disabled>Submit</button>
        </div>
        <div class="go-to-review-page">
            <a href="/review" style="text-decoration:underline;">Go to Review Page</a>    
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('video_testimonial', 'video_testimonial_shortcode');


// Handle form submission
function video_testimonial_submit() {
    if (isset($_FILES['video_data']) && isset($_POST['video_release'])) {
        $video_data = $_FILES['video_data']['tmp_name'];
        $video_release = $_POST['video_release'];
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        // Check if the video release is accepted
        if ($video_release === '1') {
            // Save the video data to the server
            $upload_dir = wp_upload_dir(); // Get the upload directory details
            
            // Generate a unique file name
            $file_path = $upload_dir['path'] . '/' . $_FILES['video_data']['name']; // Set the file path
            
            // Get the base URL of the WordPress site
            $base_url = site_url();
    
            // Move the video file to the desired location
            if (move_uploaded_file($video_data, $file_path)) {
                // Add the video file to the media library
                $attachment = array(
                    'post_mime_type' => 'video/webm',
                    'post_title' => $_FILES['video_data']['name'],
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment($attachment, $file_path);
    
                // Generate metadata for the attachment
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
    
                // Retrieve the marketing team email addresses from the settings
                $marketing_emails = get_option('marketing_emails');
                if(empty($marketing_emails)) {
                    $marketing_emails = 'dave@operationdental.com';
                }
                $marketing_team_emails = explode(',', $marketing_emails);
                $marketing_team_emails = array_map('trim', $marketing_team_emails);
                
                $abs_file_path = str_replace('/home/dwomble/public_html/master.operationdental.com', $base_url, $file_path);
                
                $practicename = do_shortcode('[od_practicename]');
                $to = implode(',', $marketing_team_emails); // Set the recipient email addresses separated by commas
                $subject = $practicename . ' has received a video testimonial';
                $message = '<p>A new video testimonial has been submitted from <strong>' .$name. '</strong>. Please find the attached video file.</p>';
                $message .= '<a href="' .$abs_file_path. '">' .$abs_file_path. '</a>';
                $message .= '<p>You will need to open this link in Chrome to view and download the video file</p>';
                $headers = array('Content-Type: text/html; charset=UTF-8'); // Set the appropriate content type
                // $attachments = array($file_path);
                
                // Send the email
                $email_sent = wp_mail($to, $subject, $message, $headers); //, $attachments);
                
                // Return success or failure response
                if ($email_sent) {
                    // Insert the video testimonial data into the custom table
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'video_testimonials';
                    $file_path = str_replace('/home/dwomble/public_html/master.operationdental.com', $base_url, $file_path);
                    $data = array(
                        'name' => $name,
                        'email' => $email,
                        'video_url' => $file_path,
                        'attachment_id' => $attachment_id,
                        'created_date' => current_time('mysql'),
                    );
                    
                    $format = array( '%s', '%s', '%s', '%s');
                    $result = $wpdb->insert($table_name, $data, $format);
                    
                    if ($result === false) {
                        $error_message = $wpdb->last_error;
                        $wpdb->print_error(); // Print the error message
                        wp_send_json_error('wpdb error: ', $error_message);
                    }else {
                        wp_send_json_success('Video testimonial submitted successfully.');
                    }
                    
                } else {
                    wp_send_json_error('Failed to send the video testimonial email.');
                }
            } else {
                // Failed to save the video
                wp_send_json_error('Failed to save the video testimonial.');
            }
        } else {
            wp_send_json_error('Please accept the video release statement.');
        }
    }
}
add_action('wp_ajax_video_testimonial_submit', 'video_testimonial_submit');
add_action('wp_ajax_nopriv_video_testimonial_submit', 'video_testimonial_submit');