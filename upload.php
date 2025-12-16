<?php
// upload.php - Cloudinary image upload handler
header('Content-Type: application/json');

$response = ['success' => false, 'url' => '', 'error' => ''];

// Load Cloudinary SDK
require 'vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

// Configure Cloudinary
$config = Configuration::instance([
    'cloud' => [
        'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
        'api_key' => getenv('CLOUDINARY_API_KEY'),
        'api_secret' => getenv('CLOUDINARY_API_SECRET'),
    ],
]);

$cloudinary = new Cloudinary($config);

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Validate upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $response['error'] = $errorMessages[$file['error']] ?? 'Unknown upload error';
        echo json_encode($response);
        exit;
    }

    // Validate file size (10MB max)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        $response['error'] = 'File too large. Maximum size is 10MB.';
        echo json_encode($response);
        exit;
    }

    // Validate MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        $response['error'] = 'Invalid file type. Only JPG, PNG, and WEBP images are allowed.';
        echo json_encode($response);
        exit;
    }

    // Validate image dimensions (optional - ensure it's actually an image)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $response['error'] = 'File is not a valid image.';
        echo json_encode($response);
        exit;
    }

    // Upload to Cloudinary
    $filePath = $file['tmp_name'];
    try {
        $uploadResponse = $cloudinary->uploadApi()->upload($filePath, [
            'folder' => 'uploads', // Optional: Specify folder in Cloudinary
        ]);

        $response['success'] = true;
        $response['url'] = $uploadResponse['secure_url'];
        $response['filename'] = $uploadResponse['public_id'];
        $response['size'] = $uploadResponse['bytes'];
    } catch (Exception $e) {
        $response['error'] = 'Cloudinary upload failed: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'No file uploaded or invalid request method.';
}

echo json_encode($response);
exit;
