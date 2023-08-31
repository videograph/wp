<?php
// Add New Video page
function videograph_ai_add_new_video_page()
{
    // Check if API credentials exist
    $access_token = get_option('videograph_ai_access_token');
    $secret_key = get_option('videograph_ai_secret_key');

    if (empty($access_token) || empty($secret_key)) {
        echo '<div class="vi-notice-error"><p>The API key is missing or invalid. Please go to the <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">settings</a> page and update it with the correct one.</p></div>';
        return;
    }


    $api_url = 'https://api.videograph.ai/video/services/api/v1/contents';
    $headers = array(
        'Authorization' => 'Basic ' . base64_encode($access_token . ':' . $secret_key),
        'Content-Type' => 'application/json',
    );

    $response = wp_remote_get($api_url, array('headers' => $headers));

    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>Failed to fetch videos from Videograph AI API.</p></div>';
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $response_code = wp_remote_retrieve_response_code($response);

    // Check if the API request was successful
    if ($response_code !== 200) {
        echo '<div class="notice notice-error"><p>Check your API Credentials in <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">Settings</a> Page</p></div>';
        return;
    }

    if (isset($_GET['success']) && $_GET['success'] === '1') {
        echo '<div class="notice notice-success"><p>Video uploaded successfully!</p></div>';
    } elseif (isset($_GET['success']) && $_GET['success'] !== '1') {
        echo '<div class="notice notice-error"><p>Error: Invalid Videograph API credentials. Please check your API credentials in the <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">Settings</a> page.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Add New Video</h1>
        <a href="<?php echo admin_url('admin.php?page=videograph-ai-upload-video') ?>" class="page-title-action">Upload Video</a>
        <div class="livestream-wrap">
            <div class="live_stream_form">
                
                <?php
                // Display error messages
                if (isset($_GET['error'])) {
                    echo '<div class="notice notice-error"><p>Error: ' . sanitize_text_field($_GET['error']) . '</p></div>';
                }
                ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="create_post_action">
                    <?php wp_nonce_field('create_post_nonce', 'nonce'); ?>

                    <div class="form-field">
                        <label for="post_title">Title</label>
                        <input type="text" id="post_title" name="post_title" class="regular-text" required placeholder="Enter video title here">
                    </div>
                    <div class="form-field">
                        <label for="post_url">Video URL</label>
                        <input type="url" id="post_url" name="post_url" class="regular-text" required placeholder="Enter video URL here">
                    </div>
                    <button type="submit" id="add_video_button" name="add_video" class="button button-primary" disabled>Add Video</button>
                </form>
            </div>
        </div>
    </div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const titleField = document.getElementById('post_title');
    const urlField = document.getElementById('post_url');
    const addButton = document.getElementById('add_video_button');

    // Function to check if both fields are non-empty
    function validateFields() {
      if (titleField.value.trim() !== '' && urlField.value.trim() !== '') {
        addButton.removeAttribute('disabled');
      } else {
        addButton.setAttribute('disabled', true);
      }
    }

    // Add input event listeners to both fields
    titleField.addEventListener('input', validateFields);
    urlField.addEventListener('input', validateFields);
  });
</script>



    <?php
}

// Callback function for form submission
function videograph_ai_add_new_video_callback()
{
    // Check if API credentials exist
    $access_token = get_option('videograph_ai_access_token');
    $secret_key = get_option('videograph_ai_secret_key');

    if (empty($access_token) || empty($secret_key)) {
        echo '<div class="notice notice-error"><p>Error: Videograph API credentials do not exist. Add Your API Credentials in <a>Seetings</a> Page</p></div>';
        return; // Stop further processing
    }

    // Verify the nonce
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'create_post_nonce')) {
        // Get the title and video URL from the form submission
        $title = sanitize_text_field($_POST['post_title']);
        $url = esc_url_raw($_POST['post_url']);

        // Prepare POST request data
        $data = array(
            'title' => $title,
            'content' => array(
                array(
                    'url' => $url
                )
            ),
            'playback_policy' => array(
                'public',
                'signed'
            ),
            'mp4_support' => true,
            'save_original_copy' => true
        );

        // Send the POST request
        $api_url = 'https://api.videograph.ai/video/services/api/v1/contents';
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($access_token . ':' . $secret_key)
        );

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30,
            'sslverify' => true, // Change this to true if your server supports SSL verification
        ));

        // Check the response status
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo '<div class="notice notice-error"><p>Error: ' . $error_message . '</p></div>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response));

            if ($response_code === 201) {
                // Success message with stream ID
                $stream_id = isset($response_body->id) ? $response_body->id : '';
                echo '<div class="notice notice-success"><p>Video uploaded successfully! Stream ID: ' . $stream_id . '</p></div>';
                wp_safe_redirect(admin_url('admin.php?page=videograph-ai-add-new-video&success=1'));
                exit;
            } elseif ($response_code === 401) {
                // Authentication error
                $error_message = 'Invalid Videograph API credentials. Please check your API credentials in the Settings page.';
                wp_safe_redirect(admin_url('admin.php?page=videograph-ai-add-new-video&error=' . $error_message));
                //echo '<div class="notice notice-error"><p>Error: Invalid Videograph API credentials. Please check your API credentials in the Settings page.</p></div>';
            } else {
                // Other error
                $error_message = 'Failed to upload video';
                echo '<div class="notice notice-error"><p>Error: ' . $error_message . '</p></div>';
            }
        }
    } else {
        // Invalid nonce
        echo '<div class="notice notice-error"><p>Invalid nonce. Form submission is not valid.</p></div>';
    }
}