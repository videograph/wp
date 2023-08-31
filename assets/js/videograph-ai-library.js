document.addEventListener('DOMContentLoaded', function() {
            /*var videoActionsBtns = document.querySelectorAll('.video-actions-btn');
            videoActionsBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var menu = this.nextElementSibling;
                    menu.classList.toggle('show');
                });
            });*/

            var videoDeleteLinks = document.querySelectorAll('.video-delete');

            videoDeleteLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    var streamId = this.getAttribute('data-content-id');
                    deleteVideo(streamId);
                });
            });

            /*var videoThumbnails = document.querySelectorAll('.video-thumbnail');
            videoThumbnails.forEach(function(thumbnail) {
                thumbnail.addEventListener('click', function(e) {
                  e.preventDefault();
                  var videoPopupId = this.getAttribute('href');
                  var videoPopup = document.querySelector(videoPopupId);
                  if (videoPopup) {
                    videoPopup.classList.toggle('show');
                    var iframe = videoPopup.querySelector('iframe');
                    if (iframe) {
                      // Stop the video when the popup is closed
                      videoPopup.addEventListener('click', function() {
                        iframe.src = iframe.src; // Set the iframe source to an empty string to stop the video
                      });
                    }
                  }
                });
            });*/
            var videoThumbnails = document.querySelectorAll('.video-thumbnail');
            videoThumbnails.forEach(function(thumbnail) {
                thumbnail.addEventListener('click', function(e) {
                    e.preventDefault();
                    var videoPopupId = this.getAttribute('href');
                    var videoPopup = document.querySelector(videoPopupId);
                    if (videoPopup) {
                        videoPopup.classList.add('show');
                        var iframe = videoPopup.querySelector('iframe');
                        if (iframe) {
                            // Start playing the video when the popup is opened
                            iframe.src = iframe.src.replace('autoplay=0', 'autoplay=1');
                        }
                    }
                });
            });

            var closeButtons = document.querySelectorAll('.close-button');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var videoPopup = this.closest('.video-popup');
                    if (videoPopup) {
                        var iframe = videoPopup.querySelector('iframe');
                        if (iframe) {
                            // Stop the video when the popup is closed
                            iframe.src = iframe.src.replace('autoplay=1', 'autoplay=0');
                        }
                        videoPopup.classList.remove('show');
                    }
                });
            });

            var closeButtons = document.querySelectorAll('.close-button');
            closeButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var videoPopup = this.closest('.video-popup');
                    if (videoPopup) {
                        videoPopup.classList.remove('show');
                    }
                });
            });

            var copyButtons = document.querySelectorAll('.copy-button');
            copyButtons.forEach(function(copyButton) {
                copyButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    var inputField = this.previousElementSibling;
                    inputField.select();
                    document.execCommand('copy');
                    alert('Shortcode copied to clipboard!');
                });
            });

            function deleteVideo(contentId) {
                var xhr = new XMLHttpRequest();
                xhr.open('DELETE', 'https://api.videograph.ai/video/services/api/v1/contents/' + contentId);
                xhr.setRequestHeader('Authorization', 'Basic <?php echo base64_encode($access_token . ':' . $secret_key); ?>');
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert('Video deleted successfully!');
                        location.reload(); // Reload the library page
                    } else {
                        alert('Failed to delete the video. Error code: ' + xhr.status);
                    }
                };
                xhr.send();
            }
        });

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