<?php
// Load configuration from JSON file
$configJson = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configJson, true);

// Define constants from the loaded configuration
define('FILES_ROOT', $config['files_root']);
define('ELASTICSEARCH_HOST', $config['elasticsearch_host']);
define('ELASTICSEARCH_INDEX', $config['elasticsearch_index']);
define('INDEXABLE_FILE_TYPES', $config['indexable_file_types']);

// Function to save configuration
function save_config($newConfig) {
    $json = json_encode($newConfig, JSON_PRETTY_PRINT);
    file_put_contents(__DIR__ . '/config.json', $json);
}
?>