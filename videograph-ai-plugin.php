<?php
/*
 * Plugin Name: videograph.ai
 * Plugin URI: https://videograph.ai/
 * Description: Accessing videos from videograph.ai
 * Version: 1.1
 * Author: videograph.ai
 * Author URI: https://videograph.ai/
 * 
 * 
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: the text domain of the plugin
 * Domain Path: where to find the translation files (see How to Internationalize Your Plugin)
 */
// Define constants
define('VIDEOGRAPH_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIDEOGRAPH_AI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/library-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/add-new-video-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/upload-new-video-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/settings-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/live-stream-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/live-stream-videos-page.php');
require_once(VIDEOGRAPH_AI_PLUGIN_DIR . 'includes/live-recording-videos-page.php');


register_activation_hook(__FILE__, 'plugin_videograph_set_default_options');

function plugin_videograph_set_default_options()
{
    if (false === get_option('videograph_ai_access_token')) {
        add_option('videograph_ai_access_token', '');
    }
    if (false === get_option('videograph_ai_secret_key')) {
        add_option('videograph_ai_secret_key', '');
    }
}

// Add the plugin to the WordPress menu
// Add the plugin to the WordPress menu
function videograph_ai_add_to_menu()
{
    add_menu_page(
        'Videograph AI Plugin',
        'Videograph AI',
        'manage_options',
        'videograph-ai-library',
        'videograph_ai_library_page',
        'dashicons-video-alt3',
        25
    );

    add_submenu_page(
        'videograph-ai-library',
        'Videos',
        'Videos',
        'manage_options',
        'videograph-ai-library',
        'videograph_ai_library_page',
    );

    add_submenu_page(
        'videograph-ai-library',
        'Add New Video',
        'Add New Video',
        'manage_options',
        'videograph-ai-add-new-video',
        'videograph_ai_add_new_video_page'
    );

    add_submenu_page(
        'videograph-ai-library',
        'Upload Video',
        'Upload Video',
        'manage_options',
        'videograph-ai-upload-video',
        'videograph_ai_upload_video_page'
    );

    add_submenu_page(
        'videograph-ai-library',
        'Create Live Stream', // Create the Live Stream page to the menu
        'Create Live Stream',
        'manage_options',
        'videograph-ai-live-stream',
        'videograph_ai_live_stream_page'
    );
    add_submenu_page(
        'videograph-ai-library',
        'Live Stream', // Add the Live Stream page to the menu
        'Live Stream',
        'manage_options',
        'videograph-ai-live-stream-videos',
        'videograph_ai_live_stream_videos_page'
    );

    add_submenu_page(
        'videograph-ai-library',
        'Live Recording', // Add the Live Stream page to the menu
        'Live Recording',
        'manage_options',
        'videograph-ai-live-recording-videos-page',
        'videograph_ai_live_recording_videos_page'
    );

    add_submenu_page(
        'videograph-ai-library',
        'Settings',
        'Settings',
        'manage_options',
        'videograph-ai-settings',
        'videograph_ai_settings_page'
    );
}

add_action('admin_menu', 'videograph_ai_add_to_menu');
add_action('admin_post_create_post_action', 'videograph_ai_add_new_video_callback');
add_action('admin_post_create_post_action', 'videograph_ai_upload_video_callback');
add_action('admin_post_create_post_action', 'videograph_ai_library_pagination');

function videograph_ai_enqueue_scripts() {
    // Enqueue CSS
    wp_enqueue_style( 'videograph-ai-style', plugin_dir_url( __FILE__ ) . 'assets/css/videograph-ai-style.css', array(), '6.3.1' );

    // Enqueue JS
    wp_enqueue_script( 'videograph-ai-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/videograph-ai-scripts.js', array(), '1.0.1', true );
}
add_action( 'admin_enqueue_scripts', 'videograph_ai_enqueue_scripts' );