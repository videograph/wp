<?php

add_shortcode('videograph', 'videograph_shortcode');
function videograph_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'content_id' => '',
        ),
        $atts
    );
    
    if (empty($atts['content_id'])) {
        return '';
    }
    
    $url = "https://dashboard.videograph.ai/videos/embed?videoId=" . esc_attr($atts['content_id']);
    
    ob_start();
    ?>
    <div class="container">
        <iframe class="responsive-iframe" src="<?php echo esc_url($url); ?>"></iframe>
    </div>
    <?php
    
    return ob_get_clean();
}

// Library page
function videograph_ai_library_page()
{   
    // Check if API keys are inserted
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
        echo '<div class="notice notice-error"><p>Failed to fetch videos from Videograph AI API. Check your API Credentials in <a href="' . admin_url('admin.php?page=videograph-ai-settings') . '">Settings</a> Page</p></div>';
        return;
    }

    $videos = json_decode($body, true);

    // Check if the response is valid and contains an array of videos
    if (!is_array($videos) || empty($videos['data'])) {
        echo '<div class="notice notice-error"><p>No Videos Found. You can add videos from <a href="' . admin_url('admin.php?page=videograph-ai-add-new-video') . '">here</a></p></div>';
        return;
    }

    $view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
    $valid_view_modes = array('grid', 'list');

    if (!in_array($view_mode, $valid_view_modes)) {
        $view_mode = 'list';
    }

    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Custom filtering function based on the search query
    function custom_video_filter($content, $search_query) {
        return strpos($content['contentId'], $search_query) !== false
            || strpos($content['title'], $search_query) !== false;
    }

    // Filter videos based on the search query
    if (!empty($search_query)) {
        $filtered_videos = array_filter($videos['data'], function ($content) use ($search_query) {
            return custom_video_filter($content, $search_query);
        });
    } else {
        $filtered_videos = $videos['data'];
    }

    // If search query provided and no matching videos found
    if (!empty($search_query) && empty($filtered_videos)) {
        echo '<div class="notice notice-warning"><p>No Videos found matching the search query: ' . esc_html($search_query) . '</p></div>';
    }

    //$per_page = 12; // Number of videos per page
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    $total_videos = count($filtered_videos);
    $total_pages = ceil($total_videos / $per_page);
    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

    $start_index = ($current_page - 1) * $per_page;
    $end_index = $start_index + $per_page;
    $videos_data = array_slice($filtered_videos, $start_index, $per_page);
    
    // Display videos
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Videograph AI Library</h1>
        <a href="<?php echo admin_url('admin.php?page=videograph-ai-add-new-video') ?>" class="page-title-action">Add New</a>
        <hr class="wp-header-end">
        <div class="wp-filter">
            <div class="filter-items">
                <div class="view-switch <?php echo $view_mode; ?>">
                    <a href="<?php echo esc_url(add_query_arg('view', 'list')); ?>" class="view-list" id="view-switch-list" >
                        <span class="screen-reader-text">List view</span>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('view', 'grid')); ?>" class="view-grid" id="view-switch-grid" aria-current="page">
                        <span class="screen-reader-text">Grid view</span>
                    </a>
                </div>
            </div>
            <div>

<?php $url = esc_url(add_query_arg(array('view' => $view_mode), admin_url('admin.php?page=videograph-ai-library&view=' . $view_mode))); ?>


<form id="videos-per-page-form" method="GET" action="">
    <input type="hidden" name="page" value="videograph-ai-library">
    <input type="hidden" name="view" value="<?php echo $view_mode; ?>"> <!-- Add this line -->
    <div class="videos-count">
        <label for="videos-per-page">items per page</label>
        <select id="videos-per-page" name="per_page">
            <option value="10" <?php selected($per_page, 10); ?>>10</option>
            <option value="20" <?php selected($per_page, 20); ?>>20</option>
            <option value="30" <?php selected($per_page, 30); ?>>30</option>
            <!-- Add other options as needed -->
        </select>
    </div>
</form>



                <div class="search-form">
                    <label for="media-search-input" class="media-search-input-label">Search</label>
                    <input type="search" id="media-search-input" class="search" name="s" value="<?php echo esc_attr($search_query); ?>" />
                </div>
            </div>
            
        </div>
        
        <div class="video-container <?php echo ($view_mode === 'list') ? 'list-view' : 'grid-view'; ?>">
            <table class="wp-list-table widefat fixed striped table-view-list posts">
    <thead>
        <tr>
            <th scope="col" class="manage-column">Created at</th>
            <th scope="col" class="manage-column">Title</th>
            <th scope="col" class="manage-column">Video ID</th>
            <th scope="col" class="manage-column">Thumbnail</th>
            <th scope="col" class="manage-column">Duration</th>
            <th scope="col" class="manage-column">Resolution</th>
            <th scope="col" id="date" class="manage-column">Status</th>
            <th scope="col" id="date" class="manage-column">Actions</th>
        </tr>
    </thead>
    <tbody id="the-list">
        <?php foreach ($videos_data as $content) { 
            $created_at = $content['created_at'];
            $timestamp = $created_at / 1000;
            date_default_timezone_set('Asia/Kolkata');
            $date = date('d/m/y h:i a', $timestamp);
            $duration = $content['status'] === 'Ready' ? gmdate('H:i:s', round($content['duration'] / 1000)) : '_';
            $resolution = $content['status'] === 'Ready' ? $content['resolution'] : '_';
            $videoId = $content['contentId'];
        ?>
        <tr>
            <td><?php echo esc_html($date); ?></td>
            <td>
                <a href="#" class="view-details-link" data-stream-id="<?php echo esc_attr($content['contentId']); ?>">
                    <?php echo esc_html($content['title']); ?>                      
                </a>
            </td>
            <td>
                <a href="#" class="view-details-link" data-stream-id="<?php echo esc_attr($content['contentId']); ?>">
                    <?php echo esc_attr($content['contentId']); ?>
                </a>
            </td>
            <td>
                <a href="#" class="view-details-link" data-stream-id="<?php echo esc_attr($content['contentId']); ?>">
                    <figure style="background-image: url(<?php echo esc_url($content['thumbnailUrl']); ?>);"></figure>
                </a>
            </td>
            <td><?php echo esc_attr($duration); ?></td>
            <td><?php echo esc_attr($resolution); ?></td>
            <td id="<?php echo esc_attr($content['status']); ?>" class="status">
                <p class="process">Processing <span class="dashicons dashicons-update"></span></p>
                <p class="ready">Ready <span class="dashicons dashicons-yes-alt"></span></p>
                <p class="failed">Failed <span class="dashicons dashicons-dismiss"></span></p>
            </td>
            <td>
                <div class="video-actions">
                    <button class="video-actions-btn">
                        <span class="dots">.</span>
                    </button>
                    <div class="video-actions-menu">
                        <ul>
                            <li><a href="#" data-stream-id="<?php echo esc_attr($content['contentId']); ?>" class="view-details-link">
                                <span class="dashicons dashicons-info"></span>Video Details
                            </a></li>
                            <li><a href="#" class="video-delete" data-stream-id="<?php echo esc_attr($content['contentId']); ?>">
                                <span class="dashicons dashicons-trash"></span>Delete Video
                            </a></li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>

        <div class="video-item">
                        <a href="#" class="view-details-link" data-stream-id="<?php echo esc_attr($content['contentId']); ?>"></a>
                        <figure style="background-image: url(<?php echo esc_url($content['thumbnailUrl']); ?>);"></figure>
                        <h3 class="video-title"><?php echo esc_html($content['title']); ?></h3>                        
                                        
                </div>

            <?php } ?>
                </tbody>
            </table>
        </div>


<?php
if ($total_pages > 1) {
    echo '<div class="pagination">';
    echo '<span class="displaying-num">' . $total_videos  . ' items </span>';

    // Get the current videos per page setting
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;

    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $prev_url = add_query_arg(array('paged' => $prev_page, 'view' => $view_mode, 'per_page' => $per_page), admin_url('admin.php?page=videograph-ai-library&view=' . $view_mode));
        echo '<a href="' . esc_url($prev_url) . '" class="prev-page button">‹</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $page_url = add_query_arg(array('paged' => $i, 'view' => $view_mode, 'per_page' => $per_page), admin_url('admin.php?page=videograph-ai-library&view=' . $view_mode));
        $active_class = ($i === $current_page) ? 'active' : '';
        echo '<a href="' . esc_url($page_url) . '" class="' . $active_class . ' button">' . $i . '</a>';
    }
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $next_url = add_query_arg(array('paged' => $next_page, 'view' => $view_mode, 'per_page' => $per_page), admin_url('admin.php?page=videograph-ai-library&view=' . $view_mode));
        echo '<a href="' . esc_url($next_url) . '" class="next-page button">›</a>';
    }
    echo '</div>';
}

?>

    </div>

<style>
.container {
  position: relative;
  overflow: hidden;
  width: 100%;
  padding-top: 56.50%;
}
.responsive-iframe {
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  width: 100%;
  height: 100%;
}
</style>

    <script>
        // Function to search and reload videos
        jQuery(document).ready(function ($) {
            // Function to filter table rows based on search input
            function filterTableRows(searchQuery) {
                const $tableRows = $('#the-list tr');

                $tableRows.each(function () {
                    const contentId = $(this).find('td:nth-child(3)').text();
                    const title = $(this).find('td:nth-child(2)').text();

                    if (
                        contentId.toLowerCase().includes(searchQuery.toLowerCase()) ||
                        title.toLowerCase().includes(searchQuery.toLowerCase())
                    ) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            // Event listener for search input changes
            $('#media-search-input').on('input', function () {
                const searchQuery = $(this).val();
                filterTableRows(searchQuery);
            });
        });

        jQuery(document).ready(function ($) {
            // Function to filter table rows based on search input
            function filterTableRows(searchQuery) {
                const $tableRows = $('#the-list tr');

                $tableRows.each(function () {
                    const contentId = $(this).find('td:nth-child(3)').text();
                    const title = $(this).find('td:nth-child(2)').text();

                    if (
                        contentId.toLowerCase().includes(searchQuery.toLowerCase()) ||
                        title.toLowerCase().includes(searchQuery.toLowerCase())
                    ) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            // Event listener for search input changes
            $('#media-search-input').on('input', function () {
                const searchQuery = $(this).val();
                filterTableRows(searchQuery);
            });
        });


        function copyText(inputElement, copyButton) {
            inputElement.select();
            inputElement.setSelectionRange(0, 99999); // For mobile devices

            document.execCommand("copy");
            
            // Use requestAnimationFrame to ensure smooth update of button label
            requestAnimationFrame(function() {
                setTimeout(1500);
            });
        }
        </script>


<div class="popup-content">
    <div class="popup-overlay"></div>
    <!-- <button class="close-popup"><span class="dashicons dashicons-no"></span></button> -->
    <div class="popup-main">
        <div class="video-popup-cnt">
            <div class="video-popup-header">
                <h2>Video Details</h2>
                <button class="close-button"><span class="dashicons dashicons-no"></span></button>
            </div>
            <div class="video-popup-main" id="liveStreamDetailsPopup">
                
            </div>
        </div>
    </div>
</div>

<script>
    function fetchVideoDetails(videoId) {
        // Make API call to fetch livestream details
        var apiUrl = 'https://api.videograph.ai/video/services/api/v1/contents/' + videoId;
        var headers = {
            'Authorization': 'Basic <?php echo base64_encode($access_token . ':' . $secret_key); ?>',
            'Content-Type': 'application/json'
        };

        fetch(apiUrl, {
                method: 'GET',
                headers: headers
            })
            .then(response => response.json())
            .then(data => {
                // Process and display the livestream details in the popup
                var videoDetails = data.data;
                var title = videoDetails.title;
                var description = videoDetails.description;
                var status = videoDetails.status;
                var contentId = videoDetails.contentId;
                var created_at = videoDetails.created_at;
                var timestamp = created_at / 1000;

                // Convert timestamp to a JavaScript Date object
                var date = new Date(timestamp * 1000);

                // Set the timezone offset for India/Kolkata
                var timezoneOffset = 5.5 * 60 * 60 * 1000; // 5 hours and 30 minutes
                date.setTime(date.getTime() + timezoneOffset);

                // Format the date in desired format
                var options = {
                    year: '2-digit',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                };
                var formattedDate = date.toLocaleString('en-IN', options);

                var popupContent = `

<div class="video-popup-main-left">
            <div class="container">
                <iframe class="responsive-iframe" src="https://dashboard.videograph.ai/videos/embed?videoId=${contentId}" allowfullscreen></iframe>
            </div>
        </div>
        <div class="video-popup-main-right">
            <div class="video-details-top">
                <p><strong>File Name: </strong> ${title}</p>
                <p><strong>Created On: </strong> ${formattedDate}</p>
                <p><strong>Published On: </strong> ${formattedDate}</p>
                <p><strong>Video updated On: </strong> ${formattedDate}</p>
            </div>

            <div class="video-details-main">
                <p>
                    <strong>Title: </strong>
                    <input type="text" value="${title}" readonly />
                </p>
                <p>
                    <strong>Description: </strong>
                    <textarea readonly>
                                                ${description}

                    </textarea>
                </p>
                <p>
                    <strong>Tags: </strong>
                    <input type="text" value="" readonly />
                </p>
                <p>
                    <strong>Video Shortcode: </strong>
                    <input type="text" value="[videograph content_id='${contentId}']" readonly />
                    <button onclick="copyText(this.previousElementSibling, this)"><span class="dashicons dashicons-admin-page"></span></button>
                </p>
                <p>
                    <strong>Sharable URL</strong>
                    <input type="text" value="https://dashboard.videograph.ai/videos/embed?videoId=${contentId}" readonly />
                    <button onclick="copyText(this.previousElementSibling, this)"><span class="dashicons dashicons-admin-page"></span></button>
                </p>
            </div>

            <div class="video-details-bottom">
                <a href="https://dashboard.videograph.ai/" target="_blank">Edit on videograph.ai</a> <span>|</span>
                <a href="#" class="video-delete" data-stream-id="${contentId}">Delete Permanently</a>
            </div>
        </div>

                                `;

                document.getElementById('liveStreamDetailsPopup').innerHTML = popupContent;
                document.querySelector('.popup-overlay').style.display = 'block';
                document.querySelector('.popup-content').style.display = 'block';

                var deleteVideoLink = document.querySelectorAll('.video-delete');
                deleteVideoLink.forEach(function(link) {
                    link.addEventListener('click', function(event) {
                        event.preventDefault();
                        var videoId = this.getAttribute('data-stream-id');
                        deleteVideo(videoId);
                    });
                });

                function deleteVideo(videoId) {
                if (confirm("Are you sure you want to delete this video?")) {
                    var apiUrl = 'https://api.videograph.ai/video/services/api/v1/contents/' + videoId;
                    var headers = {
                        'Authorization': 'Basic <?php echo base64_encode($access_token . ':' . $secret_key); ?>',
                        'Content-Type': 'application/json'
                    };

                    fetch(apiUrl, {
                            method: 'DELETE',
                            headers: headers
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'Success') {
                                // Video deleted successfully
                                // You can update the UI or perform any other action as needed
                                location.reload(); // Refresh the page to update the video list
                            } else {
                                // Failed to delete the video
                                alert('Failed to delete the video. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.log('Error:', error);
                        });
                }
            }
            })
            .catch(error => {
                console.log('Error:', error);
            });
    }

    document.addEventListener('DOMContentLoaded', function() {

        var viewDetailsLinks = document.querySelectorAll('.view-details-link');
        var body = document.querySelector('body'); // Select the body element correctly

        viewDetailsLinks.forEach(function(link) {
          link.addEventListener('click', function(event) {
            event.preventDefault();
            var videoId = this.getAttribute('data-stream-id');
            fetchVideoDetails(videoId);
            body.style.overflow = 'hidden'; // Use pure JavaScript to modify CSS
          });
        });

        var closePopupBtn = document.querySelector('.close-button');
        var popupOverlay = document.querySelector('.popup-overlay');
        var popupContent = document.querySelector('.popup-content');

        

        popupOverlay.addEventListener('click', function() {
            popupOverlay.style.display = 'none';
            popupContent.style.display = 'none';
            body.style.overflow = 'visible';
        });

        closePopupBtn.addEventListener('click', function() {
            popupOverlay.style.display = 'none';
            popupContent.style.display = 'none';
            body.style.overflow = 'visible';
        });

        // Delete video functionality
        var deleteVideoLinks = document.querySelectorAll('.video-delete');
        deleteVideoLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var videoId = this.getAttribute('data-stream-id');
                deleteVideo(videoId);
            });
        });

        function deleteVideo(videoId) {
            if (confirm("Are you sure you want to delete this video?")) {
                var apiUrl = 'https://api.videograph.ai/video/services/api/v1/contents/' + videoId;
                var headers = {
                    'Authorization': 'Basic <?php echo base64_encode($access_token . ':' . $secret_key); ?>',
                    'Content-Type': 'application/json'
                };

                fetch(apiUrl, {
                        method: 'DELETE',
                        headers: headers
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'Success') {
                            // Video deleted successfully
                            // You can update the UI or perform any other action as needed
                            location.reload(); // Refresh the page to update the video list
                        } else {
                            // Failed to delete the video
                            alert('Failed to delete the video. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.log('Error:', error);
                    });
            }
        }
    });
</script>


<script>
    // Function to automatically submit the form when the "Videos per page" dropdown value changes
    document.getElementById('videos-per-page').addEventListener('change', function() {
        document.getElementById('videos-per-page-form').submit();
    });

    // Initialize the "Videos per page" dropdown with the current value
    const currentVideosPerPage = <?php echo $per_page; ?>;
    document.getElementById('videos-per-page').value = currentVideosPerPage;

    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("videos-per-page-form");
        const perPageSelect = document.getElementById("videos-per-page");
        
        form.addEventListener("submit", function(event) {
            event.preventDefault();
            
            const selectedPerPage = perPageSelect.value;
            const currentView = "<?php echo $view_mode; ?>";
            
            const newUrl = "<?php echo admin_url('admin.php?page=videograph-ai-library'); ?>" +
                "&per_page=" + selectedPerPage + "&view=" + currentView;
            
            window.location.href = newUrl;
        });
    });
</script>

    <?php
}


