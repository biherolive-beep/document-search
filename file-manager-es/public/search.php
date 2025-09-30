<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Elastic\Elasticsearch\ClientBuilder;

$client = ClientBuilder::create()
    ->setHosts([ELASTICSEARCH_HOST])
    ->build();

$query = isset($_GET['q']) ? $_GET['q'] : '';
$results = [];

if ($query) {
    $params = [
        'index' => ELASTICSEARCH_INDEX,
        'body'  => [
            'query' => [
                'match' => [
                    'content' => $query
                ]
            ]
        ]
    ];

    try {
        $response = $client->search($params);
        $results = $response['hits']['hits'];
    } catch (Exception $e) {
        // Handle exception
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <style>
        body { font-family: sans-serif; }
        .result { margin-bottom: 20px; }
        .result-path { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Search Results for "<?php echo htmlspecialchars($query); ?>"</h1>
    <a href="index.php">Back to File List</a>

    <div id="results-container">
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $hit): ?>
                <div class="result">
                    <div class="result-path">
                        <a href="download.php?file=<?php echo urlencode($hit['_source']['filepath']); ?>">
                            <?php echo htmlspecialchars($hit['_source']['filepath']); ?>
                        </a>
                    </div>
                    <div class="result-snippet">
                        <?php
                        // This is a very basic snippet generation.
                        // For a real application, you'd want more sophisticated highlighting.
                        $snippet = substr($hit['_source']['content'], 0, 300);
                        echo htmlspecialchars($snippet) . '...';
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    </div>
</body>
</html>