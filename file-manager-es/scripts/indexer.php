<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Elastic\Elasticsearch\ClientBuilder;

// --- Elasticsearch Client Initialization ---
$client = ClientBuilder::create()
    ->setHosts([ELASTICSEARCH_HOST])
    ->build();

// --- Tika Content Extraction Function ---
function extractTextWithTika($filePath) {
    // We'll use Tika's REST server. Ensure Tika server is running.
    // java -jar /opt/tika/tika-server-standard.jar &
    $tikaUrl = 'http://localhost:9998/tika';
    $fileData = file_get_contents($filePath);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tikaUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: ' . mime_content_type($filePath),
        'Accept: text/plain'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// --- Indexing Logic ---
function indexFiles($dir, $client) {
    $allowedExtensions = explode(',', INDEXABLE_FILE_TYPES);
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($items as $item) {
        if ($item->isDir()) {
            continue;
        }

        $filePath = $item->getRealPath();
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            continue;
        }

        echo "Processing: $filePath\n";

        $content = extractTextWithTika($filePath);

        if ($content) {
            $params = [
                'index' => ELASTICSEARCH_INDEX,
                'id'    => $filePath,
                'body'  => [
                    'filepath' => $filePath,
                    'content'  => $content
                ]
            ];

            try {
                $response = $client->index($params);
                echo "Indexed: " . $filePath . "\n";
            } catch (Exception $e) {
                echo "Error indexing " . $filePath . ": " . $e->getMessage() . "\n";
            }
        } else {
            echo "Could not extract content from: $filePath\n";
        }
    }
}

// --- Run the Indexer ---
echo "Starting file indexing...\n";
indexFiles(FILES_ROOT, $client);
echo "Indexing complete.\n";
?>