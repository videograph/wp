<?php

function videograph_ai_upload_video_page() {
    // Check if API credentials exist
    $access_token = get_option('videograph_ai_access_token');
    $secret_key = get_option('videograph_ai_secret_key');

    if (empty($access_token) || empty($secret_key)) {
        echo '<div class="vi-notice-error"><p>The API key is missing or invalid. Please go to the <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">settings</a> page and update it with the correct one.</p></div>';
        return;
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if API keys are inserted
        $access_token = get_option('videograph_ai_access_token');
        $secret_key = get_option('videograph_ai_secret_key');

        if (empty($access_token) || empty($secret_key)) {
            echo '<div class="vi-notice-error"><p>Please insert your API keys in the <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">Settings</a> page to upload videos.</p></div>';
            return;
        }

        // Get the video file details from the form submission
        $video_file = $_FILES['video_file'];
        $video_name_with_extension = $video_file['name']; // Use the video name with extension as the file name
        $video_duration = 0; // Replace with the actual duration of the video (in seconds)

        // Check if file is selected
        if (empty($video_file)) {
            echo '<div class="vi-notice-error"><p>Please select a video file to upload.</p></div>';
            return;
        }

        // Check for any upload errors
        if ($video_file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="vi-notice-error"><p>Failed to upload the video file. Please try again.</p></div>';
            return;
        }

        // Get the upload URL from the API
        $upload_url_endpoint = 'https://api.videograph.ai/video/services/api/v1/uploads';
        $headers = array(
            'Authorization: Basic ' . base64_encode($access_token . ':' . $secret_key),
            'Content-Type: application/json',
        );

        // Prepare the payload for the API request
        $payload = array(
            'file_name' => $video_name_with_extension,
            'duration' => $video_duration,
        );

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $upload_url_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, 
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response_data = json_decode($response, true);
            if (isset($response_data['status']) && $response_data['status'] === 'Success') {
                $upload_url = $response_data['data']['url'];

                // Use cURL to upload the video to the obtained upload URL
                $curl_upload = curl_init();
                curl_setopt($curl_upload, CURLOPT_URL, $upload_url);
                curl_setopt($curl_upload, CURLOPT_PUT, 1);
                curl_setopt($curl_upload, CURLOPT_INFILESIZE, filesize($video_file['tmp_name']));
                curl_setopt($curl_upload, CURLOPT_INFILE, fopen($video_file['tmp_name'], 'rb'));
                curl_setopt($curl_upload, CURLOPT_RETURNTRANSFER, true);
                curl_exec($curl_upload);
                $http_code = curl_getinfo($curl_upload, CURLINFO_HTTP_CODE);
                curl_close($curl_upload);

                        // Check the upload response for success or failure
                if ($http_code === 200) {
                    echo '<div class="notice notice-success"><p>Video uploaded successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Failed to upload the video. Please try again.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Failed to get the video upload URL. Please try again.</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Upload Video</h1>
        <hr class="wp-header-end"><br>

        <form method="post" enctype="multipart/form-data">
            <!-- <div class="form-field">
                <label for="video_file">Video File</label>
                <input type="file" name="video_file" id="video_file" required>
            </div> -->
            <div class="form-field">
                <div id="drop_zone">
                    <div class="drop-cnt">
                        <p>Drop files to upload</p>
                        <span>or</span>
                        <button class="button">Select Files</button>
                    </div>
                    <input type="file" name="video_file" id="video_file" style="display: none;" required accept="video/mp4,video/x-m4v,video/*">
                </div>
            </div>
            <!-- Progress bar to display the upload percentage -->
            <div class="progress-bar" style="display: none;">
                <div class="progress-bar-fill" style="width: 0%;"></div>
            </div>
           
            <input type="submit" class="button button-primary" value="Upload">
        </form>
    </div>
    
    <?php
}
