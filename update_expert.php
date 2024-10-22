<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$system_id = $_POST['system_id'];
$expert_id = $_POST['expert_id'];
$phone = $_POST['phone'];

// Validate phone number format
if (empty($phone)) {
    die("Phone number cannot be empty.");
}

if (!preg_match('/^\+?\d*$/', $phone)) {
    die("Invalid phone number format. It should start with an optional + followed by digits.");
}

// Update Assigned_expert_id in the Expert table
$stmt = $conn->prepare("
    UPDATE Expert exp
    JOIN Expert_system_person esp ON exp.Id = esp.System_id
    SET exp.Assigned_expert_id = ?
    WHERE esp.System_id = ?
");
$stmt->bind_param("ii", $expert_id, $system_id);

if ($stmt->execute()) {
    // Update the phone number in Expert_person
    $stmt = $conn->prepare("
        UPDATE Expert_person
        SET Private_phone = ?
        WHERE Id = ?
    ");
    $stmt->bind_param("si", $phone, $expert_id);
    
    if ($stmt->execute()) {
        echo "Expert updated and assigned successfully.";
    } else {
        echo "Error updating phone number: " . $stmt->error;
    }
} else {
    echo "Error updating Assigned_expert_id in Expert table: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
