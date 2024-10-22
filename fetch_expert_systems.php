<?php
include 'dbConnection.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [];

// Validate the incoming expert_id parameter
$expert_id = isset($_GET['expert_id']) ? intval($_GET['expert_id']) : 0;

if ($expert_id <= 0) {
    echo json_encode(['error' => 'Invalid expert ID']);
    exit;
}

// Fetch all systems from the Expert table
$allSystemsStmt = $conn->prepare("
    SELECT e.Id AS System_id, e.Desc AS System_name
    FROM Expert e
    ORDER BY e.Desc ASC
");
$allSystemsStmt->execute();
$systemsResult = $allSystemsStmt->get_result();
$response['systems'] = [];
while ($row = $systemsResult->fetch_assoc()) {
    $response['systems'][] = $row;
}

// Fetch the systems associated with the specific expert
$associatedSystemsStmt = $conn->prepare("
    SELECT System_id
    FROM Expert_system_person
    WHERE Person_id = ?
");
$associatedSystemsStmt->bind_param('i', $expert_id);
$associatedSystemsStmt->execute();
$associatedSystemsResult = $associatedSystemsStmt->get_result();
$response['associated_systems'] = [];
while ($row = $associatedSystemsResult->fetch_assoc()) {
    $response['associated_systems'][] = $row['System_id'];
}

// Debugging output
error_log('Fetch Expert Systems Response: ' . print_r($response, true));

// Output the response as JSON
echo json_encode($response);

// Close statements and connection
$allSystemsStmt->close();
$associatedSystemsStmt->close();
$conn->close();
?>
