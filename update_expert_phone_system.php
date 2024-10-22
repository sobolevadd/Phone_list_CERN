<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the required POST variables are set
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['expert_id']) || !isset($_POST['phone'])) {
        die("Missing required fields.");
    }

    $expertId = $_POST['expert_id']; // Expert ID to update
    $expertPhone = $_POST['phone']; // New phone number
    $systemIds = isset($_POST['system_ids']) ? $_POST['system_ids'] : []; // Array of system IDs

    // Validate phone number
    if (empty($expertPhone)) {
        die("Phone number cannot be empty.");
    }

    // Validate phone number format
    if (!preg_match('/^\+?\d*$/', $expertPhone)) {
        die("Invalid phone number format. It should start with an optional + followed by digits.");
    }

    // Update the expert's phone number
    $stmt = $conn->prepare("UPDATE Expert_person SET Private_phone = ? WHERE Id = ?");
    $stmt->bind_param("si", $expertPhone, $expertId);

    if (!$stmt->execute()) {
        echo "Failed to update expert's phone number: " . $stmt->error;
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    // Remove existing system associations for this expert
    $deleteStmt = $conn->prepare("DELETE FROM Expert_system_person WHERE person_id = ?");
    $deleteStmt->bind_param("i", $expertId);

    if (!$deleteStmt->execute()) {
        echo "Failed to remove existing system associations: " . $deleteStmt->error;
        $deleteStmt->close();
        $conn->close();
        exit;
    }

    $deleteStmt->close();

    if (!empty($systemIds)) {
        // Add new system associations
        $insertStmt = $conn->prepare("INSERT INTO Expert_system_person (system_id, person_id) VALUES (?, ?)");

        foreach ($systemIds as $systemId) {
            // Validate if the system exists
            $checkSystemStmt = $conn->prepare("SELECT Id FROM Expert WHERE Id = ?");
            $checkSystemStmt->bind_param("i", $systemId);
            $checkSystemStmt->execute();
            $checkSystemStmt->store_result();

            if ($checkSystemStmt->num_rows === 0) {
                echo "Invalid system_id: No matching system found for system ID $systemId.";
                $checkSystemStmt->close();
                $insertStmt->close();
                $conn->close();
                exit;
            }

            $checkSystemStmt->close();
            
            $insertStmt->bind_param("ii", $systemId, $expertId);

            if (!$insertStmt->execute()) {
                echo "Failed to associate expert with system ID $systemId: " . $insertStmt->error;
                $insertStmt->close();
                $conn->close();
                exit;
            }
        }

        $insertStmt->close();
    }

    $conn->close();
    
    echo "Expert updated and assigned successfully.";
} else {
    echo "Invalid request.";
}
?>
