<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables from .env.local
$envFile = __DIR__ . '/../.env.local';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \"'");
        putenv("$key=$val");
    }
}

$cloud = getenv('CLOUDINARY_CLOUD_NAME');
$key = getenv('CLOUDINARY_API_KEY');
$secret = getenv('CLOUDINARY_API_SECRET');

if (!$cloud || !$key || !$secret) {
    http_response_code(500);
    echo json_encode(['error' => 'Cloudinary not configured. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET.']);
    exit;
}

$timestamp = time();
$folder = 'hospital_profiles';

// Build signature string from params (sorted lexicographically)
$params = [
    'folder' => $folder,
    'timestamp' => $timestamp
];
ksort($params);
$toSign = http_build_query($params);
$signature = sha1($toSign . $secret);

echo json_encode([
    'cloud_name' => $cloud,
    'api_key' => $key,
    'timestamp' => $timestamp,
    'signature' => $signature,
    'upload_url' => "https://api.cloudinary.com/v1_1/{$cloud}/image/upload",
    'folder' => $folder
]);

exit;

?>
