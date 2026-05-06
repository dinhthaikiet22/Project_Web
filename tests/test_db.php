<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Connecting to MySQL...\n";
try {
    $conn = new PDO('mysql:host=localhost;dbname=cycle_trust', 'root', '');
    echo "Connected successfully to localhost.\n";
} catch (PDOException $e) {
    echo "Failed localhost: " . $e->getMessage() . "\n";
}

try {
    $conn2 = new PDO('mysql:host=127.0.0.1;dbname=cycle_trust', 'root', '');
    echo "Connected successfully to 127.0.0.1.\n";
} catch (PDOException $e) {
    echo "Failed 127.0.0.1: " . $e->getMessage() . "\n";
}
