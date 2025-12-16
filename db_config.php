<?php
function get_db_connection() {
    // Try environment variables first (Vercel/Production)
    if (getenv('DB_HOST')) {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
    } 
    // Fallback to database.ini (Local Development)
    elseif (file_exists(__DIR__ . '/database.ini')) {
        $config = parse_ini_file(__DIR__ . '/database.ini', true);
        if ($config && isset($config['database'])) {
            $host = $config['database']['host'];
            $port = $config['database']['port'];
            $name = $config['database']['name'];
            $user = $config['database']['user'];
            $password = $config['database']['password'];
        }
    }

    if (!isset($host)) {
        die('Database configuration not found. Please set Environment Variables or create database.ini.');
    }

    // Extract endpoint ID for Neon (if applicable)
    $endpoint = explode('.', $host)[0];
    
    $connectionString = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s sslmode=require options='project=%s'",
        $host, $port, $name, $user, $password, $endpoint
    );

    try {
        $conn = pg_connect($connectionString);
        if (!$conn) throw new Exception('Connection failed');
        return $conn;
    } catch (Exception $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
?>
