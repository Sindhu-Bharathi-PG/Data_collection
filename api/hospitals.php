<?php
// api/hospitals.php - Public API to fetch approved hospitals
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all domains (or restrict to your frontend domain)
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/db_config.php';

try {
    $conn = get_db_connection();
    
    // Fetch only APPROVED hospitals
    // Join with tables if you need more details, or just fetch the main profile
    // For now, let's fetch the profile and related data as JSON columns if they exist, 
    // or we might need to do multiple queries if normalized.
    // Based on previous files, data handles: 'highlights', 'reviews', 'departments' as JSON/Text?
    // Let's check the database schema or just select *.
    
    $query = "SELECT * FROM hospital_profiles WHERE status = 'approved' ORDER BY created_at DESC";
    $result = pg_query($conn, $query);
    
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }
    
    $hospitals = pg_fetch_all($result);
    
    if (!$hospitals) {
        echo json_encode([]);
        exit;
    }
    
    // Process data to ensure JSON fields are parsed correctly if they are stored as strings
    foreach ($hospitals as &$hospital) {
         // Decode JSON fields if they are strings
         $jsonFields = ['highlights', 'top_treatments', 'medical_departments', 'doctor_profiles', 'reviews', 'packages'];
         foreach ($jsonFields as $field) {
             if (isset($hospital[$field]) && is_string($hospital[$field])) {
                 $decoded = json_decode($hospital[$field], true);
                 // If decode succeeds, use it; otherwise leave as is (or empty array)
                 $hospital[$field] = ($decoded !== null) ? $decoded : [];
             }
         }
    }
    
    echo json_encode($hospitals);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
