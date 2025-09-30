<?php
/**
 * Plugin Name:       File Manager with Elasticsearch
 * Plugin URI:        https://example.com/
 * Description:       A file manager plugin with Elasticsearch integration.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-file-manager-elasticsearch
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Admin Menu and Settings
 */
// Add the admin menu page
add_action('admin_menu', 'wpfmes_add_admin_menu');
function wpfmes_add_admin_menu() {
    add_menu_page(
        'File Manager ES',
        'File Manager ES',
        'manage_options',
        'wpfmes_settings',
        'wpfmes_settings_page_html',
        'dashicons-search'
    );
}

// Register the settings
add_action('admin_init', 'wpfmes_settings_init');
function wpfmes_settings_init() {
    register_setting('wpfmes_settings_group', 'wpfmes_options');

    add_settings_section(
        'wpfmes_settings_section_es',
        'Elasticsearch Settings',
        'wpfmes_settings_section_es_callback',
        'wpfmes_settings'
    );

    add_settings_field(
        'wpfmes_es_host',
        'Elasticsearch Host',
        'wpfmes_es_host_render',
        'wpfmes_settings',
        'wpfmes_settings_section_es'
    );

    add_settings_field(
        'wpfmes_es_port',
        'Elasticsearch Port',
        'wpfmes_es_port_render',
        'wpfmes_settings',
        'wpfmes_settings_section_es'
    );

    add_settings_section(
        'wpfmes_settings_section_files',
        'File Settings',
        'wpfmes_settings_section_files_callback',
        'wpfmes_settings'
    );

    add_settings_field(
        'wpfmes_file_directory',
        'Directory to Index',
        'wpfmes_file_directory_render',
        'wpfmes_settings',
        'wpfmes_settings_section_files'
    );
}

// Render functions for fields
function wpfmes_es_host_render() {
    $options = get_option('wpfmes_options');
    ?>
    <input type='text' name='wpfmes_options[es_host]' value='<?php echo esc_attr($options['es_host'] ?? 'localhost'); ?>'>
    <?php
}

function wpfmes_es_port_render() {
    $options = get_option('wpfmes_options');
    ?>
    <input type='text' name='wpfmes_options[es_port]' value='<?php echo esc_attr($options['es_port'] ?? '9200'); ?>'>
    <?php
}

function wpfmes_file_directory_render() {
    $options = get_option('wpfmes_options');
    $upload_dir = wp_upload_dir();
    ?>
    <input type='text' name='wpfmes_options[file_directory]' value='<?php echo esc_attr($options['file_directory'] ?? $upload_dir['basedir']); ?>' size='50'>
    <p class="description">
        The full server path to the directory you want to index. Defaults to the WordPress uploads directory.
    </p>
    <?php
}

// Section callbacks
function wpfmes_settings_section_es_callback() {
    echo '<p>Configure the connection to your Elasticsearch server.</p>';
}

function wpfmes_settings_section_files_callback() {
    echo '<p>Configure the file settings.</p>';
}

// The main settings page HTML
function wpfmes_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wpfmes_settings_group');
            do_settings_sections('wpfmes_settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Scans a directory and returns a hierarchical array of files and folders.
 *
 * @param string $dir The directory path to scan.
 * @return array The hierarchical array of the directory structure.
 */
function wpfmes_scan_directory($dir) {
    $result = [];
    $scan = scandir($dir);

    foreach ($scan as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $path = rtrim($dir, '/') . '/' . $value;
            if (is_dir($path)) {
                $result[] = [
                    'name' => $value,
                    'type' => 'folder',
                    'path' => $path,
                    'children' => wpfmes_scan_directory($path)
                ];
            } else {
                $result[] = [
                    'name' => $value,
                    'type' => 'file',
                    'path' => $path,
                    'file_type' => pathinfo($path, PATHINFO_EXTENSION)
                ];
            }
        }
    }
    return $result;
}

/**
 * Renders the hierarchical file array as an HTML list.
 *
 * @param array $file_tree The hierarchical array of files and folders.
 * @return string The HTML representation of the file tree.
 */
function wpfmes_render_file_tree($file_tree) {
    $html = '<ul>';
    foreach ($file_tree as $item) {
        if ($item['type'] === 'folder') {
            $html .= '<li class="wpfmes-folder">';
            $html .= '<span>' . esc_html($item['name']) . '</span>';
            if (!empty($item['children'])) {
                $html .= wpfmes_render_file_tree($item['children']);
            }
            $html .= '</li>';
        } else {
            $html .= '<li class="wpfmes-file wpfmes-file-ext-' . esc_attr($item['file_type']) . '">';
            $html .= '<a href="' . esc_url(str_replace(ABSPATH, site_url('/'), $item['path'])) . '" target="_blank">';
            $html .= esc_html($item['name']);
            $html .= '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
}

/**
 * Shortcode for the public-facing file manager
 */
add_shortcode('file_manager_search', 'wpfmes_file_manager_shortcode');
function wpfmes_file_manager_shortcode() {
    $options = get_option('wpfmes_options');
    $upload_dir = wp_upload_dir();
    $base_dir = $options['file_directory'] ?? $upload_dir['basedir'];

    ob_start();
    ?>
    <div class="wpfmes-file-manager-wrapper">
        <h2>File Explorer</h2>
        <?php
        if (isset($base_dir) && is_dir($base_dir) && is_readable($base_dir)) {
            $file_tree = wpfmes_scan_directory($base_dir);
            if (empty($file_tree)) {
                echo '<p>The specified directory is empty.</p>';
            } else {
                echo wpfmes_render_file_tree($file_tree);
            }
        } else {
            echo '<p style="color: red;">Error: The configured directory does not exist or is not readable.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue scripts and styles for the frontend.
 */
add_action('wp_enqueue_scripts', 'wpfmes_enqueue_assets');
function wpfmes_enqueue_assets() {
    global $post;
    // Only load assets if the shortcode is on the page to be efficient
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'file_manager_search')) {
        // Enqueue the main stylesheet
        wp_enqueue_style(
            'wpfmes-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            ['dashicons'], // Make sure dashicons are loaded
            '1.0.0'
        );
    }
}