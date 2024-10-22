<?php
include 'dbConnection.php';

$system_id = $_GET['system_id'];

// Fetch all experts that are NOT associated with the given system
$expertsStmt = $conn->prepare("
    SELECT e.Id, e.name, e.Private_phone AS phone
    FROM Expert_person e
    LEFT JOIN Expert_system_person esp ON e.Id = esp.Person_id AND esp.System_id = ?
    WHERE esp.Person_id IS NULL
    ORDER BY e.name ASC
");

$expertsStmt->bind_param("i", $system_id);
$expertsStmt->execute();
$expertsResult = $expertsStmt->get_result();
$experts = [];
while ($row = $expertsResult->fetch_assoc()) {
    $experts[] = $row;
}

// Handle case when no experts are found
if (empty($experts)) {
    $response = [
        'status' => 'error',
        'message' => 'No experts found that are not associated with the selected system.',
        'experts' => []
    ];
} else {
    // Create the response array
    $response = [
        'status' => 'success',
        'experts' => $experts,
        'expert' => $experts[0],
    ];
}

// Set the header and output the response
header('Content-Type: application/json');
echo json_encode($response);

// Close statements and connection
$expertsStmt->close();
$conn->close();
?>
