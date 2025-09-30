<?php
require_once __DIR__ . '/../config/config.php';

if (isset($_GET['file'])) {
    $filePath = $_GET['file'];

    // Security check: Ensure the file is within the defined FILES_ROOT
    $basePath = realpath(FILES_ROOT);
    $userPath = realpath($filePath);

    if ($userPath && strpos($userPath, $basePath) === 0 && file_exists($userPath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($userPath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($userPath));
        readfile($userPath);
        exit;
    }
}

// If the file is not found or not allowed, return a 404 error
http_response_code(404);
echo 'File not found.';
?>