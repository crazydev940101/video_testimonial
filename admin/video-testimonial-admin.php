<?php

require_once 'video-testimonial-admin-video-list.php';

// Add a new section for video testimonial settings
function video_testimonial_settings_section() {
    echo '<h1>Configure video testimonial settings</h1>';
    echo '<br/>';
    echo '<p>If you wanna add video testimonial in any page, you can use this shortcode like [video_testimonial]</p>';
}

// Add fields for marketing team email addresses and video release checkbox label
function video_testimonial_settings_fields() {
    $default_marketing_email = 'test@gmail.com';
    $default_video_release_label = 'I grant permission for [od_practicename] to use this testimonial video across their social media and web channels. I understand that I can cancel this authorization at any time by sending a written letter to the practice canceling my authorization to release.';

    $marketing_emails = get_option('marketing_emails', $default_marketing_email);
    $video_release_label = get_option('video_release_label', $default_video_release_label);
    
    if (empty($marketing_emails)) {
        update_option('marketing_emails', $default_marketing_email);
    }
    
    if (empty($video_release_label)) {
        update_option('video_release_label', $default_video_release_label);
    }
    
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">Marketing Team Email Addresses</th>
            <td>
                <input type="text" name="marketing_emails" value="<?php echo esc_attr($marketing_emails); ?>" class="regular-text">
                <p class="description">Enter the email addresses of the marketing team members, separated by commas.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Video Release Checkbox Label</th>
            <td>
                <input type="text" name="video_release_label" value="<?php echo esc_attr($video_release_label); ?>" class="regular-text">
                <p class="description">Enter the label for the video release checkbox.</p>
            </td>
        </tr>
    </table>
    <?php
}

// Save the video testimonial settings
function video_testimonial_save_settings() {
    if (isset($_POST['marketing_emails'])) {
        update_option('marketing_emails', sanitize_text_field($_POST['marketing_emails']));
    }
    if (isset($_POST['video_release_label'])) {
        update_option('video_release_label', sanitize_text_field($_POST['video_release_label']));
    }
}
add_action('admin_init', 'video_testimonial_save_settings');

// Render the video testimonial settings page
function video_testimonial_settings_page() {
    ?>
    <div class="wrap">
        <h1>Video Testimonial Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('video_testimonial_settings');
            do_settings_sections('video_testimonial_settings');
            video_testimonial_settings_section();
            video_testimonial_settings_fields(); // Add this line to render the settings fields
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the video testimonial settings page
function register_video_testimonial_settings_page() {
    add_menu_page('Video Testimonial Settings', 'Video Testimonial', 'manage_options', 'video_testimonial_settings', 'video_testimonial_settings_page');
    add_submenu_page('video_testimonial_settings', 'Video List', 'Video List', 'manage_options', 'video_testimonial_video_list', 'video_testimonial_video_list_page');
    // add_options_page('Video Testimonial Settings', 'Video Testimonial', 'manage_options', 'video_testimonial_settings', 'video_testimonial_settings_page');
    register_setting('video_testimonial_settings', 'marketing_emails');
    register_setting('video_testimonial_settings', 'video_release_label');
}
add_action('admin_menu', 'register_video_testimonial_settings_page');

// Set default options on plugin/theme activation
function set_default_options() {
    $default_marketing_email = 'test@gmail.com';
    $default_video_release_label = 'I grant permission for [od_practicename] to use this testimonial video across their social media and web channels. I understand that I can cancel this authorization at any time by sending a written letter to the practice canceling my authorization to release.';
    
    add_option('marketing_emails', $default_marketing_email);
    add_option('video_release_label', $default_video_release_label);
}

// Hook the set_default_options() function to plugin/theme activation
register_activation_hook(__FILE__, 'set_default_options');