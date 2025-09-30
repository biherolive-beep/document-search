<?php
require_once '../config/config.php';

function getFileTree($dir) {
    $result = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $result[] = [
                'name' => $item,
                'type' => 'folder',
                'path' => $path,
                'children' => getFileTree($path)
            ];
        } else {
            $result[] = [
                'name' => $item,
                'type' => 'file',
                'path' => $path
            ];
        }
    }
    return $result;
}

function printFileTree($tree) {
    echo '<ul>';
    foreach ($tree as $item) {
        if ($item['type'] == 'folder') {
            echo '<li><strong>' . htmlspecialchars($item['name']) . '</strong>';
            printFileTree($item['children']);
            echo '</li>';
        } else {
            echo '<li>' . htmlspecialchars($item['name']) . '</li>';
        }
    }
    echo '</ul>';
}

$fileTree = getFileTree(FILES_ROOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <style>
        body { font-family: sans-serif; }
        ul { list-style-type: none; }
        li { padding-left: 20px; }
    </style>
</head>
<body>
    <h1>File Manager</h1>
    <div id="search-container">
        <form action="search.php" method="get">
            <input type="text" name="q" placeholder="Search files..." />
            <button type="submit">Search</button>
        </form>
    </div>
    <div id="file-tree">
        <?php printFileTree($fileTree); ?>
    </div>
</body>
</html>