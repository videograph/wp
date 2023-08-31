<?php

// Live Stream page
function videograph_ai_live_stream_page()
{
    // Check if API keys are inserted
    $access_token = get_option('videograph_ai_access_token');
    $secret_key = get_option('videograph_ai_secret_key');

    if (empty($access_token) || empty($secret_key)) {
        echo '<div class="vi-notice-error"><p>The API key is missing or invalid. Please go to the <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">settings</a> page and update it with the correct one.</p></div>';
        return;
    }

    // Check if form is submitted
    if (isset($_POST['start_live_stream'])) {
        // Create Live Stream
        $title = sanitize_text_field($_POST['live_stream_title']);
        $description = "desc";
        $region = sanitize_text_field($_POST['live_stream_region']);
        $record = isset($_POST['live_stream_record']) ? true : false;
        $dvrDurationInMins = 0; // Assuming DVR duration is set to 0
        $tags = ['string']; // Assuming a single tag
        $metadata = [['key' => 'string', 'value' => 'string']]; // Assuming a single metadata item
        $playback_policy = ['public']; // Assuming playback policy is set to public
        $recordings_playback_policy = ['public']; // Assuming recordings playback policy is set to public

        // Send POST request to create live stream
        $api_url = 'https://api.videograph.ai/video/services/api/v1/livestreams';
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($access_token . ':' . $secret_key),
            'Content-Type' => 'application/json',
        );
        $body = json_encode(array(
            'title' => $title,
            'description' => $description,
            'region' => $region,
            'record' => $record,
            'dvrDurationInMins' => $dvrDurationInMins,
            'tags' => $tags,
            'metadata' => $metadata,
            'playback_policy' => $playback_policy,
            'recordings_playback_policy' => $recordings_playback_policy,
        ));

        $response = wp_remote_post($api_url, array('headers' => $headers, 'body' => $body));

        if (is_wp_error($response)) {
            echo '<div class="notice notice-error"><p>Failed to create live stream. Error: ' . $response->get_error_message() . '</p></div>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_message = wp_remote_retrieve_response_message($response);
            $body = wp_remote_retrieve_body($response);
            $live_stream_data = json_decode($body, true);

            // Check if the API request was successful
            if ($response_code === 201) {
                $streamId = $live_stream_data['data']['streamUUID'];

                echo '<div class="notice notice-success"><p style="text-align:left;">Live stream created successfully.<br>Stream ID: <strong>' . esc_html($streamId) . '</strong></p>';

                echo '</div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to create live stream. Check your API Credentials in <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">Settings</a> Page</p></div>';
            }
        }
    }

    // Display live stream form
    ?>
    <div class="wrap">
        <!-- <h1 class="wp-heading-inline">Create a Live Stream</h1> -->
        <div class="livestream-wrap">
            <div class="live_stream_form">
                <h2>Create a Live Stream</h2>
                <div id="loader" style="display: none;">
                    <div class="loader"></div>
                </div>
                <form method="post" id="live-stream-form">
                    <div class="form-field">
                        <label for="live-stream-title">Title:</label>
                        <input type="text" id="live-stream-title" name="live_stream_title" required placeholder="Enter live stream title here">
                    </div>

                    <div class="form-field">
                        <label for="live-stream-region">Region:</label>
                        <select id="live-stream-region" name="live_stream_region">
                            <option value="ap-south-1">AP South 1</option>
                            <option value="us-east-1">US East 1</option>
                            <!-- Add more options for different regions if needed -->
                        </select>
                    </div>
                    <div class="form-field record">
                        <label for="live-stream-record">Record:</label>
                        <input type="checkbox" id="live-stream-record" name="live_stream_record" checked>
                        <span>Live Recording</span>
                    </div>
                    <button type="submit" id="start_live_stream_button" name="start_live_stream" class="button button-primary" disabled>Create Live Stream</button>
                </form>
            </div>
        </div>
    </div>


<script>
  document.addEventListener('DOMContentLoaded', function() {
    const titleField = document.getElementById('live-stream-title');
    const regionField = document.getElementById('live-stream-region');
    const recordCheckbox = document.getElementById('live-stream-record');
    const startButton = document.getElementById('start_live_stream_button');

    // Function to check if required fields are filled
    function validateFields() {
      const titleValue = titleField.value.trim();
      const regionValue = regionField.value;
      
      if (titleValue !== '' && regionValue !== '') {
        startButton.removeAttribute('disabled');
      } else {
        startButton.setAttribute('disabled', true);
      }
    }

    // Add input and change event listeners to the fields
    titleField.addEventListener('input', validateFields);
    regionField.addEventListener('change', validateFields);
    recordCheckbox.addEventListener('change', validateFields);
  });
</script>

    <?php
}