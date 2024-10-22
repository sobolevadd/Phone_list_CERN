<?php
include 'dbConnection.php';
include 'functions.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Call the function to assign experts
assignExpertsRandomly($conn);

// Close the database connection
$conn->close();
?>
