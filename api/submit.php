<?php
// api/submit.php - JSON API for hospital submission (CORS-enabled)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
    exit;
}

// Simple validators
function validate_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
function validate_url($url) { return filter_var($url, FILTER_VALIDATE_URL); }

$errors = [];
$name = trim($data['name'] ?? '');
if ($name === '') $errors[] = 'Hospital name is required.';

$type = $data['type'] ?? '';
if (!in_array($type, ['Government', 'Private'])) $errors[] = 'Invalid hospital type.';

$address = trim($data['address'] ?? '');
if ($address === '') $errors[] = 'Address is required.';

$city = trim($data['city'] ?? '');
if ($city === '') $errors[] = 'City is required.';

$contact_email = trim($data['contact_email'] ?? '');
if ($contact_email && !validate_email($contact_email)) $errors[] = 'Invalid email address.';

$contact_website = trim($data['contact_website'] ?? '');
if ($contact_website && !validate_url($contact_website)) $errors[] = 'Invalid website URL.';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

require_once __DIR__ . '/../db_config.php';

try {
    $conn = get_db_connection();

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

    // Build values
    $establishment_year = isset($data['establishment_year']) && $data['establishment_year'] !== '' ? (int)$data['establishment_year'] : null;
    $beds = isset($data['beds']) && $data['beds'] !== '' ? (int)$data['beds'] : null;

    $patient_count = json_encode([
        'total' => isset($data['patient_count_total']) && $data['patient_count_total'] !== '' ? (int)$data['patient_count_total'] : null,
        'annual' => isset($data['patient_count_annual']) && $data['patient_count_annual'] !== '' ? (int)$data['patient_count_annual'] : null
    ]);

    $accreditations = is_array($data['accreditations'] ?? null) ? $data['accreditations'] : [];
    if (!empty($data['accreditations_other'])) {
        $accreditations = array_merge($accreditations, array_map('trim', explode(',', $data['accreditations_other'])));
    }
    $accreditations_json = json_encode(array_filter($accreditations));

    $location = json_encode([
        'address' => $address,
        'city' => $city,
        'state' => $data['state'] ?? '',
        'lat' => isset($data['latitude']) && $data['latitude'] !== '' ? (float)$data['latitude'] : null,
        'lng' => isset($data['longitude']) && $data['longitude'] !== '' ? (float)$data['longitude'] : null
    ]);

    $contact = json_encode([
        'general' => $data['contact_general'] ?? null,
        'emergency' => $data['contact_emergency'] ?? null,
        'email' => $contact_email ?: null,
        'website' => $contact_website ?: null
    ]);

    $description = json_encode([
        'brief' => $data['description_brief'] ?? null,
        'detailed' => $data['description_detailed'] ?? null,
        'highlights' => $data['highlights'] ?? []
    ]);

    $departments_json = json_encode($data['departments'] ?? []);
    $specialties_json = json_encode($data['specialties'] ?? []);
    $equipment_json = json_encode($data['equipment'] ?? []);

    $facilities = is_array($data['facilities'] ?? null) ? $data['facilities'] : [];
    if (!empty($data['facilities_other'])) {
        $facilities = array_merge($facilities, array_map('trim', explode(',', $data['facilities_other'])));
    }
    $facilities_json = json_encode(array_filter($facilities));

    $doctors_json = json_encode($data['doctors'] ?? []);
    $treatments_json = json_encode($data['treatments'] ?? []);
    $packages_json = json_encode($data['packages'] ?? []);
    $photos_json = json_encode($data['photos'] ?? []);

    $params = [
        $name,
        $type,
        $establishment_year,
        $accreditations_json,
        $beds,
        $patient_count,
        $location,
        $contact,
        $description,
        $departments_json,
        $specialties_json,
        $equipment_json,
        $facilities_json,
        $doctors_json,
        $treatments_json,
        $packages_json,
        $photos_json,
        'pending'
    ];

    $result = pg_query_params($conn, $sql, $params);
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log('Submit API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}

?>
