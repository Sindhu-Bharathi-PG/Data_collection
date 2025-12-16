<?php
// submit.php - Process hospital profile submission with comprehensive validation

// Load DB config from INI (gitignored)
$config = parse_ini_file(__DIR__ . '/database.ini', true);
if (!$config || !isset($config['database'])) {
    die('Database configuration not found.');
}
// Brace removed here

// Helper function to execute queries using pg_* functions
function db_query($sql, $params = []) {
    $conn = $GLOBALS['pgConn'];
    
    if (empty($params)) {
        $result = pg_query($conn, $sql);
    } else {
        $result = pg_query_params($conn, $sql, $params);
    }
    
    if (!$result) {
        throw new Exception('Query failed: ' . pg_last_error($conn));
    }
    
    return $result;
}

function db_insert($sql, $params = []) {
    $result = db_query($sql, $params);
    return $result !== false;
}

// Helper functions
function get_post($key, $default = null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function sanitize($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Error collection
$errors = [];

// ===== BASIC INFORMATION =====
$name = get_post('name');
if (empty($name)) {
    $errors[] = 'Hospital name is required.';
}

$type = get_post('type');
if (!in_array($type, ['Government', 'Private'])) {
    $errors[] = 'Invalid hospital type. Must be Government or Private.';
}

$establishment_year = get_post('establishment_year');
if ($establishment_year && (!ctype_digit($establishment_year) || $establishment_year < 1800 || $establishment_year > 2100)) {
    $errors[] = 'Establishment year must be between 1800 and 2100.';
}

$beds = get_post('beds');
if ($beds && (!ctype_digit($beds) || $beds < 0)) {
    $errors[] = 'Beds must be a non-negative integer.';
}

$patient_count_total = get_post('patient_count_total');
$patient_count_annual = get_post('patient_count_annual');

// Build patient count JSON
$patient_count = json_encode([
    'total' => $patient_count_total ? (int)$patient_count_total : null,
    'annual' => $patient_count_annual ? (int)$patient_count_annual : null
]);

// Accreditations
$accreditations = isset($_POST['accreditations']) ? $_POST['accreditations'] : [];
$accreditations_other = get_post('accreditations_other');
if ($accreditations_other) {
    $accreditations = array_merge($accreditations, array_map('trim', explode(',', $accreditations_other)));
}
$accreditations_json = json_encode(array_filter($accreditations));

// ===== LOCATION & CONTACT =====
$address = get_post('address');
if (empty($address)) {
    $errors[] = 'Full address is required.';
}

$city = get_post('city');
if (empty($city)) {
    $errors[] = 'City is required.';
}

$state = get_post('state', '');
$latitude = get_post('latitude');
$longitude = get_post('longitude');

// Validate coordinates if provided
if ($latitude && !is_numeric($latitude)) {
    $errors[] = 'Latitude must be a valid number.';
}
if ($longitude && !is_numeric($longitude)) {
    $errors[] = 'Longitude must be a valid number.';
}

// Build location JSON
$location = json_encode([
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'lat' => $latitude ? (float)$latitude : null,
    'lng' => $longitude ? (float)$longitude : null
]);

// Contact information
$contact_general = get_post('contact_general');
$contact_emergency = get_post('contact_emergency');
$contact_email = get_post('contact_email');
$contact_website = get_post('contact_website');

// Validate email
if ($contact_email && !validate_email($contact_email)) {
    $errors[] = 'Invalid email address format.';
}

// Validate website URL
if ($contact_website && !validate_url($contact_website)) {
    $errors[] = 'Invalid website URL format.';
}

// Build contact JSON
$contact = json_encode([
    'general' => $contact_general,
    'emergency' => $contact_emergency,
    'email' => $contact_email,
    'website' => $contact_website
]);

// ===== CONTENT & DESCRIPTIONS =====
$description_brief = get_post('description_brief');
$description_detailed = get_post('description_detailed');

// Get highlights and reviews from JSON
$highlights = json_decode(get_post('highlights', '[]'), true) ?? [];
$reviews = json_decode(get_post('reviews', '[]'), true) ?? [];

// Build description JSON
$description = json_encode([
    'brief' => $description_brief,
    'detailed' => $description_detailed,
    'highlights' => $highlights
]);

// ===== CLINICAL CAPABILITIES =====
$departments = json_decode(get_post('departments', '[]'), true) ?? [];
$specialties = json_decode(get_post('specialties', '[]'), true) ?? [];
$equipment = json_decode(get_post('equipment', '[]'), true) ?? [];

// Facilities
$facilities = isset($_POST['facilities']) ? $_POST['facilities'] : [];
$facilities_other = get_post('facilities_other');
if ($facilities_other) {
    $facilities = array_merge($facilities, array_map('trim', explode(',', $facilities_other)));
}
$facilities_json = json_encode(array_filter($facilities));

// ===== DYNAMIC SECTIONS =====
$doctors = json_decode(get_post('doctors', '[]'), true) ?? [];
$treatments = json_decode(get_post('treatments', '[]'), true) ?? [];
$packages = json_decode(get_post('packages', '[]'), true) ?? [];
$photos = json_decode(get_post('photos_json', '[]'), true) ?? [];

// Validate JSON decoding
if (json_last_error() !== JSON_ERROR_NONE) {
    $errors[] = 'Invalid JSON data in form submission.';
}

// ===== ERROR HANDLING =====
if (!empty($errors)) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Submission Error</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-red-50 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Submission Errors</h1>
            <div class="space-y-2">';
    foreach ($errors as $error) {
        echo '<p class="text-red-700">• ' . sanitize($error) . '</p>';
    }
    echo '      </div>
            <div class="mt-6">
                <a href="javascript:history.back()" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    ← Go Back and Fix Errors
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// ===== DATABASE INSERTION =====
try {
    $sql = "INSERT INTO hospital_profiles (
        name, type, establishment_year, accreditations, beds, 
        patient_count, location, contact, description,
        departments, specialties, equipment, facilities,
        doctors, treatments, packages, photos, status
    ) VALUES (
        $1, $2, $3, $4, $5,
        $6, $7, $8, $9,
        $10, $11, $12, $13,
        $14, $15, $16, $17, $18
    )";
    
    $params = [
        sanitize($name),
        $type,
        $establishment_year ?: null,
        $accreditations_json,
        $beds ?: null,
        $patient_count,
        $location,
        $contact,
        $description,
        json_encode($departments),
        json_encode($specialties),
        json_encode($equipment),
        $facilities_json,
        json_encode($doctors),
        json_encode($treatments),
        json_encode($packages),
        json_encode($photos),
        'pending'
    ];
    
    db_query($sql, $params);

    // Success - redirect with flash message
    header('Location: index.php?status=success');
    exit;

} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Error</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-red-50 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full">
            <h1 class="text-2xl font-bold text-red-600 mb-4">Database Error</h1>
            <p class="text-gray-700 mb-4">An error occurred while saving your submission. Please try again later.</p>
            <div class="mt-6">
                <a href="index.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    ← Return to Form
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
?>
