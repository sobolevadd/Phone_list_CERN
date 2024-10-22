<?php
require 'vendor/autoload.php';

// Initialize Dotenv and load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

// Retrieve environment variables
$servername = $_ENV['DB_SERVERNAME'];
$username = $_ENV['DB_USERNAME'];
$dbname = $_ENV['DB_NAME'];
$password = $_ENV['DB_PASSWORD'];

// Validate environment variables
if (!$servername || !$username || !$dbname) {
    die("Configuration error. Please check the environment variables. Servername: " . ($servername ? $servername : "not set") . ", Username: " . ($username ? $username : "not set") . ", DB Name: " . ($dbname ? $dbname : "not set"));
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
?>
