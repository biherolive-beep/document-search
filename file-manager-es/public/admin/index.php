<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['trigger_indexing'])) {
        // Execute the indexer script
        $output = shell_exec('php ' . __DIR__ . '/../../scripts/indexer.php');
        $message = "Indexing process triggered. Output:<br><pre>$output</pre>";
    } elseif (isset($_POST['save_config'])) {
        $newConfig = [
            'files_root' => $_POST['files_root'],
            'elasticsearch_host' => $_POST['es_host'],
            'elasticsearch_index' => $_POST['es_index'],
            'indexable_file_types' => $_POST['indexable_file_types']
        ];
        save_config($newConfig);
        $message = "Configuration saved successfully.";
        // Reload the page to reflect changes
        header('Location: index.php?message=' . urlencode($message));
        exit;
    }
}

// Display success message if redirected
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body { font-family: sans-serif; }
        .container { width: 800px; margin: 0 auto; }
        .message { background-color: #e0ffe0; border: 1px solid #b0ffb0; padding: 10px; margin-bottom: 20px; }
        form { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        <a href="logout.php">Logout</a>

        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Configuration</h2>
        <form method="post">
            <div>
                <label for="files_root">Files Root Path:</label><br>
                <input type="text" id="files_root" name="files_root" value="<?php echo htmlspecialchars(FILES_ROOT); ?>" size="80">
            </div>
            <br>
            <div>
                <label for="es_host">Elasticsearch Host:</label><br>
                <input type="text" id="es_host" name="es_host" value="<?php echo htmlspecialchars(ELASTICSEARCH_HOST); ?>" size="80">
            </div>
            <br>
            <div>
                <label for="es_index">Elasticsearch Index Name:</label><br>
                <input type="text" id="es_index" name="es_index" value="<?php echo htmlspecialchars(ELASTICSEARCH_INDEX); ?>" size="80">
            </div>
            <br>
            <div>
                <label for="indexable_file_types">Indexable File Types (comma-separated):</label><br>
                <input type="text" id="indexable_file_types" name="indexable_file_types" value="<?php echo htmlspecialchars(INDEXABLE_FILE_TYPES); ?>" size="80">
            </div>
            <br>
            <button type="submit" name="save_config">Save Configuration</button>
        </form>

        <h2>Indexing</h2>
        <form method="post">
            <p>Click the button to start the file indexing process. This may take a while.</p>
            <button type="submit" name="trigger_indexing">Trigger Indexing</button>
        </form>
    </div>
</body>
</html>