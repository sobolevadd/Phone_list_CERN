<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expertName = $_POST['new_expert_name'];
    $expertPhone = $_POST['new_expert_phone'];
    $systemId = $_POST['system_id']; // System ID to associate with

    // Validate phone number
    if (empty($expertPhone)) {
        echo json_encode([
            'status' => 'error',
            'message' => "Phone number cannot be empty."
        ]);
        exit;
    }

    // Validate phone number format
    if (!preg_match('/^\+?\d*$/', $expertPhone)) {
        echo json_encode([
            'status' => 'error',
            'message' => "Invalid phone number format. It should start with an optional + followed by digits."
        ]);
        exit;
    }

    // Insert new expert into Experts table
    $stmt = $conn->prepare("INSERT INTO Expert_person (name, Private_phone) VALUES (?, ?)");
    $stmt->bind_param("ss", $expertName, $expertPhone);

    if ($stmt->execute()) {
        $expertId = $stmt->insert_id; // Get the ID of the newly inserted expert

        // Check if the systemId exists
        $checkSystemStmt = $conn->prepare("SELECT Id FROM Expert WHERE Id = ?");
        $checkSystemStmt->bind_param("i", $systemId);
        $checkSystemStmt->execute();
        $checkSystemStmt->store_result();

        if ($checkSystemStmt->num_rows === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "Invalid system_id: No matching system found."
            ]);
            $stmt->close();
            $conn->close();
            exit;
        }

        // Associate the new expert with the system in Expert_system_person table
        $assocStmt = $conn->prepare("INSERT INTO Expert_system_person (system_id, person_id) VALUES (?, ?)
                                     ON DUPLICATE KEY UPDATE person_id = VALUES(person_id)");
        $assocStmt->bind_param("ii", $systemId, $expertId);

        if ($assocStmt->execute()) {
            // Prepare the response
            $response = [
                'status' => 'success',
                'expert' => [
                    'expert_id' => $expertId,
                    'expert_name' => $expertName,
                    'phone' => $expertPhone
                ],
                'message' => "New expert added and assigned successfully."
            ];
            echo json_encode($response);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => "Failed to associate the expert with the system: " . $assocStmt->error
            ]);
        }

        $assocStmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => "Failed to add new expert: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => "Invalid request."
    ]);
}
?>