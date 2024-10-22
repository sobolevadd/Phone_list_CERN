<?php
// Include the database connection file
include 'dbConnection.php';

// Get the expert ID from the GET request
$expert_id = isset($_GET['expert_id']) ? intval($_GET['expert_id']) : 0;

// Prepare the SQL query to fetch details for the specified expert
$stmt = $conn->prepare("
    SELECT name, Private_phone as phone
    FROM Expert_person
    WHERE Id = ?
");

// Bind the expert ID parameter
$stmt->bind_param("i", $expert_id);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the expert details
$expert = $result->fetch_assoc();

// Set the content type to JSON
header('Content-Type: application/json');

// Output the expert details as JSON
echo json_encode($expert);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
