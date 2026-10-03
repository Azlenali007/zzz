<?php
/**
 * Built-in PHP Development Server Router
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$filePath = __DIR__ . '/public' . $uri;

// If requested static file exists in public directory, let built-in web server serve it
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// Otherwise pass to front controller
require_once __DIR__ . '/public/index.php';
