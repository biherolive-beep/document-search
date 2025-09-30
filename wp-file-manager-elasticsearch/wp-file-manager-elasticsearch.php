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

// Include the Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Handle the case where the autoloader is missing
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>';
        echo '<strong>File Manager with Elasticsearch:</strong> Composer autoloader not found. Please run the build script or download a packaged version of the plugin.';
        echo '</p></div>';
    });
    return; // Stop further execution
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

    add_settings_section(
        'wpfmes_settings_section_automation',
        'Automation Settings',
        'wpfmes_settings_section_automation_callback',
        'wpfmes_settings'
    );

    add_settings_field(
        'wpfmes_enable_cron',
        'Automatic Daily Re-indexing',
        'wpfmes_enable_cron_render',
        'wpfmes_settings',
        'wpfmes_settings_section_automation'
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

function wpfmes_settings_section_automation_callback() {
    echo '<p>Enable or disable automatic features.</p>';
}

// Render function for the cron checkbox
function wpfmes_enable_cron_render() {
    $options = get_option('wpfmes_options');
    ?>
    <input type='checkbox' name='wpfmes_options[enable_cron]' <?php checked(isset($options['enable_cron']), 1); ?> value='1'>
    <p class="description">
        If checked, the plugin will automatically re-index all files once a day.
    </p>
    <?php
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

        <hr>

        <h2>Indexing Actions</h2>
        <div id="wpfmes-indexing-actions">
            <p>Click the button below to delete all existing data from the index and re-index all files from your configured directory. This can take some time depending on the number and size of your files.</p>
            <button id="wpfmes-force-reindex" class="button button-primary">Force Re-index</button>
            <span class="spinner" style="float: none; vertical-align: middle; margin-left: 5px;"></span>
            <div id="wpfmes-reindex-status" style="margin-top: 10px; font-weight: bold;"></div>
        </div>
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
    $search_query = isset($_GET['wpfmes_search']) ? sanitize_text_field($_GET['wpfmes_search']) : '';

    ob_start();
    ?>
    <div class="wpfmes-file-manager-wrapper">
        <h2>File Search</h2>
        <form method="get" action="">
            <input type="text" name="wpfmes_search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search file contents..." size="50">
            <input type="submit" value="Search">
        </form>

        <div class="wpfmes-results-area" style="margin-top: 20px;">
        <?php
        if (!empty($search_query)) {
            echo "<h3>Search Results for '" . esc_html($search_query) . "'</h3>";
            $results = wpfmes_search_files($search_query);
            echo wpfmes_render_search_results($results);
        } else {
            echo "<h3>Browse Files</h3>";
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
        }
        ?>
        </div>
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

/**
 * Enqueue scripts and styles for the admin panel.
 */
add_action('admin_enqueue_scripts', 'wpfmes_admin_enqueue_assets');
function wpfmes_admin_enqueue_assets($hook) {
    // Only load on our plugin's settings page
    if ($hook !== 'toplevel_page_wpfmes_settings') {
        return;
    }

    wp_enqueue_script(
        'wpfmes-admin-js',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Pass data to the script
    wp_localize_script(
        'wpfmes-admin-js',
        'wpfmes_admin_nonce',
        wp_create_nonce('wpfmes_reindex_nonce')
    );
}

/**
 * AJAX handler for the force re-index action.
 */
add_action('wp_ajax_wpfmes_force_reindex', 'wpfmes_force_reindex_ajax_handler');
function wpfmes_force_reindex_ajax_handler() {
    check_ajax_referer('wpfmes_reindex_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
    }

    $result = wpfmes_index_files();

    if ($result['success']) {
        wp_send_json_success(['message' => $result['message']]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

/**
 * Cron job and scheduling logic
 */
// The action hook for our cron job
add_action('wpfmes_daily_reindex_event', 'wpfmes_cron_reindex_files');

// The function that the cron job will execute
function wpfmes_cron_reindex_files() {
    $options = get_option('wpfmes_options');
    if (isset($options['enable_cron']) && $options['enable_cron'] == 1) {
        error_log('WP File Manager ES: Daily re-index cron job started.');
        wpfmes_index_files();
    }
}

// Function to handle scheduling when settings are saved
add_action('update_option_wpfmes_options', 'wpfmes_handle_cron_on_settings_save', 10, 2);
function wpfmes_handle_cron_on_settings_save($old_value, $new_value) {
    $old_cron_status = isset($old_value['enable_cron']) && $old_value['enable_cron'] == 1;
    $new_cron_status = isset($new_value['enable_cron']) && $new_value['enable_cron'] == 1;

    if ($new_cron_status && !$old_cron_status) {
        // If the setting was just turned ON
        if (!wp_next_scheduled('wpfmes_daily_reindex_event')) {
            wp_schedule_event(time(), 'daily', 'wpfmes_daily_reindex_event');
        }
    } elseif (!$new_cron_status && $old_cron_status) {
        // If the setting was just turned OFF
        $timestamp = wp_next_scheduled('wpfmes_daily_reindex_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wpfmes_daily_reindex_event');
        }
    }
}

// Schedule event on plugin activation if not already scheduled
register_activation_hook(__FILE__, 'wpfmes_plugin_activation');
function wpfmes_plugin_activation() {
    $options = get_option('wpfmes_options');
    if (isset($options['enable_cron']) && $options['enable_cron'] == 1) {
        if (!wp_next_scheduled('wpfmes_daily_reindex_event')) {
            wp_schedule_event(time(), 'daily', 'wpfmes_daily_reindex_event');
        }
    }
}

// Unschedule event on plugin deactivation
register_deactivation_hook(__FILE__, 'wpfmes_plugin_deactivation');
function wpfmes_plugin_deactivation() {
    $timestamp = wp_next_scheduled('wpfmes_daily_reindex_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wpfmes_daily_reindex_event');
    }
}

/**
 * Creates and returns an Elasticsearch client instance.
 *
 * @return \Elasticsearch\Client|null The Elasticsearch client instance or null on failure.
 */
function wpfmes_get_es_client() {
    $options = get_option('wpfmes_options');
    $host = $options['es_host'] ?? 'localhost';
    $port = $options['es_port'] ?? '9200';

    if (empty($host) || empty($port)) {
        return null;
    }

    try {
        $client = \Elasticsearch\ClientBuilder::create()
            ->setHosts(["{$host}:{$port}"])
            ->build();
        return $client;
    } catch (Exception $e) {
        // In a real plugin, you'd want to log this error.
        error_log('Elasticsearch Connection Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Creates the Elasticsearch index and ingest pipeline if they don't already exist.
 *
 * @return bool True on success, false on failure.
 */
function wpfmes_setup_es_index_and_pipeline() {
    $client = wpfmes_get_es_client();
    if (!$client) {
        return false;
    }

    $index_name = 'wpfmes_files';
    $pipeline_name = 'wpfmes_attachment_pipeline';

    try {
        // 1. Create the Ingest Pipeline
        if (!$client->ingest()->getPipeline(['id' => $pipeline_name])->asBool()) {
            $client->ingest()->putPipeline([
                'id' => $pipeline_name,
                'body' => [
                    'description' => 'Extracts file content for indexing.',
                    'processors' => [
                        [
                            'attachment' => [
                                'field' => 'data',
                                'target_field' => 'attachment',
                                'indexed_chars' => -1 // Index all characters
                            ]
                        ]
                    ]
                ]
            ]);
        }

        // 2. Create the Index with mapping
        if (!$client->indices()->exists(['index' => $index_name])->asBool()) {
            $client->indices()->create([
                'index' => $index_name,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'path' => ['type' => 'keyword'],
                            'filename' => ['type' => 'text'],
                            'attachment.content' => ['type' => 'text'],
                            'data' => ['type' => 'binary', 'doc_values' => false, 'store' => false] // The base64 data itself
                        ]
                    ]
                ]
            ]);
        }
        return true;
    } catch (Exception $e) {
        error_log('Elasticsearch Index/Pipeline Setup Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to recursively get all file paths from a directory.
 *
 * @param string $dir The directory to scan.
 * @return array A flat array of file paths.
 */
function wpfmes_get_all_files($dir) {
    $files = [];
    $items = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        if ($item->isFile()) {
            $files[] = $item->getRealPath();
        }
    }
    return $files;
}

/**
 * Indexes all files from the configured directory into Elasticsearch.
 *
 * @return array An array with 'success' (bool) and 'message' (string).
 */
function wpfmes_index_files() {
    $client = wpfmes_get_es_client();
    if (!$client) {
        return ['success' => false, 'message' => 'Could not connect to Elasticsearch.'];
    }

    // Ensure the index and pipeline are ready
    if (!wpfmes_setup_es_index_and_pipeline()) {
        return ['success' => false, 'message' => 'Failed to set up Elasticsearch index or pipeline.'];
    }

    $options = get_option('wpfmes_options');
    $upload_dir = wp_upload_dir();
    $base_dir = $options['file_directory'] ?? $upload_dir['basedir'];
    $index_name = 'wpfmes_files';
    $pipeline_name = 'wpfmes_attachment_pipeline';

    if (!is_dir($base_dir) || !is_readable($base_dir)) {
        return ['success' => false, 'message' => 'The configured directory is not readable or does not exist.'];
    }

    try {
        // Clear all previous documents from the index for a fresh start
        $client->deleteByQuery([
            'index' => $index_name,
            'body' => [
                'query' => [
                    'match_all' => new \stdClass()
                ]
            ]
        ]);

        $files = wpfmes_get_all_files($base_dir);
        if (empty($files)) {
            return ['success' => true, 'message' => 'No files found to index.'];
        }

        $params = ['body' => []];
        $count = 0;

        foreach ($files as $file_path) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index_name,
                    'pipeline' => $pipeline_name
                ]
            ];
            $params['body'][] = [
                'path' => $file_path,
                'filename' => basename($file_path),
                'data' => base64_encode(file_get_contents($file_path))
            ];

            $count++;

            // Every 100 documents, send the bulk request
            if ($count % 100 == 0) {
                $client->bulk($params);
                // Reset the batch
                $params = ['body' => []];
            }
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $client->bulk($params);
        }

        return ['success' => true, 'message' => "Successfully indexed {$count} files."];

    } catch (Exception $e) {
        error_log('Elasticsearch Indexing Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during indexing: ' . $e->getMessage()];
    }
}

/**
 * Performs a search query against the Elasticsearch index.
 *
 * @param string $query The search term.
 * @return array The search results from Elasticsearch.
 */
function wpfmes_search_files($query) {
    $client = wpfmes_get_es_client();
    if (!$client) {
        return [];
    }

    $index_name = 'wpfmes_files';

    try {
        $params = [
            'index' => $index_name,
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['filename', 'attachment.content']
                    ]
                ],
                'highlight' => [
                    'fields' => [
                        'attachment.content' => new \stdClass()
                    ]
                ]
            ]
        ];

        $response = $client->search($params);
        return $response['hits']['hits'];

    } catch (Exception $e) {
        error_log('Elasticsearch Search Error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Renders the search results as an HTML list.
 *
 * @param array $results The array of hits from Elasticsearch.
 * @return string The HTML representation of the search results.
 */
function wpfmes_render_search_results($results) {
    if (empty($results)) {
        return '<p>No matching files found.</p>';
    }

    $html = '<ul>';
    foreach ($results as $hit) {
        $source = $hit['_source'];
        $file_path = $source['path'];
        $file_url = esc_url(str_replace(ABSPATH, site_url('/'), $file_path));

        $html .= '<li class="wpfmes-search-result">';
        $html .= '<h4><a href="' . $file_url . '" target="_blank">' . esc_html($source['filename']) . '</a></h4>';

        if (!empty($hit['highlight']['attachment.content'])) {
            $html .= '<div class="wpfmes-highlight">';
            foreach ($hit['highlight']['attachment.content'] as $snippet) {
                $html .= '... ' . wp_kses_post($snippet) . ' ...<br>';
            }
            $html .= '</div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}